<?php
/**
 * API Proxy para pagamentos e licenças do AI SEO PRO
 * 
 * HOSPEDE ESTE ARQUIVO NO MESMO DIRETÓRIO DO license-admin.php
 * Exemplo: https://seu-dominio.com/api/payment-proxy.php
 */

// ================================================================
// CONFIGURAÇÕES - EDITE AQUI
// ================================================================

// Sua chave do Asaas (MANTENHA SEGURA AQUI)
define('ASAAS_API_KEY', '$aact_COLOQUE_SUA_CHAVE_AQUI');

// Modo sandbox (true para testes, false para produção)
define('ASAAS_SANDBOX', false);

// Arquivo de licenças (mesmo do license-admin.php)
define('LICENSES_FILE', __DIR__ . '/licenses.json');

// Domínios permitidos (deixe vazio para permitir todos)
$allowed_origins = [];

// Chaves mestra (para você e amigos - funcionam para sempre)
define('MASTER_KEYS', [
    // Adicione suas chaves mestra aqui:
    // 'MASTER-XXXX-XXXX-XXXX-XXXX',
]);

// ================================================================
// NÃO MODIFIQUE ABAIXO DESTA LINHA
// ================================================================

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
 * Carrega licenças do arquivo
 */
function load_licenses() {
    if (!file_exists(LICENSES_FILE)) {
        return [];
    }
    $data = json_decode(file_get_contents(LICENSES_FILE), true);
    return $data ?: [];
}

/**
 * Salva licenças no arquivo
 */
function save_licenses($licenses) {
    file_put_contents(LICENSES_FILE, json_encode($licenses, JSON_PRETTY_PRINT));
}

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
function generate_license_key($prefix = 'AISEO') {
    $segments = [];
    for ($i = 0; $i < 4; $i++) {
        $segments[] = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
    }
    return $prefix . '-' . implode('-', $segments);
}

