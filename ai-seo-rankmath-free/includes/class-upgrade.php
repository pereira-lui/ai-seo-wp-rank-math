<?php
/**
 * Página de informações sobre a versão PRO
 * 
 * Compatível com as diretrizes do WordPress.org
 * Apenas informativo, sem checkout interno
 * 
 * @package AI_SEO_RankMath_Free
 * @since 2.4.0
 */

if (!defined('ABSPATH')) exit;

class AI_SEO_RM_Upgrade {
    
    /** @var string URL do site de vendas */
    private $sales_url = 'https://ai-seo-wp-rank-math.it2.solutions/';
    
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
        add_action('admin_menu', [$this, 'add_upgrade_menu']);
        add_filter('plugin_action_links_' . AI_SEO_RM_BASENAME, [$this, 'add_plugin_links']);
    }
    
    /**
     * Adiciona link nas ações do plugin
     */
    public function add_plugin_links($links) {
        $pro_link = '<a href="' . esc_url($this->sales_url) . '" target="_blank" style="color:#667eea;font-weight:bold;">🚀 Obter PRO</a>';
        array_unshift($links, $pro_link);
        return $links;
    }
    
    /**
     * Adiciona item no menu
     */
    public function add_upgrade_menu() {
        add_submenu_page(
            'options-general.php',
            'AI SEO PRO',
            '<span style="color:#667eea;">🚀 AI SEO PRO</span>',
            'manage_options',
            'ai-seo-pro',
            [$this, 'render_page']
        );
    }
    
    /**
     * Renderiza página informativa
     */
    public function render_page() {
        ?>
        <div class="wrap">
            <style>
                .ai-seo-pro-wrap { max-width: 800px; margin: 20px auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
                .ai-seo-pro-header { text-align: center; padding: 40px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border-radius: 12px; margin-bottom: 30px; }
                .ai-seo-pro-header h1 { margin: 0 0 10px 0; font-size: 28px; color: #fff; }
                .ai-seo-pro-header p { margin: 0; font-size: 16px; opacity: 0.9; }
                .ai-seo-pro-card { background: #fff; border-radius: 12px; padding: 30px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .ai-seo-pro-card h2 { margin: 0 0 20px 0; font-size: 20px; color: #333; }
                .ai-seo-pro-features { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 25px; }
                @media (max-width: 600px) { .ai-seo-pro-features { grid-template-columns: 1fr; } }
                .ai-seo-pro-feature { display: flex; align-items: flex-start; gap: 10px; padding: 10px; background: #f8f9fa; border-radius: 8px; }
                .ai-seo-pro-feature .icon { font-size: 20px; }
                .ai-seo-pro-feature .text { font-size: 14px; color: #333; }
                .ai-seo-pro-feature .text strong { display: block; margin-bottom: 3px; }
                .ai-seo-pro-feature .text span { color: #666; font-size: 13px; }
                .ai-seo-pro-comparison { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
                .ai-seo-pro-comparison th, .ai-seo-pro-comparison td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
                .ai-seo-pro-comparison th { background: #f8f9fa; font-weight: 600; }
                .ai-seo-pro-comparison .check { color: #22c55e; }
                .ai-seo-pro-comparison .cross { color: #ef4444; }
                .ai-seo-pro-cta { text-align: center; padding: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; }
                .ai-seo-pro-cta h3 { color: #fff; margin: 0 0 10px 0; font-size: 22px; }
                .ai-seo-pro-cta p { color: rgba(255,255,255,0.9); margin: 0 0 20px 0; }
                .ai-seo-pro-cta a { display: inline-block; background: #fff; color: #667eea; padding: 15px 40px; border-radius: 8px; font-size: 16px; font-weight: bold; text-decoration: none; transition: transform 0.2s, box-shadow 0.2s; }
                .ai-seo-pro-cta a:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0,0,0,0.2); }
                .ai-seo-pro-prices { display: flex; justify-content: center; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
                .ai-seo-pro-price { background: rgba(255,255,255,0.1); padding: 15px 20px; border-radius: 8px; text-align: center; }
                .ai-seo-pro-price .value { font-size: 24px; font-weight: bold; color: #fff; }
                .ai-seo-pro-price .period { font-size: 12px; color: rgba(255,255,255,0.8); }
            </style>
            
            <div class="ai-seo-pro-wrap">
                <div class="ai-seo-pro-header">
                    <h1>🚀 AI SEO Assistant PRO</h1>
                    <p>Automatize completamente o SEO do seu WordPress com Inteligência Artificial</p>
                </div>
                
                <div class="ai-seo-pro-card">
                    <h2>✨ Recursos Exclusivos da Versão PRO</h2>
                    <div class="ai-seo-pro-features">
                        <div class="ai-seo-pro-feature">
                            <span class="icon">🎯</span>
                            <div class="text">
                                <strong>Focus Keyword Automática</strong>
                                <span>IA sugere a melhor palavra-chave para seu conteúdo</span>
                            </div>
                        </div>
                        <div class="ai-seo-pro-feature">
                            <span class="icon">📝</span>
                            <div class="text">
                                <strong>Title SEO Otimizado</strong>
                                <span>Títulos persuasivos gerados automaticamente</span>
                            </div>
                        </div>
                        <div class="ai-seo-pro-feature">
                            <span class="icon">📄</span>
                            <div class="text">
                                <strong>Meta Description</strong>
                                <span>Descrições que aumentam o CTR nos resultados</span>
                            </div>
                        </div>
                        <div class="ai-seo-pro-feature">
                            <span class="icon">⚡</span>
                            <div class="text">
                                <strong>Auto-aplicar ao Publicar</strong>
                                <span>SEO preenchido automaticamente ao salvar posts</span>
                            </div>
                        </div>
                        <div class="ai-seo-pro-feature">
                            <span class="icon">📱</span>
                            <div class="text">
                                <strong>Open Graph</strong>
                                <span>Otimização para Facebook, Twitter e LinkedIn</span>
                            </div>
                        </div>
                        <div class="ai-seo-pro-feature">
                            <span class="icon">💡</span>
                            <div class="text">
                                <strong>Brief/Contexto</strong>
                                <span>Personalize o comportamento da IA para seu nicho</span>
                            </div>
                        </div>
                        <div class="ai-seo-pro-feature">
                            <span class="icon">🔄</span>
                            <div class="text">
                                <strong>Atualizações Automáticas</strong>
                                <span>Receba novas funcionalidades automaticamente</span>
                            </div>
                        </div>
                        <div class="ai-seo-pro-feature">
                            <span class="icon">💬</span>
                            <div class="text">
                                <strong>Suporte Prioritário</strong>
                                <span>Atendimento rápido por email</span>
                            </div>
                        </div>
                    </div>
                    
                    <h2>📊 Comparativo FREE vs PRO</h2>
                    <table class="ai-seo-pro-comparison">
                        <thead>
                            <tr>
                                <th>Recurso</th>
                                <th>FREE</th>
                                <th>PRO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Geração de Title SEO</td>
                                <td><span class="check">✅</span> Limitado</td>
                                <td><span class="check">✅</span> Ilimitado</td>
                            </tr>
                            <tr>
                                <td>Geração de Description</td>
                                <td><span class="check">✅</span> Limitado</td>
                                <td><span class="check">✅</span> Ilimitado</td>
                            </tr>
                            <tr>
                                <td>Focus Keyword Automática</td>
                                <td><span class="cross">❌</span></td>
                                <td><span class="check">✅</span></td>
                            </tr>
                            <tr>
                                <td>Auto-aplicar ao Publicar</td>
                                <td><span class="cross">❌</span></td>
                                <td><span class="check">✅</span></td>
                            </tr>
                            <tr>
                                <td>Open Graph (Redes Sociais)</td>
                                <td><span class="cross">❌</span></td>
                                <td><span class="check">✅</span></td>
                            </tr>
                            <tr>
                                <td>Brief/Contexto Personalizado</td>
                                <td><span class="cross">❌</span></td>
                                <td><span class="check">✅</span></td>
                            </tr>
                            <tr>
                                <td>Suporte</td>
                                <td>Comunidade</td>
                                <td><span class="check">✅</span> Prioritário</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="ai-seo-pro-cta">
                    <h3>🎉 Experimente o AI SEO PRO</h3>
                    <p>Economize horas de trabalho manual em SEO. Deixe a IA fazer o trabalho pesado!</p>
                    
                    <div class="ai-seo-pro-prices">
                        <div class="ai-seo-pro-price">
                            <div class="value">R$ 29,90</div>
                            <div class="period">30 dias</div>
                        </div>
                        <div class="ai-seo-pro-price">
                            <div class="value">R$ 297</div>
                            <div class="period">1 ano</div>
                        </div>
                        <div class="ai-seo-pro-price">
                            <div class="value">R$ 497</div>
                            <div class="period">Vitalício</div>
                        </div>
                    </div>
                    
                    <a href="<?php echo esc_url($this->sales_url); ?>" target="_blank">
                        🚀 Obter AI SEO PRO
                    </a>
                    
                    <p style="margin-top:20px; font-size:12px; opacity:0.8;">
                        🛡️ Garantia de 7 dias • 🔒 Pagamento seguro via PIX, Boleto ou Cartão
                    </p>
                </div>
            </div>
        </div>
        <?php
    }
}

add_action('plugins_loaded', function() { 
    if (defined('AI_SEO_RM_BASENAME')) {
        AI_SEO_RM_Upgrade::instance(); 
    }
});
