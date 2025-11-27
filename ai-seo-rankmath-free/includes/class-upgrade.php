<?php
/**
 * Sistema de Upgrade para versão PRO (via API Proxy)
 * 
 * Este arquivo NÃO contém sua chave do Asaas!
 * A chave fica segura no seu servidor (payment-proxy.php)
 * 
 * @package AI_SEO_RankMath_Free
 * @since 2.3.0
 */

if (!defined('ABSPATH')) exit;

class AI_SEO_RM_Upgrade {
    
    /**
     * URL do seu proxy de pagamento
     * Configure isso com a URL onde você hospedou o payment-proxy.php
     */
    private $proxy_url = '';
    
    /** @var array Planos disponíveis */
    private $plans = [];
    
    /** @var AI_SEO_RM_Upgrade Instância singleton */
    private static $instance = null;
    
    /**
     * Retorna instância singleton
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
        // ========================================================
        // CONFIGURE A URL DO SEU PROXY AQUI
        // Exemplo: https://seu-dominio.com/api/payment-proxy.php
        // ========================================================
        $this->proxy_url = 'https://ai-seo-wp-rank-math.it2.solutions/api/payment-proxy.php';
        
        // Planos disponíveis (pagamento único)
        $this->plans = [
            'monthly' => [
                'id' => 'monthly',
                'name' => '30 Dias',
                'price' => 29.90,
                'days' => 30,
                'description' => 'Licença de 30 dias',
            ],
            'yearly' => [
                'id' => 'yearly',
                'name' => '1 Ano',
                'price' => 297.00,
                'days' => 365,
                'popular' => true,
                'description' => 'Licença de 1 ano (economia de 2 meses)',
            ],
            'lifetime' => [
                'id' => 'lifetime',
                'name' => 'Vitalício',
                'price' => 497.00,
                'days' => 0,
                'description' => 'Licença vitalícia - pague uma vez, use para sempre',
            ],
        ];
        
        // Hooks
        add_action('admin_menu', [$this, 'add_upgrade_page']);
        add_action('wp_ajax_ai_seo_rm_create_order', [$this, 'ajax_create_order']);
        add_action('wp_ajax_ai_seo_rm_check_order', [$this, 'ajax_check_order']);
    }
    
    /**
     * Verifica se o proxy está configurado
     */
    private function is_configured() {
        return !empty($this->proxy_url) && strpos($this->proxy_url, 'SEU-DOMINIO') === false;
    }
    
    /**
     * Adiciona página de upgrade
     */
    public function add_upgrade_page() {
        add_submenu_page(
            'options-general.php',
            'Upgrade para PRO - AI SEO',
            '',
            'manage_options',
            'ai-seo-upgrade',
            [$this, 'render_upgrade_page']
        );
    }
    
