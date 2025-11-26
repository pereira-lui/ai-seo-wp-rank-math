<?php
/**
 * License Manager for AI SEO Assistant
 * Gerencia ativação, validação e verificação de licenças
 * 
 * @package AI_SEO_RankMath
 * @since 2.0.0
 */

if (!defined('ABSPATH')) exit;

class AI_SEO_RM_License_Manager {
    
    /** @var string URL do servidor de licenças */
    private $api_url = '';
    
    /** @var string Slug do produto */
    private $product_slug = 'ai-seo-rankmath';
    
    /** @var string Option name para a chave de licença */
    private $license_key_option = 'ai_seo_rm_license_key';
    
    /** @var string Option name para status da licença */
    private $license_status_option = 'ai_seo_rm_license_status';
    
    /** @var string Option name para dados da licença */
    private $license_data_option = 'ai_seo_rm_license_data';
    
    /** @var AI_SEO_RM_License_Manager Instância singleton */
    private static $instance = null;
    
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
        // Definir URL do servidor de licenças (altere para seu servidor)
        $this->api_url = defined('AI_SEO_RM_LICENSE_SERVER') 
            ? AI_SEO_RM_LICENSE_SERVER 
            : 'https://seu-servidor.com/wp-json/lmfwc/v2/';
        
        // Hooks
        add_action('admin_init', [$this, 'schedule_license_check']);
        add_action('ai_seo_rm_daily_license_check', [$this, 'daily_license_check']);
    }
    
    /**
     * Agenda verificação diária de licença
     */
    public function schedule_license_check() {
        if (!wp_next_scheduled('ai_seo_rm_daily_license_check')) {
            wp_schedule_event(time(), 'daily', 'ai_seo_rm_daily_license_check');
        }
    }
    
    /**
     * Verificação diária de licença
     */
    public function daily_license_check() {
        $license_key = $this->get_license_key();
        if ($license_key) {
            $this->validate_license($license_key);
        }
    }
    
    /**
     * Retorna a chave de licença salva
     */
    public function get_license_key() {
        return get_option($this->license_key_option, '');
    }
    
    /**
     * Retorna o status da licença
     */
    public function get_license_status() {
        return get_option($this->license_status_option, 'inactive');
    }
    
    /**
     * Retorna os dados completos da licença
     */
    public function get_license_data() {
        return get_option($this->license_data_option, []);
    }
    
    /**
     * Verifica se a licença está ativa
     */
    public function is_license_active() {
        $status = $this->get_license_status();
        return in_array($status, ['active', 'valid'], true);
    }
    
    /**
     * Verifica se está em modo de teste/trial
     */
    public function is_trial_mode() {
        // Modo trial: 7 dias sem licença
        $first_activation = get_option('ai_seo_rm_first_activation', 0);
        
        if (!$first_activation) {
            update_option('ai_seo_rm_first_activation', time());
            $first_activation = time();
        }
        
        $trial_days = 7;
        $trial_end = $first_activation + ($trial_days * DAY_IN_SECONDS);
        
        return time() < $trial_end;
    }
    
    /**
     * Retorna dias restantes do trial
     */
    public function get_trial_days_remaining() {
        $first_activation = get_option('ai_seo_rm_first_activation', time());
        $trial_days = 7;
        $trial_end = $first_activation + ($trial_days * DAY_IN_SECONDS);
        $remaining = $trial_end - time();
        
        return max(0, ceil($remaining / DAY_IN_SECONDS));
    }
    
    /**
     * Verifica se o plugin pode ser usado (licença ativa ou trial)
     */
    public function can_use_plugin() {
        return $this->is_license_active() || $this->is_trial_mode();
    }
    
    /**
     * Ativa uma licença
     * 
     * @param string $license_key Chave de licença
     * @return array Resultado da ativação
     */
    public function activate_license($license_key) {
        $license_key = sanitize_text_field(trim($license_key));
        
        if (empty($license_key)) {
            return [
                'success' => false,
                'message' => __('Por favor, insira uma chave de licença válida.', 'ai-seo-rankmath')
            ];
        }
        
        // Se não há servidor configurado, ativa localmente (para desenvolvimento)
        if (empty($this->api_url) || strpos($this->api_url, 'seu-servidor.com') !== false) {
            return $this->activate_license_local($license_key);
        }
        
        // Chamada para o servidor de licenças
        $response = wp_remote_post($this->api_url . 'licenses/activate/' . $license_key, [
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'product_slug' => $this->product_slug,
                'site_url' => home_url(),
                'site_name' => get_bloginfo('name'),
            ])
        ]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => __('Erro de conexão: ', 'ai-seo-rankmath') . $response->get_error_message()
            ];
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $code = wp_remote_retrieve_response_code($response);
        
        if ($code === 200 && !empty($body['success'])) {
            // Salva dados da licença
            update_option($this->license_key_option, $license_key);
            update_option($this->license_status_option, 'active');
            update_option($this->license_data_option, $body['data'] ?? []);
            
            return [
                'success' => true,
                'message' => __('Licença ativada com sucesso!', 'ai-seo-rankmath'),
                'data' => $body['data'] ?? []
            ];
        }
        
        return [
            'success' => false,
            'message' => $body['message'] ?? __('Falha ao ativar licença.', 'ai-seo-rankmath')
        ];
    }
    
    /**
     * Ativação local (sem servidor) - para desenvolvimento/testes
     */
    private function activate_license_local($license_key) {
        // Valida formato básico da chave (pode personalizar)
        if (strlen($license_key) < 10) {
            return [
                'success' => false,
                'message' => __('Chave de licença inválida.', 'ai-seo-rankmath')
            ];
        }
        
        // Para desenvolvimento: aceita qualquer chave com prefixo válido
        $valid_prefixes = ['AISEO-', 'PRO-', 'DEV-'];
        $is_valid = false;
        
        foreach ($valid_prefixes as $prefix) {
            if (strpos($license_key, $prefix) === 0) {
                $is_valid = true;
                break;
            }
        }
        
        if (!$is_valid) {
            return [
                'success' => false,
                'message' => __('Formato de chave inválido. Use: AISEO-XXXX-XXXX-XXXX', 'ai-seo-rankmath')
            ];
        }
        
        // Ativa a licença localmente
        update_option($this->license_key_option, $license_key);
        update_option($this->license_status_option, 'active');
        update_option($this->license_data_option, [
            'license_key' => $license_key,
            'status' => 'active',
            'expires' => date('Y-m-d', strtotime('+1 year')),
            'activations' => 1,
            'activations_limit' => 3,
            'product' => 'AI SEO Assistant Pro'
        ]);
        
        return [
            'success' => true,
            'message' => __('Licença ativada com sucesso!', 'ai-seo-rankmath')
        ];
    }
    
    /**
     * Desativa a licença
     */
    public function deactivate_license() {
        $license_key = $this->get_license_key();
        
        if (empty($license_key)) {
            return [
                'success' => false,
                'message' => __('Nenhuma licença para desativar.', 'ai-seo-rankmath')
            ];
        }
        
        // Limpa dados locais
        delete_option($this->license_key_option);
        update_option($this->license_status_option, 'inactive');
        delete_option($this->license_data_option);
        
        return [
            'success' => true,
            'message' => __('Licença desativada com sucesso.', 'ai-seo-rankmath')
        ];
    }
    
    /**
     * Valida a licença no servidor
     */
    public function validate_license($license_key = null) {
        $license_key = $license_key ?? $this->get_license_key();
        
        if (empty($license_key)) {
            update_option($this->license_status_option, 'inactive');
            return false;
        }
        
        // Validação local se não há servidor
        if (empty($this->api_url) || strpos($this->api_url, 'seu-servidor.com') !== false) {
            return $this->get_license_status() === 'active';
        }
        
        $response = wp_remote_get($this->api_url . 'licenses/validate/' . $license_key, [
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ]);
        
        if (is_wp_error($response)) {
            return $this->get_license_status() === 'active'; // Mantém status anterior se falhar
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $code = wp_remote_retrieve_response_code($response);
        
        if ($code === 200 && !empty($body['success'])) {
            update_option($this->license_status_option, 'active');
            if (!empty($body['data'])) {
                update_option($this->license_data_option, $body['data']);
            }
            return true;
        }
        
        update_option($this->license_status_option, 'expired');
        return false;
    }
    
    /**
     * Mascara a chave de licença para exibição
     */
    public function mask_license_key($key = null) {
        $key = $key ?? $this->get_license_key();
        if (empty($key)) return '';
        
        $len = strlen($key);
        if ($len <= 8) return str_repeat('*', $len);
        
        return substr($key, 0, 5) . str_repeat('*', $len - 9) . substr($key, -4);
    }
    
    /**
     * Retorna informações formatadas da licença
     */
    public function get_license_info() {
        $data = $this->get_license_data();
        $status = $this->get_license_status();
        
        return [
            'status' => $status,
            'status_label' => $this->get_status_label($status),
            'key_masked' => $this->mask_license_key(),
            'expires' => $data['expires'] ?? '',
            'activations' => $data['activations'] ?? 0,
            'activations_limit' => $data['activations_limit'] ?? 1,
            'product' => $data['product'] ?? 'AI SEO Assistant',
            'is_active' => $this->is_license_active(),
            'is_trial' => !$this->is_license_active() && $this->is_trial_mode(),
            'trial_days' => $this->get_trial_days_remaining(),
        ];
    }
    
    /**
     * Retorna label do status
     */
    private function get_status_label($status) {
        $labels = [
            'active' => __('Ativa', 'ai-seo-rankmath'),
            'valid' => __('Válida', 'ai-seo-rankmath'),
            'inactive' => __('Inativa', 'ai-seo-rankmath'),
            'expired' => __('Expirada', 'ai-seo-rankmath'),
            'disabled' => __('Desabilitada', 'ai-seo-rankmath'),
            'invalid' => __('Inválida', 'ai-seo-rankmath'),
        ];
        
        return $labels[$status] ?? ucfirst($status);
    }
}

// Função helper global
function ai_seo_rm_license() {
    return AI_SEO_RM_License_Manager::instance();
}