// Processa ações
switch ($action) {
    
    // ============================================================
    // VALIDAR LICENÇA (chamado pelo plugin)
    // ============================================================
    case 'validate_license':
        $license_key = trim($input['license_key'] ?? '');
        $site_url = $input['site_url'] ?? '';
        
        if (!$license_key) {
            echo json_encode(['success' => false, 'error' => 'Chave não informada']);
            exit;
        }
        
        // Verifica se é chave mestra
        if (in_array($license_key, MASTER_KEYS)) {
            echo json_encode([
                'success' => true,
                'valid' => true,
                'type' => 'master',
                'expires' => null,
                'plan' => 'Vitalício (Master)',
            ]);
            exit;
        }
        
        // Busca no arquivo de licenças
        $licenses = load_licenses();
        
        foreach ($licenses as $lic) {
            if ($lic['license_key'] === $license_key && $lic['status'] === 'active') {
                // Verifica expiração
                if ($lic['expires_at'] !== null && strtotime($lic['expires_at']) < time()) {
                    echo json_encode([
                        'success' => true,
                        'valid' => false,
                        'error' => 'Licença expirada',
                        'expired_at' => $lic['expires_at'],
                    ]);
                    exit;
                }
                
                // Licença válida
                echo json_encode([
                    'success' => true,
                    'valid' => true,
                    'type' => $lic['type'],
                    'plan' => $lic['plan'] ?? 'PRO',
                    'expires' => $lic['expires_at'],
                    'customer_name' => $lic['customer_name'] ?? '',
                ]);
                exit;
            }
        }
        
        // Licença não encontrada
        echo json_encode([
            'success' => true,
            'valid' => false,
            'error' => 'Licença não encontrada ou inativa',
        ]);
        break;
    
    // ============================================================
    // CRIAR PAGAMENTO
    // ============================================================
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
        
        // Planos (pagamento único)
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
        $license_key = generate_license_key('AISEO');
        
        // Cria cobrança (pagamento único)
        $payment_data = [
            'customer' => $customer['id'],
            'billingType' => $payment_method,
            'value' => $plan['price'],
            'dueDate' => date('Y-m-d', strtotime('+3 days')),
            'description' => 'AI SEO PRO - ' . $plan['name'],
            'externalReference' => json_encode([
                'license_key' => $license_key,
                'plan_id' => $plan_id,
                'plan_name' => $plan['name'],
                'days' => $plan['days'],
                'email' => $email,
                'name' => $name,
            ]),
        ];
        
        $payment = asaas_request('payments', 'POST', $payment_data);
        
        if (!$payment['success']) {
            echo json_encode(['success' => false, 'error' => $payment['data']['errors'][0]['description'] ?? 'Erro ao criar cobrança']);
            exit;
        }
        
        // Salva licença como pendente
        $licenses = load_licenses();
        $licenses[] = [
            'license_key' => $license_key,
            'customer_name' => $name,
            'customer_email' => $email,
            'plan' => $plan['name'],
            'plan_id' => $plan_id,
            'type' => 'payment',
            'status' => 'pending',
            'payment_id' => $payment['data']['id'],
            'created_at' => date('Y-m-d H:i:s'),
            'activated_at' => null,
            'expires_at' => null,
            'days' => $plan['days'],
        ];
        save_licenses($licenses);
        
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
    
    // ============================================================
    // VERIFICAR STATUS DO PAGAMENTO
    // ============================================================
    case 'check_payment':
        $payment_id = $input['payment_id'] ?? '';
        
        if (!$payment_id) {
            echo json_encode(['success' => false, 'error' => 'ID não informado']);
            exit;
        }
        
        $result = asaas_request('payments/' . $payment_id);
        
        if ($result['success']) {
            $is_paid = in_array($result['data']['status'], ['CONFIRMED', 'RECEIVED']);
            
            // Se pago, ativa a licença
            if ($is_paid) {
                $licenses = load_licenses();
                foreach ($licenses as &$lic) {
                    if (isset($lic['payment_id']) && $lic['payment_id'] === $payment_id && $lic['status'] === 'pending') {
                        $lic['status'] = 'active';
                        $lic['activated_at'] = date('Y-m-d H:i:s');
                        if ($lic['days'] > 0) {
                            $lic['expires_at'] = date('Y-m-d H:i:s', strtotime('+' . $lic['days'] . ' days'));
                        } else {
                            $lic['expires_at'] = null; // Vitalício
                        }
                        break;
                    }
                }
                save_licenses($licenses);
            }
            
            echo json_encode([
                'success' => true,
                'status' => $result['data']['status'],
                'is_paid' => $is_paid,
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erro ao verificar']);
        }
        break;
    
    // ============================================================
    // WEBHOOK DO ASAAS
    // ============================================================
    case 'webhook':
        $event = $input['event'] ?? '';
        $payment = $input['payment'] ?? [];
        
        // Log do webhook
        file_put_contents(__DIR__ . '/webhook.log', date('Y-m-d H:i:s') . ' - ' . json_encode($input) . "\n", FILE_APPEND);
        
        if ($event === 'PAYMENT_CONFIRMED' || $event === 'PAYMENT_RECEIVED') {
            $payment_id = $payment['id'] ?? '';
            $external_ref = json_decode($payment['externalReference'] ?? '{}', true);
            
            if ($payment_id) {
                $licenses = load_licenses();
                foreach ($licenses as &$lic) {
                    if (isset($lic['payment_id']) && $lic['payment_id'] === $payment_id && $lic['status'] === 'pending') {
                        $lic['status'] = 'active';
                        $lic['activated_at'] = date('Y-m-d H:i:s');
                        if ($lic['days'] > 0) {
                            $lic['expires_at'] = date('Y-m-d H:i:s', strtotime('+' . $lic['days'] . ' days'));
                        } else {
                            $lic['expires_at'] = null;
                        }
                        break;
                    }
                }
                save_licenses($licenses);
            }
        }
        
        echo json_encode(['success' => true]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Ação inválida']);
}