    /**
     * Renderiza página de upgrade/compra
     */
    public function render_upgrade_page() {
        $is_configured = $this->is_configured();
        
        ?>
        <div class="wrap ai-seo-upgrade-page">
            <style>
                /* Esconde notificações do WordPress na página de upgrade */
                .ai-seo-upgrade-page .notice,
                .ai-seo-upgrade-page .updated,
                .ai-seo-upgrade-page .update-nag,
                .ai-seo-upgrade-page .error,
                .ai-seo-upgrade-page .notice-warning,
                .ai-seo-upgrade-page .notice-info,
                .ai-seo-upgrade-page .notice-error,
                .ai-seo-upgrade-page .notice-success,
                .wrap.ai-seo-upgrade-page ~ .notice,
                .wrap.ai-seo-upgrade-page ~ .updated,
                #wpbody-content > .notice,
                #wpbody-content > .updated,
                #wpbody-content > .update-nag,
                #wpbody-content > .error {
                    display: none !important;
                }
                .ai-seo-upgrade-wrap { max-width: 900px; margin: 20px auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
                .ai-seo-header { text-align: center; padding: 40px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border-radius: 12px; margin-bottom: 30px; }
                .ai-seo-header h1 { margin: 0 0 10px 0; font-size: 32px; color: #fff; }
                .ai-seo-header p { margin: 0; font-size: 18px; opacity: 0.9; }
                .ai-seo-plans { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
                @media (max-width: 768px) { .ai-seo-plans { grid-template-columns: 1fr; } }
                .ai-seo-plan { background: #fff; border: 2px solid #e0e0e0; border-radius: 12px; padding: 25px; text-align: center; position: relative; transition: all 0.3s ease; }
                .ai-seo-plan:hover { border-color: #667eea; box-shadow: 0 5px 20px rgba(102, 126, 234, 0.2); }
                .ai-seo-plan.popular { border-color: #667eea; transform: scale(1.05); }
                .ai-seo-plan.popular .popular-badge { position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: #667eea; color: #fff; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; }
                .ai-seo-plan h3 { margin: 0 0 10px 0; font-size: 20px; color: #333; }
                .ai-seo-plan .price { font-size: 36px; font-weight: bold; color: #667eea; margin: 15px 0; }
                .ai-seo-plan .description { color: #666; margin-bottom: 20px; font-size: 14px; }
                .ai-seo-plan button { width: 100%; padding: 12px 20px; font-size: 16px; font-weight: bold; border: none; border-radius: 8px; cursor: pointer; transition: all 0.3s ease; }
                .ai-seo-plan button.primary { background: #667eea; color: #fff; }
                .ai-seo-plan button.primary:hover { background: #5a6fd6; }
                .ai-seo-plan button.secondary { background: #f0f0f0; color: #333; }
                .ai-seo-plan button.secondary:hover { background: #e0e0e0; }
                .ai-seo-plan button:disabled { background: #ccc; cursor: not-allowed; }
                .ai-seo-modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 100000; align-items: center; justify-content: center; }
                .ai-seo-modal.active { display: flex; }
                .ai-seo-modal-content { background: #fff; border-radius: 12px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; }
                .ai-seo-modal-header { padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
                .ai-seo-modal-header h2 { margin: 0; font-size: 20px; }
                .ai-seo-modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #666; }
                .ai-seo-modal-body { padding: 20px; }
                .ai-seo-form-group { margin-bottom: 15px; }
                .ai-seo-form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
                .ai-seo-form-group input { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
                .ai-seo-form-group input:focus { border-color: #667eea; outline: none; }
                .ai-seo-payment-methods { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px; }
                .ai-seo-payment-method { border: 2px solid #e0e0e0; border-radius: 8px; padding: 15px 10px; text-align: center; cursor: pointer; transition: all 0.2s ease; }
                .ai-seo-payment-method:hover { border-color: #667eea; }
                .ai-seo-payment-method.selected { border-color: #667eea; background: #f0f4ff; }
                .ai-seo-payment-method .icon { font-size: 24px; margin-bottom: 5px; }
                .ai-seo-payment-method .label { font-size: 12px; color: #666; }
                .ai-seo-btn-checkout { width: 100%; padding: 15px; font-size: 16px; font-weight: bold; background: #667eea; color: #fff; border: none; border-radius: 8px; cursor: pointer; }
                .ai-seo-btn-checkout:hover { background: #5a6fd6; }
                .ai-seo-btn-checkout:disabled { background: #ccc; cursor: not-allowed; }
                .ai-seo-pix-qr { max-width: 200px; margin: 15px auto; border: 1px solid #ddd; border-radius: 8px; padding: 10px; background: #fff; }
                .ai-seo-pix-code { background: #f5f5f5; padding: 10px; border-radius: 6px; word-break: break-all; font-size: 12px; margin: 10px 0; }
                .ai-seo-copy-btn { background: #667eea; color: #fff; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
                .ai-seo-features { background: #f9f9f9; border-radius: 12px; padding: 25px; margin-bottom: 30px; }
                .ai-seo-features h2 { margin: 0 0 20px 0; text-align: center; }
                .ai-seo-features-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
                .ai-seo-feature { display: flex; align-items: center; gap: 10px; }
                .ai-seo-feature .check { color: #22c55e; font-size: 20px; }
                .ai-seo-guarantee { text-align: center; padding: 20px; background: #fff3cd; border-radius: 8px; margin-bottom: 20px; }
                .ai-seo-loading { display: inline-block; width: 20px; height: 20px; border: 2px solid #fff; border-radius: 50%; border-top-color: transparent; animation: spin 1s linear infinite; }
                @keyframes spin { to { transform: rotate(360deg); } }
            </style>
            
            <div class="ai-seo-upgrade-wrap">
                <div class="ai-seo-header">
                    <h1>🚀 Upgrade para AI SEO PRO</h1>
                    <p>Preencha automaticamente o Rank Math com Inteligência Artificial</p>
                </div>
                
                <?php if (!$is_configured): ?>
                <div style="background:#fff3cd; border:1px solid #ffc107; padding:20px; border-radius:8px; margin-bottom:20px;">
                    <strong>⚠️ Sistema de pagamento não configurado.</strong><br>
                    Entre em contato com o desenvolvedor para adquirir a versão PRO.
                </div>
                <?php endif; ?>
                
                <div class="ai-seo-plans">
                    <?php foreach ($this->plans as $plan): ?>
                    <div class="ai-seo-plan <?php echo isset($plan['popular']) ? 'popular' : ''; ?>">
                        <?php if (isset($plan['popular'])): ?>
                        <span class="popular-badge">MAIS POPULAR</span>
                        <?php endif; ?>
                        <h3><?php echo esc_html($plan['name']); ?></h3>
                        <div class="price">R$ <?php echo number_format($plan['price'], 2, ',', '.'); ?></div>
                        <p class="description"><?php echo esc_html($plan['description']); ?></p>
                        <button class="<?php echo isset($plan['popular']) ? 'primary' : 'secondary'; ?> ai-seo-buy-btn"
                            data-plan="<?php echo esc_attr($plan['id']); ?>"
                            data-price="<?php echo esc_attr($plan['price']); ?>"
                            data-name="<?php echo esc_attr($plan['name']); ?>"
                            <?php echo !$is_configured ? 'disabled' : ''; ?>>
                            Comprar Agora
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="ai-seo-features">
                    <h2>✨ O que você recebe na versão PRO</h2>
                    <div class="ai-seo-features-grid">
                        <div class="ai-seo-feature"><span class="check">✅</span><span>Geração automática de Title SEO</span></div>
                        <div class="ai-seo-feature"><span class="check">✅</span><span>Geração automática de Description</span></div>
                        <div class="ai-seo-feature"><span class="check">✅</span><span>Sugestão de Focus Keyword</span></div>
                        <div class="ai-seo-feature"><span class="check">✅</span><span>Auto-aplicar ao publicar posts</span></div>
                        <div class="ai-seo-feature"><span class="check">✅</span><span>Open Graph (Facebook/Twitter)</span></div>
                        <div class="ai-seo-feature"><span class="check">✅</span><span>Brief/Contexto personalizado</span></div>
                        <div class="ai-seo-feature"><span class="check">✅</span><span>Atualizações automáticas</span></div>
                        <div class="ai-seo-feature"><span class="check">✅</span><span>Suporte por email</span></div>
                    </div>
                </div>
                
                <div class="ai-seo-guarantee">🛡️ <strong>Garantia de 7 dias</strong> - Se não ficar satisfeito, devolvemos seu dinheiro.</div>
                <div style="text-align:center; color:#666; font-size:13px;">🔒 Pagamento 100% seguro via PIX, Boleto ou Cartão</div>
            </div>
            
            <div class="ai-seo-modal" id="ai-seo-checkout-modal">
                <div class="ai-seo-modal-content">
                    <div class="ai-seo-modal-header">
                        <h2>🛒 Finalizar Compra</h2>
                        <button class="ai-seo-modal-close">&times;</button>
                    </div>
                    <div class="ai-seo-modal-body">
                        <form id="ai-seo-checkout-form">
                            <input type="hidden" id="checkout-plan" name="plan" value="">
                            <div class="ai-seo-form-group">
                                <label>Plano Selecionado</label>
                                <div id="checkout-plan-info" style="padding:10px; background:#f5f5f5; border-radius:6px;"></div>
                            </div>
                            <div class="ai-seo-form-group">
                                <label for="checkout-name">Nome Completo *</label>
                                <input type="text" id="checkout-name" name="name" required>
                            </div>
                            <div class="ai-seo-form-group">
                                <label for="checkout-email">E-mail *</label>
                                <input type="email" id="checkout-email" name="email" required>
                            </div>
                            <div class="ai-seo-form-group">
                                <label for="checkout-cpf">CPF (opcional)</label>
                                <input type="text" id="checkout-cpf" name="cpf" placeholder="000.000.000-00">
                            </div>
                            <div class="ai-seo-form-group">
                                <label>Forma de Pagamento</label>
                                <div class="ai-seo-payment-methods">
                                    <div class="ai-seo-payment-method selected" data-method="PIX">
                                        <div class="icon">📱</div><div class="label">PIX</div>
                                    </div>
                                    <div class="ai-seo-payment-method" data-method="BOLETO">
                                        <div class="icon">📄</div><div class="label">Boleto</div>
                                    </div>
                                    <div class="ai-seo-payment-method" data-method="CREDIT_CARD">
                                        <div class="icon">💳</div><div class="label">Cartão</div>
                                    </div>
                                </div>
                                <input type="hidden" id="checkout-payment-method" name="payment_method" value="PIX">
                            </div>
                            <button type="submit" class="ai-seo-btn-checkout" id="btn-checkout">💳 Pagar Agora</button>
                        </form>
                        <div class="ai-seo-payment-result" id="payment-result" style="display:none;"></div>
                    </div>
                </div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                var selectedPlan = null, paymentMethod = 'PIX';
                
                $('.ai-seo-buy-btn').on('click', function() {
                    if ($(this).prop('disabled')) return;
                    selectedPlan = { id: $(this).data('plan'), price: $(this).data('price'), name: $(this).data('name') };
                    $('#checkout-plan').val(selectedPlan.id);
                    $('#checkout-plan-info').html('<strong>' + selectedPlan.name + '</strong> - R$ ' + selectedPlan.price.toFixed(2).replace('.', ','));
                    $('#ai-seo-checkout-form').show();
                    $('#payment-result').hide();
                    $('#btn-checkout').prop('disabled', false).html('💳 Pagar Agora');
                    $('#ai-seo-checkout-modal').addClass('active');
                });
                
                $('.ai-seo-modal-close').on('click', function() { $('#ai-seo-checkout-modal').removeClass('active'); });
                $('#ai-seo-checkout-modal').on('click', function(e) { if (e.target === this) $(this).removeClass('active'); });
                
                $('.ai-seo-payment-method').on('click', function() {
                    $('.ai-seo-payment-method').removeClass('selected');
                    $(this).addClass('selected');
                    paymentMethod = $(this).data('method');
                    $('#checkout-payment-method').val(paymentMethod);
                });
                
                $('#ai-seo-checkout-form').on('submit', function(e) {
                    e.preventDefault();
                    var $btn = $('#btn-checkout');
                    $btn.prop('disabled', true).html('<span class="ai-seo-loading"></span> Processando...');
                    
                    $.ajax({
                        url: ajaxurl, type: 'POST',
                        data: {
                            action: 'ai_seo_rm_create_order',
                            nonce: '<?php echo wp_create_nonce('ai_seo_rm_upgrade'); ?>',
                            plan: selectedPlan.id,
                            name: $('#checkout-name').val(),
                            email: $('#checkout-email').val(),
                            cpf: $('#checkout-cpf').val(),
                            payment_method: paymentMethod
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#ai-seo-checkout-form').hide();
                                showPaymentResult(response.data);
                            } else {
                                alert('Erro: ' + (response.data.message || 'Tente novamente'));
                                $btn.prop('disabled', false).html('💳 Pagar Agora');
                            }
                        },
                        error: function() {
                            alert('Erro de conexão. Tente novamente.');
                            $btn.prop('disabled', false).html('💳 Pagar Agora');
                        }
                    });
                });
                
                function showPaymentResult(data) {
                    var html = '<h3 style="color:#22c55e;">✅ Cobrança criada!</h3>';
                    
                    if (data.pix && data.pix.qrcode_image) {
                        html += '<p>Escaneie o QR Code ou copie o código PIX:</p>';
                        html += '<img src="data:image/png;base64,' + data.pix.qrcode_image + '" class="ai-seo-pix-qr">';
                        html += '<div class="ai-seo-pix-code" id="pix-code">' + data.pix.payload + '</div>';
                        html += '<button type="button" class="ai-seo-copy-btn" onclick="copyPixCode()">📋 Copiar Código PIX</button>';
                    } else if (data.boleto_url) {
                        html += '<p>Seu boleto foi gerado:</p>';
                        html += '<a href="' + data.boleto_url + '" target="_blank" class="ai-seo-copy-btn">📄 Ver Boleto</a>';
                    } else if (data.invoice_url) {
                        html += '<p>Complete o pagamento:</p>';
                        html += '<a href="' + data.invoice_url + '" target="_blank" class="ai-seo-copy-btn">💳 Pagar Agora</a>';
                    }
                    
                    html += '<hr style="margin:20px 0;">';
                    html += '<p><strong>🔑 Sua chave de licença:</strong></p>';
                    html += '<div class="ai-seo-pix-code" style="background:#e8f5e9; font-weight:bold;">' + data.license_key + '</div>';
                    html += '<p style="font-size:12px; color:#666;">Guarde esta chave! Ela será ativada após a confirmação do pagamento.</p>';
                    html += '<div id="payment-status" style="margin-top:20px; padding:15px; background:#f0f7fc; border-radius:8px;">⏳ Aguardando confirmação do pagamento...</div>';
                    
                    $('#payment-result').html(html).show();
                    checkPaymentStatus(data.payment_id, data.license_key);
                }
                
                function checkPaymentStatus(paymentId, licenseKey) {
                    var attempts = 0, maxAttempts = 360;
                    var checkInterval = setInterval(function() {
                        attempts++;
                        if (attempts >= maxAttempts) { clearInterval(checkInterval); return; }
                        
                        $.ajax({
                            url: ajaxurl, type: 'POST',
                            data: { action: 'ai_seo_rm_check_order', nonce: '<?php echo wp_create_nonce('ai_seo_rm_upgrade'); ?>', payment_id: paymentId },
                            success: function(response) {
                                if (response.success && response.data.is_paid) {
                                    clearInterval(checkInterval);
                                    $('#payment-status').html(
                                        '<div style="color:#22c55e; font-size:20px; margin-bottom:15px;">🎉 Pagamento Confirmado!</div>' +
                                        '<p>Sua licença está ativa. Baixe o plugin PRO:</p>' +
                                        '<a href="https://github.com/pereira-lui/ai-seo-wp-rank-math/releases/latest/download/ai-seo-rankmath-pro.zip" class="ai-seo-copy-btn" style="display:inline-block; margin:10px 0;">⬇️ Download AI SEO PRO</a>' +
                                        '<p style="margin-top:15px; font-size:13px; color:#666;">Instale o plugin PRO e ative com a chave:<br><strong>' + licenseKey + '</strong></p>'
                                    );
                                }
                            }
                        });
                    }, 5000);
                }
            });
            
            function copyPixCode() {
                var code = document.getElementById('pix-code').innerText;
                navigator.clipboard.writeText(code).then(function() { alert('✅ Código PIX copiado!'); });
            }
            </script>
        </div>
        <?php
    }
    
    public function ajax_create_order() {
        check_ajax_referer('ai_seo_rm_upgrade', 'nonce');
        
        if (!$this->is_configured()) {
            wp_send_json_error(['message' => 'Sistema de pagamento não configurado.']);
        }
        
        $response = wp_remote_post($this->proxy_url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'action' => 'create_payment',
                'name' => sanitize_text_field($_POST['name'] ?? ''),
                'email' => sanitize_email($_POST['email'] ?? ''),
                'cpf' => sanitize_text_field($_POST['cpf'] ?? ''),
                'plan' => sanitize_text_field($_POST['plan'] ?? ''),
                'payment_method' => sanitize_text_field($_POST['payment_method'] ?? 'PIX'),
            ]),
            'timeout' => 30,
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Erro de conexão']);
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['success']) && $body['success']) {
            wp_send_json_success($body);
        } else {
            wp_send_json_error(['message' => $body['error'] ?? 'Erro ao processar']);
        }
    }
    
    public function ajax_check_order() {
        check_ajax_referer('ai_seo_rm_upgrade', 'nonce');
        
        if (!$this->is_configured()) {
            wp_send_json_error(['message' => 'Sistema não configurado.']);
        }
        
        $response = wp_remote_post($this->proxy_url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'action' => 'check_payment',
                'payment_id' => sanitize_text_field($_POST['payment_id'] ?? ''),
            ]),
            'timeout' => 15,
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Erro de conexão']);
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['success']) && $body['success']) {
            wp_send_json_success($body);
        } else {
            wp_send_json_error(['message' => $body['error'] ?? 'Erro']);
        }
    }
}

add_action('plugins_loaded', function() { AI_SEO_RM_Upgrade::instance(); });
