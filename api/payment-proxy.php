<?php
/**
 * API Proxy para pagamentos do AI SEO PRO
 * 
 * HOSPEDE ESTE ARQUIVO EM QUALQUER SERVIDOR PHP
 * Exemplo: https://seu-dominio.com/api/payment-proxy.php
 * 
 * Depois configure a URL no plugin.
 */

// Sua chave do Asaas (MANTENHA SEGURA AQUI)
define('ASAAS_API_KEY', '$aact_COLOQUE_SUA_CHAVE_AQUI');

// Modo sandbox (true para testes, false para produção)
define('ASAAS_SANDBOX', false);

// Domínios permitidos (adicione os domínios dos seus clientes ou deixe vazio para todos)
$allowed_origins = [
    // '*', // Permite todos (menos seguro, mas funciona para qualquer cliente)
];

// ============================================================
// NÃO MODIFIQUE ABAIXO DESTA LINHA
// ============================================================

header('Content-Type: application/json');

// CORS
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
if (empty($allowed_origins) || in_array('*', $allowed_origins) || in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$api_url = ASAAS_SANDBOX 
    ? 'https://sandbox.asaas.com/api/v3/'
    : 'https://api.asaas.com/v3/';

/**
 * Faz requisição à API do Asaas
 */
function asaas_request($endpoint, $method = 'GET', $data = null) {
    global $api_url;
    
    $ch = curl_init($api_url . $endpoint);
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'access_token: ' . ASAAS_API_KEY,
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => $http_code >= 200 && $http_code < 300,
        'data' => json_decode($response, true),
        'http_code' => $http_code,
    ];
}

/**
 * Gera chave de licença
 */
function generate_license_key() {
    $segments = [];
    for ($i = 0; $i < 4; $i++) {
        $segments[] = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
    }
    return 'AISEO-' . implode('-', $segments);
}

// Processa ações
switch ($action) {
    case 'create_payment':
        $name = $input['name'] ?? '';
        $email = $input['email'] ?? '';
        $cpf = $input['cpf'] ?? '';
        $plan_id = $input['plan'] ?? '';
        $payment_method = $input['payment_method'] ?? 'PIX';
        
        if (!$name || !$email) {
            echo json_encode(['success' => false, 'error' => 'Nome e email obrigatórios']);
            exit;
        }
        
        // Planos
        $plans = [
            'monthly' => ['name' => '30 Dias', 'price' => 29.90, 'days' => 30],
            'yearly' => ['name' => '1 Ano', 'price' => 297.00, 'days' => 365],
            'lifetime' => ['name' => 'Vitalício', 'price' => 497.00, 'days' => 0],
        ];
        
        if (!isset($plans[$plan_id])) {
            echo json_encode(['success' => false, 'error' => 'Plano inválido']);
            exit;
        }
        
        $plan = $plans[$plan_id];
        
        // Busca ou cria cliente
        $search = asaas_request('customers?email=' . urlencode($email));
        
        if ($search['success'] && !empty($search['data']['data'])) {
            $customer = $search['data']['data'][0];
        } else {
            $customer_data = ['name' => $name, 'email' => $email];
            if ($cpf) {
                $customer_data['cpfCnpj'] = preg_replace('/\D/', '', $cpf);
            }
            $result = asaas_request('customers', 'POST', $customer_data);
            if (!$result['success']) {
                echo json_encode(['success' => false, 'error' => 'Erro ao criar cliente']);
                exit;
            }
            $customer = $result['data'];
        }
        
        // Gera licença
        $license_key = generate_license_key();
        
        // Cria cobrança
        $payment_data = [
            'customer' => $customer['id'],
            'billingType' => $payment_method,
            'value' => $plan['price'],
            'dueDate' => date('Y-m-d', strtotime('+3 days')),
            'description' => 'AI SEO PRO - ' . $plan['name'],
            'externalReference' => json_encode([
                'license_key' => $license_key,
                'plan_id' => $plan_id,
                'days' => $plan['days'],
                'email' => $email,
            ]),
        ];
        
        $payment = asaas_request('payments', 'POST', $payment_data);
        
        if (!$payment['success']) {
            echo json_encode(['success' => false, 'error' => $payment['data']['errors'][0]['description'] ?? 'Erro ao criar cobrança']);
            exit;
        }
        
        $response = [
            'success' => true,
            'payment_id' => $payment['data']['id'],
            'license_key' => $license_key,
            'status' => $payment['data']['status'],
        ];
        
        if (isset($payment['data']['invoiceUrl'])) {
            $response['invoice_url'] = $payment['data']['invoiceUrl'];
        }
        if (isset($payment['data']['bankSlipUrl'])) {
            $response['boleto_url'] = $payment['data']['bankSlipUrl'];
        }
        
        // PIX
        if ($payment_method === 'PIX') {
            $pix = asaas_request('payments/' . $payment['data']['id'] . '/pixQrCode');
            if ($pix['success']) {
                $response['pix'] = [
                    'payload' => $pix['data']['payload'] ?? '',
                    'qrcode_image' => $pix['data']['encodedImage'] ?? '',
                ];
            }
        }
        
        echo json_encode($response);
        break;
        
    case 'check_payment':
        $payment_id = $input['payment_id'] ?? '';
        
        if (!$payment_id) {
            echo json_encode(['success' => false, 'error' => 'ID não informado']);
            exit;
        }
        
        $result = asaas_request('payments/' . $payment_id);
        
        if ($result['success']) {
            $is_paid = in_array($result['data']['status'], ['CONFIRMED', 'RECEIVED']);
            echo json_encode([
                'success' => true,
                'status' => $result['data']['status'],
                'is_paid' => $is_paid,
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erro ao verificar']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Ação inválida']);
}
