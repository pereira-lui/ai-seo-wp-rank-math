<?php
/**
 * Página de Compra/Ativação de Licença
 * 
 * @package AI_SEO_RankMath
 * @since 2.1.0
 */

if (!defined('ABSPATH')) exit;

/**
 * Renderiza a página de compra
 */
function ai_seo_rm_render_purchase_page() {
    $asaas = ai_seo_rm_asaas();
    $plans = $asaas->get_plans();
    $is_configured = $asaas->is_configured();
    $license = ai_seo_rm_license();
    $license_info = $license->get_license_info();
    
    ?>
    <div class="ai-seo-rm-purchase-wrap">
        <style>
            .ai-seo-rm-purchase-wrap {
                max-width: 900px;
                margin: 20px auto;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            }
            .ai-seo-rm-header {
                text-align: center;
                margin-bottom: 30px;
            }
            .ai-seo-rm-header h1 {
                font-size: 28px;
                margin-bottom: 10px;
            }
            .ai-seo-rm-header p {
                color: #666;
                font-size: 16px;
            }
            .ai-seo-rm-plans {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            .ai-seo-rm-plan {
                background: #fff;
                border: 2px solid #e0e0e0;
                border-radius: 12px;
                padding: 25px;
                text-align: center;
                transition: all 0.3s ease;
                cursor: pointer;
            }
            .ai-seo-rm-plan:hover {
                border-color: #2271b1;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
            .ai-seo-rm-plan.selected {
                border-color: #2271b1;
                background: #f0f7fc;
            }
            .ai-seo-rm-plan.popular {
                position: relative;
            }
            .ai-seo-rm-plan.popular::before {
                content: "Mais Popular";
                position: absolute;
                top: -12px;
                left: 50%;
                transform: translateX(-50%);
                background: #2271b1;
                color: #fff;
                padding: 4px 12px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: bold;
            }
            .ai-seo-rm-plan-name {
                font-size: 20px;
                font-weight: bold;
                margin-bottom: 10px;
            }
            .ai-seo-rm-plan-price {
                font-size: 36px;
                font-weight: bold;
                color: #2271b1;
            }
            .ai-seo-rm-plan-price span {
                font-size: 14px;
                color: #666;
                font-weight: normal;
            }
            .ai-seo-rm-plan-features {
                list-style: none;
                padding: 0;
                margin: 20px 0;
                text-align: left;
            }
            .ai-seo-rm-plan-features li {
                padding: 8px 0;
                border-bottom: 1px solid #eee;
            }
            .ai-seo-rm-plan-features li::before {
                content: "✓";
                color: #00a32a;
                font-weight: bold;
                margin-right: 8px;
            }
            .ai-seo-rm-checkout {
                background: #fff;
                border: 1px solid #e0e0e0;
                border-radius: 12px;
                padding: 30px;
            }
            .ai-seo-rm-checkout h2 {
                margin-top: 0;
                margin-bottom: 20px;
            }
            .ai-seo-rm-form-row {
                margin-bottom: 15px;
            }
            .ai-seo-rm-form-row label {
                display: block;
                margin-bottom: 5px;
                font-weight: 500;
            }
            .ai-seo-rm-form-row input,
            .ai-seo-rm-form-row select {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid #ddd;
                border-radius: 6px;
                font-size: 14px;
            }
            .ai-seo-rm-form-row input:focus,
            .ai-seo-rm-form-row select:focus {
                border-color: #2271b1;
                outline: none;
                box-shadow: 0 0 0 2px rgba(34,113,177,0.2);
            }
            .ai-seo-rm-payment-methods {
                display: flex;
                gap: 10px;
                margin: 20px 0;
            }
            .ai-seo-rm-payment-method {
                flex: 1;
                padding: 15px;
                border: 2px solid #e0e0e0;
                border-radius: 8px;
                text-align: center;
                cursor: pointer;
                transition: all 0.2s;
            }
            .ai-seo-rm-payment-method:hover {
                border-color: #2271b1;
            }
            .ai-seo-rm-payment-method.selected {
                border-color: #2271b1;
                background: #f0f7fc;
            }
            .ai-seo-rm-payment-method-icon {
                font-size: 24px;
                margin-bottom: 5px;
            }
            .ai-seo-rm-btn-purchase {
                width: 100%;
                padding: 15px 30px;
                background: #2271b1;
                color: #fff;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: bold;
                cursor: pointer;
                transition: background 0.2s;
            }
            .ai-seo-rm-btn-purchase:hover {
                background: #135e96;
            }
            .ai-seo-rm-btn-purchase:disabled {
                background: #ccc;
                cursor: not-allowed;
            }
            .ai-seo-rm-result {
                margin-top: 20px;
                padding: 20px;
                border-radius: 8px;
                display: none;
            }
            .ai-seo-rm-result.success {
                background: #d4edda;
                border: 1px solid #c3e6cb;
                color: #155724;
            }
            .ai-seo-rm-result.error {
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                color: #721c24;
            }
            .ai-seo-rm-pix-container {
                text-align: center;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 8px;
                margin-top: 15px;
            }
            .ai-seo-rm-pix-qrcode {
                max-width: 200px;
                margin: 15px auto;
            }
            .ai-seo-rm-pix-code {
                background: #fff;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                word-break: break-all;
                font-size: 12px;
                margin: 10px 0;
            }
            .ai-seo-rm-copy-btn {
                padding: 8px 16px;
                background: #28a745;
                color: #fff;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }
            .ai-seo-rm-secure-badge {
                text-align: center;
                margin-top: 20px;
                color: #666;
                font-size: 13px;
            }
            .ai-seo-rm-secure-badge span {
                color: #00a32a;
            }
        </style>
        
        <?php if ($license_info['is_active']): ?>
            <div style="background:#d4edda; border:1px solid #c3e6cb; padding:20px; border-radius:8px; text-align:center; margin-bottom:20px;">
                <h2 style="color:#155724; margin:0 0 10px 0;">✅ Licença Ativa</h2>
                <p style="margin:0; color:#155724;">Sua licença está ativa: <strong><?php echo esc_html($license_info['key_masked']); ?></strong></p>
            </div>
        <?php else: ?>
            
            <div class="ai-seo-rm-header">
                <h1>🚀 Adquira sua Licença</h1>
                <p>Desbloqueie todo o potencial do AI SEO Assistant para Rank Math</p>
            </div>
            
            <?php if (!$is_configured): ?>
                <div style="background:#fff3cd; border:1px solid #ffc107; padding:15px; border-radius:8px; margin-bottom:20px;">
                    <strong>⚠️ Atenção:</strong> A integração com Asaas não está configurada. 
                    Configure em <code>wp-config.php</code>: <code>define('AI_SEO_RM_ASAAS_API_KEY', 'sua_chave');</code>
                </div>
            <?php endif; ?>
            
            <!-- Planos -->
            <div class="ai-seo-rm-plans">
                <?php foreach ($plans as $plan_id => $plan): ?>
                    <div class="ai-seo-rm-plan <?php echo $plan_id === 'yearly' ? 'popular' : ''; ?>" 
                         data-plan="<?php echo esc_attr($plan_id); ?>">
                        <div class="ai-seo-rm-plan-name"><?php echo esc_html($plan['name']); ?></div>
                        <div class="ai-seo-rm-plan-price">
                            R$ <?php echo number_format($plan['price'], 2, ',', '.'); ?>
                            <span><?php echo $plan['cycle'] === 'MONTHLY' ? '/mês' : ($plan['cycle'] === 'YEARLY' ? '/ano' : ''); ?></span>
                        </div>
                        <ul class="ai-seo-rm-plan-features">
                            <?php foreach ($plan['features'] as $feature): ?>
                                <li><?php echo esc_html($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Checkout -->
            <div class="ai-seo-rm-checkout">
                <h2>💳 Finalizar Compra</h2>
                
                <form id="ai-seo-rm-checkout-form">
                    <div class="ai-seo-rm-form-row">
                        <label for="checkout-name">Nome Completo *</label>
                        <input type="text" id="checkout-name" name="name" required>
                    </div>
                    
                    <div class="ai-seo-rm-form-row">
                        <label for="checkout-email">Email *</label>
                        <input type="email" id="checkout-email" name="email" required 
                               value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>">
                    </div>
                    
                    <div class="ai-seo-rm-form-row">
                        <label for="checkout-cpf">CPF/CNPJ</label>
                        <input type="text" id="checkout-cpf" name="cpf_cnpj" placeholder="Opcional">
                    </div>
                    
                    <input type="hidden" id="checkout-plan" name="plan" value="yearly">
                    <input type="hidden" id="checkout-billing" name="billing_type" value="PIX">
                    
                    <label style="display:block; margin-bottom:10px; font-weight:500;">Forma de Pagamento</label>
                    <div class="ai-seo-rm-payment-methods">
                        <div class="ai-seo-rm-payment-method selected" data-method="PIX">
                            <div class="ai-seo-rm-payment-method-icon">📱</div>
                            <div>PIX</div>
                        </div>
                        <div class="ai-seo-rm-payment-method" data-method="BOLETO">
                            <div class="ai-seo-rm-payment-method-icon">📄</div>
                            <div>Boleto</div>
                        </div>
                        <div class="ai-seo-rm-payment-method" data-method="CREDIT_CARD">
                            <div class="ai-seo-rm-payment-method-icon">💳</div>
                            <div>Cartão</div>
                        </div>
                    </div>
                    
                    <button type="submit" class="ai-seo-rm-btn-purchase" <?php echo !$is_configured ? 'disabled' : ''; ?>>
                        🔒 Comprar Agora
                    </button>
                </form>
                
                <div id="ai-seo-rm-payment-result" class="ai-seo-rm-result"></div>
                
                <div class="ai-seo-rm-secure-badge">
                    <span>🔒</span> Pagamento seguro processado pelo Asaas
                </div>
            </div>
            
        <?php endif; ?>
    </div>
    
    <script>
    (function($){
        // Selecionar plano
        $('.ai-seo-rm-plan').on('click', function(){
            $('.ai-seo-rm-plan').removeClass('selected');
            $(this).addClass('selected');
            $('#checkout-plan').val($(this).data('plan'));
        });
        
        // Selecionar método de pagamento
        $('.ai-seo-rm-payment-method').on('click', function(){
            $('.ai-seo-rm-payment-method').removeClass('selected');
            $(this).addClass('selected');
            $('#checkout-billing').val($(this).data('method'));
        });
        
        // Selecionar plano anual por padrão
        $('.ai-seo-rm-plan[data-plan="yearly"]').addClass('selected');
        
        // Máscara CPF/CNPJ
        $('#checkout-cpf').on('input', function(){
            var v = $(this).val().replace(/\D/g, '');
            if (v.length <= 11) {
                v = v.replace(/(\d{3})(\d)/, '$1.$2');
                v = v.replace(/(\d{3})(\d)/, '$1.$2');
                v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            } else {
                v = v.replace(/^(\d{2})(\d)/, '$1.$2');
                v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
                v = v.replace(/(\d{4})(\d)/, '$1-$2');
            }
            $(this).val(v);
        });
        
        // Submit do formulário
        $('#ai-seo-rm-checkout-form').on('submit', function(e){
            e.preventDefault();
            
            var $btn = $(this).find('button[type="submit"]');
            var $result = $('#ai-seo-rm-payment-result');
            
            $btn.prop('disabled', true).text('Processando...');
            $result.hide();
            
            $.post(AISEO_RM.ajaxurl, {
                action: 'ai_seo_rm_create_payment',
                nonce: AISEO_RM.nonce,
                name: $('#checkout-name').val(),
                email: $('#checkout-email').val(),
                cpf_cnpj: $('#checkout-cpf').val(),
                plan: $('#checkout-plan').val(),
                billing_type: $('#checkout-billing').val()
            }).done(function(resp){
                $btn.prop('disabled', false).text('🔒 Comprar Agora');
                
                if (resp.success) {
                    var html = '<h3>✅ Cobrança criada!</h3>';
                    html += '<p><strong>Chave de licença:</strong> <code>' + resp.data.license_key + '</code></p>';
                    
                    // PIX
                    if (resp.data.pix) {
                        html += '<div class="ai-seo-rm-pix-container">';
                        html += '<h4>📱 Pague com PIX</h4>';
                        if (resp.data.pix.qrcode_image) {
                            html += '<img src="data:image/png;base64,' + resp.data.pix.qrcode_image + '" class="ai-seo-rm-pix-qrcode">';
                        }
                        html += '<div class="ai-seo-rm-pix-code">' + resp.data.pix.payload + '</div>';
                        html += '<button type="button" class="ai-seo-rm-copy-btn" onclick="navigator.clipboard.writeText(\'' + resp.data.pix.payload + '\'); this.textContent=\'Copiado!\';">Copiar código PIX</button>';
                        html += '</div>';
                    }
                    
                    // Boleto
                    if (resp.data.boleto_url) {
                        html += '<p><a href="' + resp.data.boleto_url + '" target="_blank" class="button">📄 Abrir Boleto</a></p>';
                    }
                    
                    // Link de pagamento geral
                    if (resp.data.invoice_url) {
                        html += '<p><a href="' + resp.data.invoice_url + '" target="_blank" class="button button-primary">💳 Pagar Agora</a></p>';
                    }
                    
                    html += '<p style="margin-top:15px; color:#666;">Após o pagamento, sua licença será ativada automaticamente.</p>';
                    
                    // Salva payment_id para verificar status
                    html += '<button type="button" class="button" onclick="checkPaymentStatus(\'' + resp.data.payment_id + '\')">🔄 Verificar Status do Pagamento</button>';
                    
                    $result.html(html).removeClass('error').addClass('success').show();
                    
                } else {
                    $result.html('<p>❌ ' + resp.data.message + '</p>').removeClass('success').addClass('error').show();
                }
            }).fail(function(){
                $btn.prop('disabled', false).text('🔒 Comprar Agora');
                $result.html('<p>❌ Erro ao processar. Tente novamente.</p>').removeClass('success').addClass('error').show();
            });
        });
        
        // Verificar status do pagamento
        window.checkPaymentStatus = function(paymentId) {
            $.post(AISEO_RM.ajaxurl, {
                action: 'ai_seo_rm_check_payment',
                nonce: AISEO_RM.nonce,
                payment_id: paymentId
            }).done(function(resp){
                if (resp.success) {
                    if (resp.data.is_paid) {
                        alert('✅ Pagamento confirmado! Sua licença foi ativada. Recarregando...');
                        location.reload();
                    } else {
                        alert('⏳ Status: ' + resp.data.status_label);
                    }
                } else {
                    alert('Erro ao verificar: ' + resp.data.message);
                }
            });
        };
        
    })(jQuery);
    </script>
    <?php
}
