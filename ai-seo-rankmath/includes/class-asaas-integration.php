<?php
/**
 * Integração com Asaas para Pagamentos
 * 
 * @package AI_SEO_RankMath
 * @since 2.1.0
 */

if (!defined('ABSPATH')) exit;

class AI_SEO_RM_Asaas_Integration {
    
    /** @var string URL da API (sandbox ou produção) */
    private $api_url;
    
    /** @var string API Key do Asaas */
    private $api_key;
    
    /** @var bool Modo sandbox */
    private $sandbox = false;
    
    /** @var AI_SEO_RM_Asaas_Integration Instância singleton */
    private static $instance = null;
    
    /** @var array Planos disponíveis */
    private $plans = [];
    
    /**
     * Retorna a instância singleton
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Construtor
     */
    private function __construct() {
        // Configurações
        $this->sandbox = get_option('ai_seo_rm_asaas_sandbox', true);
        $this->api_key = $this->get_api_key();
        $this->api_url = $this->sandbox 
            ? 'https://sandbox.asaas.com/api/v3/'
            : 'https://api.asaas.com/v3/';
        
        // Planos disponíveis
        $this->plans = [
            'monthly' => [
                'id' => 'monthly',
                'name' => 'Mensal',
                'price' => 29.90,
                'cycle' => 'MONTHLY',
                'description' => 'Licença mensal do AI SEO Assistant',
                'features' => [
                    'Uso ilimitado',
                    'Atualizações automáticas',
                    'Suporte por email',
                ]
            ],
            'yearly' => [
                'id' => 'yearly',
                'name' => 'Anual',
                'price' => 297.00,
                'cycle' => 'YEARLY',
                'description' => 'Licença anual do AI SEO Assistant (2 meses grátis)',
                'features' => [
                    'Uso ilimitado',
                    'Atualizações automáticas',
                    'Suporte prioritário',
                    '2 meses grátis',
                ]
            ],
            'lifetime' => [
                'id' => 'lifetime',
                'name' => 'Vitalício',
                'price' => 497.00,
                'cycle' => null,
                'description' => 'Licença vitalícia do AI SEO Assistant',
                'features' => [
                    'Uso ilimitado para sempre',
                    'Atualizações vitalícias',
                    'Suporte VIP',
                    'Pagamento único',
                ]
            ],
        ];
        
        // Hooks
        add_action('wp_ajax_ai_seo_rm_create_payment', [$this, 'ajax_create_payment']);
        add_action('wp_ajax_ai_seo_rm_check_payment', [$this, 'ajax_check_payment']);
        add_action('rest_api_init', [$this, 'register_webhook_endpoint']);
    }
    
    /**
     * Retorna a API Key do Asaas
     */
    private function get_api_key() {
        if (defined('AI_SEO_RM_ASAAS_API_KEY')) {
            return AI_SEO_RM_ASAAS_API_KEY;
        }
        return get_option('ai_seo_rm_asaas_api_key', '');
    }
    
    /**
     * Verifica se a integração está configurada
     */
    public function is_configured() {
        return !empty($this->api_key);
    }
    
    /**
     * Retorna os planos disponíveis
     */
    public function get_plans() {
        return apply_filters('ai_seo_rm_payment_plans', $this->plans);
    }
    
    /**
     * Faz requisição à API do Asaas
     */
    private function api_request($endpoint, $method = 'GET', $data = null) {
        $args = [
            'method' => $method,
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'access_token' => $this->api_key,
            ],
        ];
        
