<?php
/**
 * Sistema de Upgrade para versão PRO
 * Permite comprar o plugin PRO diretamente de dentro do WordPress
 * 
 * @package AI_SEO_RankMath_Free
 * @since 2.2.0
 */

if (!defined('ABSPATH')) exit;

class AI_SEO_RM_Upgrade {
    
    /** @var string URL da API do Asaas */
    private $api_url = 'https://api.asaas.com/v3/';
    
    /** @var string API Key do Asaas (SUA chave) */
    private $api_key = ''; // Coloque sua chave aqui
    
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
        // IMPORTANTE: Configure sua chave do Asaas aqui
        // ========================================================
        $this->api_key = '$aact_COLOQUE_SUA_CHAVE_ASAAS_AQUI';
        
        // Para usar sandbox (testes), descomente a linha abaixo:
        // $this->api_url = 'https://sandbox.asaas.com/api/v3/';
        
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
        add_action('rest_api_init', [$this, 'register_webhook']);
    }
    
    /**
     * Adiciona página de upgrade
     */
    public function add_upgrade_page() {
        add_submenu_page(
            'options-general.php',
            'Upgrade para PRO - AI SEO',
            '',  // Oculto do menu
            'manage_options',
            'ai-seo-upgrade',
            [$this, 'render_upgrade_page']
        );
    }
    
    /**
     * Renderiza página de upgrade/compra
     */
    public function render_upgrade_page() {
        // Verifica se API está configurada
        $api_configured = !empty($this->api_key) && strpos($this->api_key, 'COLOQUE') === false;
        
        ?>
        <div class="wrap">
            <style>
                .ai-seo-upgrade-wrap {
                    max-width: 900px;
                    margin: 20px auto;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                }
                .ai-seo-header {
                    text-align: center;
                    padding: 40px 20px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: #fff;
                    border-radius: 12px;
                    margin-bottom: 30px;
                }
                .ai-seo-header h1 {
                    margin: 0 0 10px 0;
                    font-size: 32px;
                    color: #fff;
                }
                .ai-seo-header p {
                    margin: 0;
                    font-size: 18px;
                    opacity: 0.9;
                }
                .ai-seo-plans {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 20px;
                    margin-bottom: 30px;
                }
                @media (max-width: 768px) {
                    .ai-seo-plans {
                        grid-template-columns: 1fr;
                    }
                }
                .ai-seo-plan {
                    background: #fff;
                    border: 2px solid #e0e0e0;
                    border-radius: 12px;
                    padding: 25px;
                    text-align: center;
                    position: relative;
                    transition: all 0.3s ease;
                }
                .ai-seo-plan:hover {
                    border-color: #667eea;
                    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.2);
                }
                .ai-seo-plan.popular {
                    border-color: #667eea;
                    transform: scale(1.05);
                }
                .ai-seo-plan.popular .popular-badge {
                    position: absolute;
                    top: -12px;
                    left: 50%;
                    transform: translateX(-50%);
                    background: #667eea;
                    color: #fff;
                    padding: 5px 15px;
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: bold;
                }
                .ai-seo-plan h3 {
                    margin: 0 0 10px 0;
                    font-size: 20px;
                    color: #333;
                }
                .ai-seo-plan .price {
                    font-size: 36px;
                    font-weight: bold;
                    color: #667eea;
                    margin: 15px 0;
                }
                .ai-seo-plan .price small {
                    font-size: 14px;
                    color: #666;
                    font-weight: normal;
                }
                .ai-seo-plan .description {
                    color: #666;
                    margin-bottom: 20px;
                    font-size: 14px;
                }
                .ai-seo-plan button {
                    width: 100%;
                    padding: 12px 20px;
                    font-size: 16px;
                    font-weight: bold;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                }
                .ai-seo-plan button.primary {
                    background: #667eea;
                    color: #fff;
                }
                .ai-seo-plan button.primary:hover {
                    background: #5a6fd6;
                }
                .ai-seo-plan button.secondary {
                    background: #f0f0f0;
                    color: #333;
                }
                .ai-seo-plan button.secondary:hover {
                    background: #e0e0e0;
                }
                
                /* Modal de Checkout */
                .ai-seo-modal {
                    display: none;
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0,0,0,0.7);
                    z-index: 100000;
                    align-items: center;
                    justify-content: center;
                }
                .ai-seo-modal.active {
                    display: flex;
                }
                .ai-seo-modal-content {
                    background: #fff;
                    border-radius: 12px;
                    max-width: 500px;
                    width: 90%;
                    max-height: 90vh;
                    overflow-y: auto;
                    position: relative;
                }
                .ai-seo-modal-header {
                    padding: 20px;
                    border-bottom: 1px solid #eee;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .ai-seo-modal-header h2 {
                    margin: 0;
                    font-size: 20px;
                }
                .ai-seo-modal-close {
                    background: none;
                    border: none;
                    font-size: 24px;
                    cursor: pointer;
                    color: #666;
                }
                .ai-seo-modal-body {
                    padding: 20px;
                }
                .ai-seo-form-group {
                    margin-bottom: 15px;
                }
                .ai-seo-form-group label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: 600;
                    color: #333;
                }
                .ai-seo-form-group input,
                .ai-seo-form-group select {
                    width: 100%;
                    padding: 10px 12px;
                    border: 1px solid #ddd;
                    border-radius: 6px;
                    font-size: 14px;
                }
                .ai-seo-form-group input:focus,
                .ai-seo-form-group select:focus {
                    border-color: #667eea;
                    outline: none;
                }
                .ai-seo-payment-methods {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 10px;
                    margin-bottom: 20px;
                }
                .ai-seo-payment-method {
                    border: 2px solid #e0e0e0;
                    border-radius: 8px;
                    padding: 15px 10px;
                    text-align: center;
                    cursor: pointer;
                    transition: all 0.2s ease;
                }
                .ai-seo-payment-method:hover {
                    border-color: #667eea;
                }
                .ai-seo-payment-method.selected {
                    border-color: #667eea;
                    background: #f0f4ff;
                }
                .ai-seo-payment-method .icon {
                    font-size: 24px;
                    margin-bottom: 5px;
                }
                .ai-seo-payment-method .label {
                    font-size: 12px;
                    color: #666;
                }
                .ai-seo-btn-checkout {
                    width: 100%;
                    padding: 15px;
                    font-size: 16px;
                    font-weight: bold;
                    background: #667eea;
                    color: #fff;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                }
                .ai-seo-btn-checkout:hover {
                    background: #5a6fd6;
                }
                .ai-seo-btn-checkout:disabled {
                    background: #ccc;
                    cursor: not-allowed;
                }
                
                /* Resultado do pagamento */
                .ai-seo-payment-result {
                    text-align: center;
                    padding: 20px;
                }
                .ai-seo-pix-qr {
                    max-width: 200px;
                    margin: 15px auto;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    padding: 10px;
                    background: #fff;
                }
                .ai-seo-pix-code {
                    background: #f5f5f5;
                    padding: 10px;
                    border-radius: 6px;
                    word-break: break-all;
                    font-size: 12px;
                    margin: 10px 0;
                }
                .ai-seo-copy-btn {
                    background: #667eea;
                    color: #fff;
                    border: none;
                    padding: 8px 15px;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 14px;
                }
                .ai-seo-features {
                    background: #f9f9f9;
                    border-radius: 12px;
                    padding: 25px;
                    margin-bottom: 30px;
                }
                .ai-seo-features h2 {
                    margin: 0 0 20px 0;
                    text-align: center;
                }
                .ai-seo-features-grid {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 15px;
                }
                .ai-seo-feature {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                .ai-seo-feature .check {
                    color: #22c55e;
                    font-size: 20px;
                }
                .ai-seo-guarantee {
                    text-align: center;
                    padding: 20px;
                    background: #fff3cd;
                    border-radius: 8px;
                    margin-bottom: 20px;
                }
            </style>
            
            <div class="ai-seo-upgrade-wrap">
                <div class="ai-seo-header">
                    <h1>🚀 Upgrade para AI SEO PRO</h1>
                    <p>Preencha automaticamente o Rank Math com Inteligência Artificial</p>
                </div>
                
                <?php if (!$api_configured): ?>
                <div style="background:#fff3cd; border:1px solid #ffc107; padding:20px; border-radius:8px; margin-bottom:20px;">
                    <strong>⚠️ Sistema de pagamento não configurado.</strong><br>
                    O desenvolvedor precisa configurar a chave do Asaas no arquivo <code>class-upgrade.php</code>.
                </div>
                <?php endif; ?>
                
                <!-- Planos -->
                <div class="ai-seo-plans">
                    <?php foreach ($this->plans as $plan): ?>
                    <div class="ai-seo-plan <?php echo isset($plan['popular']) ? 'popular' : ''; ?>">
                        <?php if (isset($plan['popular'])): ?>
                        <span class="popular-badge">MAIS POPULAR</span>
                        <?php endif; ?>
                        <h3><?php echo esc_html($plan['name']); ?></h3>
                        <div class="price">
                            R$ <?php echo number_format($plan['price'], 2, ',', '.'); ?>
                            <small><?php echo $plan['days'] ? '' : '/ único'; ?></small>
                        </div>
                        <p class="description"><?php echo esc_html($plan['description']); ?></p>
                        <button 
                            class="<?php echo isset($plan['popular']) ? 'primary' : 'secondary'; ?> ai-seo-buy-btn"
                            data-plan="<?php echo esc_attr($plan['id']); ?>"
                            data-price="<?php echo esc_attr($plan['price']); ?>"
                            data-name="<?php echo esc_attr($plan['name']); ?>"
                            <?php echo !$api_configured ? 'disabled' : ''; ?>
                        >
                            Comprar Agora
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Features -->
                <div class="ai-seo-features">
                    <h2>✨ O que você recebe na versão PRO</h2>
                    <div class="ai-seo-features-grid">
                        <div class="ai-seo-feature">
                            <span class="check">✅</span>
                            <span>Geração automática de Title SEO</span>
                        </div>
                        <div class="ai-seo-feature">
                            <span class="check">✅</span>
                            <span>Geração automática de Description</span>
                        </div>
                        <div class="ai-seo-feature">
                            <span class="check">✅</span>
                            <span>Sugestão de Focus Keyword</span>
                        </div>
                        <div class="ai-seo-feature">
                            <span class="check">✅</span>
                            <span>Auto-aplicar ao publicar posts</span>
                        </div>
                        <div class="ai-seo-feature">
                            <span class="check">✅</span>
                            <span>Open Graph (Facebook/Twitter)</span>
                        </div>
                        <div class="ai-seo-feature">
                            <span class="check">✅</span>
                            <span>Brief/Contexto personalizado</span>
                        </div>
                        <div class="ai-seo-feature">
                            <span class="check">✅</span>
                            <span>Atualizações automáticas</span>
                        </div>
                        <div class="ai-seo-feature">
                            <span class="check">✅</span>
                            <span>Suporte por email</span>
                        </div>
                    </div>
                </div>
                
                <!-- Garantia -->
                <div class="ai-seo-guarantee">
                    🛡️ <strong>Garantia de 7 dias</strong> - Se não ficar satisfeito, devolvemos seu dinheiro.
                </div>
            </div>
            
            <!-- Modal de Checkout -->
            <div class="ai-seo-modal" id="ai-seo-checkout-modal">
                <div class="ai-seo-modal-content">
                    <div class="ai-seo-modal-header">
                        <h2>🛒 Finalizar Compra</h2>
                        <button class="ai-seo-modal-close">&times;</button>
                    </div>
                    <div class="ai-seo-modal-body">
                        <!-- Formulário de checkout -->
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
                                        <div class="icon">📱</div>
                                        <div class="label">PIX</div>
                                    </div>
                                    <div class="ai-seo-payment-method" data-method="BOLETO">
                                        <div class="icon">📄</div>
                                        <div class="label">Boleto</div>
                                    </div>
                                    <div class="ai-seo-payment-method" data-method="CREDIT_CARD">
                                        <div class="icon">💳</div>
                                        <div class="label">Cartão</div>
                                    </div>
                                </div>
                                <input type="hidden" id="checkout-payment-method" name="payment_method" value="PIX">
                            </div>
                            
                            <button type="submit" class="ai-seo-btn-checkout" id="btn-checkout">
                                💳 Pagar Agora
                            </button>
                        </form>
                        
                        <!-- Resultado do pagamento -->
                        <div class="ai-seo-payment-result" id="payment-result" style="display:none;"></div>
                    </div>
                </div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                var selectedPlan = null;
                var paymentMethod = 'PIX';
                
                // Clique no botão de comprar
                $('.ai-seo-buy-btn').on('click', function() {
                    selectedPlan = {
                        id: $(this).data('plan'),
                        price: $(this).data('price'),
                        name: $(this).data('name')
                    };
                    
                    $('#checkout-plan').val(selectedPlan.id);
                    $('#checkout-plan-info').html(
                        '<strong>' + selectedPlan.name + '</strong> - R$ ' + 
                        selectedPlan.price.toFixed(2).replace('.', ',')
                    );
                    
                    // Reset form
                    $('#ai-seo-checkout-form').show();
                    $('#payment-result').hide();
                    
                    // Abre modal
                    $('#ai-seo-checkout-modal').addClass('active');
                });
                
                // Fechar modal
                $('.ai-seo-modal-close').on('click', function() {
                    $('#ai-seo-checkout-modal').removeClass('active');
                });
                
                // Fechar ao clicar fora
                $('#ai-seo-checkout-modal').on('click', function(e) {
                    if (e.target === this) {
                        $(this).removeClass('active');
                    }
                });
                
                // Selecionar método de pagamento
                $('.ai-seo-payment-method').on('click', function() {
                    $('.ai-seo-payment-method').removeClass('selected');
                    $(this).addClass('selected');
                    paymentMethod = $(this).data('method');
                    $('#checkout-payment-method').val(paymentMethod);
                });
                
                // Submit do form
                $('#ai-seo-checkout-form').on('submit', function(e) {
                    e.preventDefault();
                    
                    var $btn = $('#btn-checkout');
                    $btn.prop('disabled', true).text('Processando...');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
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
                                alert('Erro: ' + response.data.message);
                                $btn.prop('disabled', false).text('💳 Pagar Agora');
                            }
                        },
                        error: function() {
                            alert('Erro de conexão. Tente novamente.');
                            $btn.prop('disabled', false).text('💳 Pagar Agora');
                        }
                    });
                });
                
                function showPaymentResult(data) {
                    var html = '<h3>✅ Cobrança criada!</h3>';
                    
                    if (data.pix) {
                        html += '<p>Escaneie o QR Code ou copie o código PIX:</p>';
                        if (data.pix.qrcode_image) {
                            html += '<img src="data:image/png;base64,' + data.pix.qrcode_image + '" class="ai-seo-pix-qr">';
                        }
                        html += '<div class="ai-seo-pix-code" id="pix-code">' + data.pix.payload + '</div>';
                        html += '<button class="ai-seo-copy-btn" onclick="copyPixCode()">📋 Copiar Código PIX</button>';
                    } else if (data.boleto_url) {
                        html += '<p>Seu boleto foi gerado:</p>';
                        html += '<a href="' + data.boleto_url + '" target="_blank" class="ai-seo-copy-btn">📄 Ver Boleto</a>';
                    } else if (data.invoice_url) {
                        html += '<p>Complete o pagamento:</p>';
                        html += '<a href="' + data.invoice_url + '" target="_blank" class="ai-seo-copy-btn">💳 Pagar Agora</a>';
                    }
                    
                    html += '<hr style="margin:20px 0;">';
                    html += '<p><strong>Sua chave de licença:</strong></p>';
                    html += '<div class="ai-seo-pix-code">' + data.license_key + '</div>';
                    html += '<p style="font-size:12px; color:#666;">Guarde esta chave! Ela será ativada automaticamente após a confirmação do pagamento.</p>';
                    html += '<p style="font-size:12px; color:#666;">Você também receberá por e-mail.</p>';
                    
                    // Polling para verificar pagamento
                    html += '<div id="payment-status" style="margin-top:20px; padding:15px; background:#f0f7fc; border-radius:8px;">';
                    html += '⏳ Aguardando confirmação do pagamento...';
                    html += '</div>';
                    
                    $('#payment-result').html(html).show();
                    
                    // Inicia polling
                    checkPaymentStatus(data.payment_id, data.license_key);
                }
                
                function checkPaymentStatus(paymentId, licenseKey) {
                    var checkInterval = setInterval(function() {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'ai_seo_rm_check_order',
                                nonce: '<?php echo wp_create_nonce('ai_seo_rm_upgrade'); ?>',
                                payment_id: paymentId
                            },
                            success: function(response) {
                                if (response.success && response.data.is_paid) {
                                    clearInterval(checkInterval);
                                    $('#payment-status').html(
                                        '<div style="color:#22c55e; font-size:18px;">✅ Pagamento Confirmado!</div>' +
                                        '<p>Sua licença foi ativada. Faça o download do plugin PRO:</p>' +
                                        '<a href="https://github.com/pereira-lui/ai-seo-wp-rank-math/releases/latest/download/ai-seo-rankmath-pro.zip" ' +
                                        'class="ai-seo-copy-btn" style="display:inline-block; margin-top:10px;">⬇️ Download AI SEO PRO</a>' +
                                        '<p style="margin-top:15px; font-size:12px; color:#666;">Instale o plugin PRO e ative com a chave: <strong>' + licenseKey + '</strong></p>'
                                    );
                                }
                            }
                        });
                    }, 5000); // Verifica a cada 5 segundos
                    
                    // Para após 30 minutos
                    setTimeout(function() {
                        clearInterval(checkInterval);
                    }, 1800000);
                }
            });
            
            function copyPixCode() {
                var code = document.getElementById('pix-code').innerText;
                navigator.clipboard.writeText(code).then(function() {
                    alert('Código PIX copiado!');
                });
            }
            </script>
        </div>
        <?php
    }
    
    /**
     * AJAX: Criar pedido
     */
    public function ajax_create_order() {
        check_ajax_referer('ai_seo_rm_upgrade', 'nonce');
        
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $cpf = sanitize_text_field($_POST['cpf'] ?? '');
        $plan_id = sanitize_text_field($_POST['plan'] ?? '');
        $payment_method = sanitize_text_field($_POST['payment_method'] ?? 'PIX');
        
        if (!$name || !$email || !isset($this->plans[$plan_id])) {
            wp_send_json_error(['message' => 'Dados inválidos.']);
        }
        
        $plan = $this->plans[$plan_id];
        
        // Cria/encontra cliente no Asaas
        $customer = $this->find_or_create_customer($email, $name, $cpf);
        
        if (!$customer) {
            wp_send_json_error(['message' => 'Erro ao criar cliente.']);
        }
        
        // Gera licença
        $license_key = $this->generate_license_key();
        
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
        
        $result = $this->api_request('payments', 'POST', $payment_data);
        
        if (!$result['success']) {
            wp_send_json_error(['message' => $result['error'] ?? 'Erro ao criar cobrança.']);
        }
        
        $payment = $result['data'];
        
        // Salva pedido pendente
        $this->save_pending_order($payment, $plan, $license_key, $email);
        
        $response = [
            'payment_id' => $payment['id'],
            'license_key' => $license_key,
            'status' => $payment['status'],
        ];
        
        // Adiciona URLs
        if (isset($payment['invoiceUrl'])) {
            $response['invoice_url'] = $payment['invoiceUrl'];
        }
        if (isset($payment['bankSlipUrl'])) {
            $response['boleto_url'] = $payment['bankSlipUrl'];
        }
        
        // PIX QR Code
        if ($payment_method === 'PIX') {
            $pix = $this->get_pix_qrcode($payment['id']);
            if ($pix) {
                $response['pix'] = $pix;
            }
        }
        
        wp_send_json_success($response);
    }
    
    /**
     * AJAX: Verificar status do pedido
     */
    public function ajax_check_order() {
        check_ajax_referer('ai_seo_rm_upgrade', 'nonce');
        
        $payment_id = sanitize_text_field($_POST['payment_id'] ?? '');
        
        if (!$payment_id) {
            wp_send_json_error(['message' => 'ID não informado.']);
        }
        
        $result = $this->api_request('payments/' . $payment_id);
        
        if ($result['success']) {
            $is_paid = in_array($result['data']['status'], ['CONFIRMED', 'RECEIVED']);
            wp_send_json_success([
                'status' => $result['data']['status'],
                'is_paid' => $is_paid,
            ]);
        }
        
        wp_send_json_error(['message' => 'Erro ao verificar.']);
    }
    
    /**
     * Registra webhook
     */
    public function register_webhook() {
        register_rest_route('ai-seo-rm/v1', '/webhook/payment', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_webhook'],
            'permission_callback' => '__return_true',
        ]);
    }
    
    /**
     * Processa webhook
     */
    public function handle_webhook($request) {
        $body = $request->get_json_params();
        $event = $body['event'] ?? '';
        $payment = $body['payment'] ?? [];
        
        if (in_array($event, ['PAYMENT_CONFIRMED', 'PAYMENT_RECEIVED'])) {
            $external_ref = json_decode($payment['externalReference'] ?? '{}', true);
            
            if (!empty($external_ref['email']) && !empty($external_ref['license_key'])) {
                // Envia email com licença
                $this->send_license_email(
                    $external_ref['email'],
                    $external_ref['license_key'],
                    $external_ref['plan_id'] ?? 'pro'
                );
            }
        }
        
        return new WP_REST_Response(['success' => true], 200);
    }
    
    /**
     * Envia email com licença
     */
    private function send_license_email($email, $license_key, $plan_id) {
        $subject = '🎉 Sua licença AI SEO PRO está ativa!';
        $message = "Olá!\n\n";
        $message .= "Seu pagamento foi confirmado e sua licença está ativa.\n\n";
        $message .= "Chave de Licença: {$license_key}\n\n";
        $message .= "Como ativar:\n";
        $message .= "1. Baixe o plugin PRO: https://github.com/pereira-lui/ai-seo-wp-rank-math/releases/latest/download/ai-seo-rankmath-pro.zip\n";
        $message .= "2. Instale no seu WordPress (Plugins > Adicionar Novo > Upload)\n";
        $message .= "3. Vá em Configurações > AI SEO (Rank Math)\n";
        $message .= "4. Cole a chave de licença e clique em Ativar\n\n";
        $message .= "Obrigado pela compra!\n";
        
        wp_mail($email, $subject, $message);
    }
    
    // === Métodos auxiliares ===
    
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
            return ['success' => false, 'error' => $response->get_error_message()];
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $code = wp_remote_retrieve_response_code($response);
        
        if ($code >= 200 && $code < 300) {
            return ['success' => true, 'data' => $body];
        }
        
        return [
            'success' => false,
            'error' => $body['errors'][0]['description'] ?? 'Erro desconhecido',
        ];
    }
    
    private function find_or_create_customer($email, $name, $cpf = null) {
        $search = $this->api_request('customers?email=' . urlencode($email));
        
        if ($search['success'] && !empty($search['data']['data'])) {
            return $search['data']['data'][0];
        }
        
        $customer_data = [
            'name' => $name,
            'email' => $email,
        ];
        
        if ($cpf) {
            $customer_data['cpfCnpj'] = preg_replace('/\D/', '', $cpf);
        }
        
        $result = $this->api_request('customers', 'POST', $customer_data);
        
        return $result['success'] ? $result['data'] : null;
    }
    
    private function generate_license_key() {
        $segments = [];
        for ($i = 0; $i < 4; $i++) {
            $segments[] = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
        }
        return 'AISEO-' . implode('-', $segments);
    }
    
    private function get_pix_qrcode($payment_id) {
        $result = $this->api_request('payments/' . $payment_id . '/pixQrCode');
        
        if ($result['success']) {
            return [
                'payload' => $result['data']['payload'] ?? '',
                'qrcode_image' => $result['data']['encodedImage'] ?? '',
            ];
        }
        
        return null;
    }
    
    private function save_pending_order($payment, $plan, $license_key, $email) {
        $orders = get_option('ai_seo_rm_pending_orders', []);
        $orders[$payment['id']] = [
            'payment_id' => $payment['id'],
            'license_key' => $license_key,
            'plan_id' => $plan['id'],
            'email' => $email,
            'value' => $payment['value'],
            'created_at' => current_time('mysql'),
        ];
        update_option('ai_seo_rm_pending_orders', $orders);
    }
}

// Inicializa
add_action('plugins_loaded', function() {
    AI_SEO_RM_Upgrade::instance();
});