        if ($data && in_array($method, ['POST', 'PUT'])) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($this->api_url . $endpoint, $args);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message()
            ];
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $code = wp_remote_retrieve_response_code($response);
        
        if ($code >= 200 && $code < 300) {
            return [
                'success' => true,
                'data' => $body
            ];
        }
        
        return [
            'success' => false,
            'error' => $body['errors'][0]['description'] ?? 'Erro desconhecido',
            'code' => $code
        ];
    }
    
    /**
     * Cria ou encontra cliente no Asaas
     */
    public function find_or_create_customer($email, $name, $cpf_cnpj = null) {
        // Busca cliente existente por email
        $search = $this->api_request('customers?email=' . urlencode($email));
        
        if ($search['success'] && !empty($search['data']['data'])) {
            return $search['data']['data'][0];
        }
        
        // Cria novo cliente
        $customer_data = [
            'name' => $name,
            'email' => $email,
            'notificationDisabled' => false,
        ];
        
        if ($cpf_cnpj) {
            $customer_data['cpfCnpj'] = preg_replace('/\D/', '', $cpf_cnpj);
        }
        
        $result = $this->api_request('customers', 'POST', $customer_data);
        
        if ($result['success']) {
            return $result['data'];
        }
        
        return null;
    }
    
    /**
     * Cria uma cobrança (pagamento único)
     */
    public function create_payment($customer_id, $plan_id, $billing_type = 'UNDEFINED') {
        $plan = $this->plans[$plan_id] ?? null;
        
        if (!$plan) {
            return ['success' => false, 'error' => 'Plano inválido'];
        }
        
        $payment_data = [
            'customer' => $customer_id,
            'billingType' => $billing_type, // BOLETO, CREDIT_CARD, PIX, UNDEFINED
            'value' => $plan['price'],
            'dueDate' => date('Y-m-d', strtotime('+3 days')),
            'description' => $plan['description'],
            'externalReference' => $this->generate_license_key(),
        ];
        
        $result = $this->api_request('payments', 'POST', $payment_data);
        
        if ($result['success']) {
            // Salva referência do pagamento
            $this->save_pending_payment($result['data']);
        }
        
        return $result;
    }
    
    /**
     * Cria uma assinatura recorrente
     */
    public function create_subscription($customer_id, $plan_id, $billing_type = 'UNDEFINED') {
        $plan = $this->plans[$plan_id] ?? null;
        
        if (!$plan || !$plan['cycle']) {
            return ['success' => false, 'error' => 'Plano inválido para assinatura'];
        }
        
        $subscription_data = [
            'customer' => $customer_id,
            'billingType' => $billing_type,
            'value' => $plan['price'],
            'nextDueDate' => date('Y-m-d'),
            'cycle' => $plan['cycle'], // MONTHLY, YEARLY
            'description' => $plan['description'],
            'externalReference' => $this->generate_license_key(),
        ];
        
        $result = $this->api_request('subscriptions', 'POST', $subscription_data);
        
        if ($result['success']) {
            $this->save_pending_payment($result['data']);
        }
        
        return $result;
    }
    
    /**
     * Gera uma chave de licença única
     */
    private function generate_license_key() {
        $segments = [];
        for ($i = 0; $i < 4; $i++) {
            $segments[] = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
        }
        return 'AISEO-' . implode('-', $segments);
    }
    
    /**
     * Salva pagamento pendente
     */
    private function save_pending_payment($payment_data) {
        $pending = get_option('ai_seo_rm_pending_payments', []);
        $pending[$payment_data['id']] = [
            'id' => $payment_data['id'],
            'license_key' => $payment_data['externalReference'],
            'status' => $payment_data['status'],
            'value' => $payment_data['value'],
            'created_at' => current_time('mysql'),
            'customer' => $payment_data['customer'],
        ];
        update_option('ai_seo_rm_pending_payments', $pending);
    }
    
    /**
     * Processa confirmação de pagamento (webhook)
     */
    public function process_payment_confirmation($payment_id, $status) {
        $pending = get_option('ai_seo_rm_pending_payments', []);
        
        if (!isset($pending[$payment_id])) {
            return false;
        }
        
        $payment = $pending[$payment_id];
        
        if (in_array($status, ['CONFIRMED', 'RECEIVED'])) {
            // Ativa a licença
            $license_manager = ai_seo_rm_license();
            $license_manager->activate_license($payment['license_key']);
            
            // Salva dados do pagamento na licença
            update_option('ai_seo_rm_license_payment', [
                'payment_id' => $payment_id,
                'license_key' => $payment['license_key'],
                'value' => $payment['value'],
                'activated_at' => current_time('mysql'),
            ]);
            
            // Remove dos pendentes
            unset($pending[$payment_id]);
            update_option('ai_seo_rm_pending_payments', $pending);
            
            // Dispara ação para integrações
            do_action('ai_seo_rm_payment_confirmed', $payment);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Registra endpoint do webhook
     */
    public function register_webhook_endpoint() {
        register_rest_route('ai-seo-rm/v1', '/webhook/asaas', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_webhook'],
            'permission_callback' => '__return_true',
        ]);
    }
    
    /**
     * Processa webhook do Asaas
     */
    public function handle_webhook($request) {
        $body = $request->get_json_params();
        
        // Log do webhook (para debug)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AI SEO RM Webhook: ' . json_encode($body));
        }
        
        $event = $body['event'] ?? '';
        $payment = $body['payment'] ?? [];
        
        switch ($event) {
            case 'PAYMENT_CONFIRMED':
            case 'PAYMENT_RECEIVED':
                $this->process_payment_confirmation($payment['id'], $payment['status']);
                break;
                
            case 'PAYMENT_OVERDUE':
            case 'PAYMENT_DELETED':
            case 'PAYMENT_REFUNDED':
                // Desativa licença se necessário
                $pending = get_option('ai_seo_rm_pending_payments', []);
                if (isset($pending[$payment['id']])) {
                    // Licença ainda não foi ativada, só remove
                    unset($pending[$payment['id']]);
                    update_option('ai_seo_rm_pending_payments', $pending);
                }
                break;
        }
        
        return new WP_REST_Response(['success' => true], 200);
    }
    
    /**
     * AJAX: Criar pagamento
     */
    public function ajax_create_payment() {
        check_ajax_referer('ai_seo_rm_ajax', 'nonce');
        
        if (!$this->is_configured()) {
            wp_send_json_error(['message' => 'Integração com Asaas não configurada.']);
        }
        
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $cpf_cnpj = sanitize_text_field($_POST['cpf_cnpj'] ?? '');
        $plan_id = sanitize_text_field($_POST['plan'] ?? 'monthly');
        $billing_type = sanitize_text_field($_POST['billing_type'] ?? 'UNDEFINED');
        
        if (!$name || !$email) {
            wp_send_json_error(['message' => 'Nome e email são obrigatórios.']);
        }
        
        // Cria/encontra cliente
        $customer = $this->find_or_create_customer($email, $name, $cpf_cnpj);
        
        if (!$customer) {
            wp_send_json_error(['message' => 'Erro ao criar cliente no Asaas.']);
        }
        
        // Cria pagamento ou assinatura
        $plan = $this->plans[$plan_id] ?? null;
        
        if (!$plan) {
            wp_send_json_error(['message' => 'Plano inválido.']);
        }
        
        if ($plan['cycle']) {
            // Assinatura recorrente
            $result = $this->create_subscription($customer['id'], $plan_id, $billing_type);
        } else {
            // Pagamento único (vitalício)
            $result = $this->create_payment($customer['id'], $plan_id, $billing_type);
        }
        
        if ($result['success']) {
            $payment_data = $result['data'];
            
            $response = [
                'message' => 'Cobrança criada com sucesso!',
                'payment_id' => $payment_data['id'],
                'status' => $payment_data['status'],
                'value' => $payment_data['value'],
                'license_key' => $payment_data['externalReference'],
            ];
            
            // Adiciona links de pagamento
            if (isset($payment_data['invoiceUrl'])) {
                $response['invoice_url'] = $payment_data['invoiceUrl'];
            }
            if (isset($payment_data['bankSlipUrl'])) {
                $response['boleto_url'] = $payment_data['bankSlipUrl'];
            }
            if (isset($payment_data['invoiceNumber'])) {
                $response['invoice_number'] = $payment_data['invoiceNumber'];
            }
            
            // Para PIX
            if ($billing_type === 'PIX') {
                $pix_info = $this->get_pix_qrcode($payment_data['id']);
                if ($pix_info) {
                    $response['pix'] = $pix_info;
                }
            }
            
            wp_send_json_success($response);
        } else {
            wp_send_json_error([
                'message' => 'Erro ao criar cobrança: ' . ($result['error'] ?? 'Erro desconhecido')
            ]);
        }
    }
    
    /**
     * Obtém QR Code PIX
     */
    public function get_pix_qrcode($payment_id) {
        $result = $this->api_request('payments/' . $payment_id . '/pixQrCode');
        
        if ($result['success']) {
            return [
                'payload' => $result['data']['payload'] ?? '',
                'qrcode_image' => $result['data']['encodedImage'] ?? '',
                'expiration' => $result['data']['expirationDate'] ?? '',
            ];
        }
        
        return null;
    }
    
    /**
     * AJAX: Verificar status do pagamento
     */
    public function ajax_check_payment() {
        check_ajax_referer('ai_seo_rm_ajax', 'nonce');
        
        $payment_id = sanitize_text_field($_POST['payment_id'] ?? '');
        
        if (!$payment_id) {
            wp_send_json_error(['message' => 'ID do pagamento não informado.']);
        }
        
        $result = $this->api_request('payments/' . $payment_id);
        
        if ($result['success']) {
            $payment = $result['data'];
            $status = $payment['status'];
            
            // Se confirmado, ativa a licença
            if (in_array($status, ['CONFIRMED', 'RECEIVED'])) {
                $this->process_payment_confirmation($payment_id, $status);
            }
            
            wp_send_json_success([
                'status' => $status,
                'status_label' => $this->get_status_label($status),
                'is_paid' => in_array($status, ['CONFIRMED', 'RECEIVED']),
            ]);
        } else {
            wp_send_json_error(['message' => 'Erro ao verificar pagamento.']);
        }
    }
    
    /**
     * Retorna label do status
     */
    private function get_status_label($status) {
        $labels = [
            'PENDING' => 'Aguardando pagamento',
            'RECEIVED' => 'Pago',
            'CONFIRMED' => 'Confirmado',
            'OVERDUE' => 'Vencido',
            'REFUNDED' => 'Estornado',
            'RECEIVED_IN_CASH' => 'Recebido em dinheiro',
            'REFUND_REQUESTED' => 'Estorno solicitado',
            'CHARGEBACK_REQUESTED' => 'Chargeback solicitado',
            'CHARGEBACK_DISPUTE' => 'Em disputa',
            'AWAITING_CHARGEBACK_REVERSAL' => 'Aguardando reversão',
            'DUNNING_REQUESTED' => 'Em recuperação',
            'DUNNING_RECEIVED' => 'Recuperado',
            'AWAITING_RISK_ANALYSIS' => 'Em análise',
        ];
        
        return $labels[$status] ?? $status;
    }
    
    /**
     * Retorna URL do webhook para configurar no Asaas
     */
    public function get_webhook_url() {
        return rest_url('ai-seo-rm/v1/webhook/asaas');
    }
}

// Função helper global
function ai_seo_rm_asaas() {
    return AI_SEO_RM_Asaas_Integration::instance();
}
