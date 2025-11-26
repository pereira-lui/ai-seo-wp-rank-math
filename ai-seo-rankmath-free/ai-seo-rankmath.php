<?php
/**
 * Plugin Name: AI SEO Assistant for Rank Math
 * Plugin URI: https://github.com/pereira-lui/ai-seo-wp-rank-math
 * Description: Analisa suas páginas e mostra sugestões de SEO para o Rank Math. Versão gratuita com análise técnica completa. Upgrade para Pro para preenchimento automático com IA.
 * Version: 2.1.0
 * Author: Lui
 * Author URI: https://github.com/pereira-lui
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-seo-rankmath
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * 
 * @package AI_SEO_RankMath_Free
 */

if (!defined('ABSPATH')) exit;

// === Plugin Constants ========================================================
define('AI_SEO_RM_VERSION', '2.3.0');
define('AI_SEO_RM_PLUGIN_FILE', __FILE__);
define('AI_SEO_RM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_SEO_RM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_SEO_RM_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('AI_SEO_RM_IS_PRO', false);

// === Load Classes ============================================================
require_once AI_SEO_RM_PLUGIN_DIR . 'includes/class-upgrade.php';

// === Settings Page ===========================================================
add_action('admin_menu', function() {
    add_options_page(
        'AI SEO (Rank Math)',
        'AI SEO (Rank Math)',
        'manage_options',
        'ai-seo-rankmath',
        'ai_seo_rm_settings_page'
    );
});

function ai_seo_rm_settings_page() {
    if (!current_user_can('manage_options')) return;
    ?>
    <div class="wrap">
        <h1>🤖 AI SEO Assistant for Rank Math <small style="font-size:12px; color:#666;">v<?php echo AI_SEO_RM_VERSION; ?> FREE</small></h1>
        
        <!-- Upgrade Banner -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:#fff; padding:25px 30px; border-radius:12px; margin:20px 0;">
            <h2 style="margin:0 0 10px 0; color:#fff;">🚀 Upgrade para a Versão PRO</h2>
            <p style="margin:0 0 15px 0; font-size:15px; opacity:0.95;">
                Desbloqueie o preenchimento automático com Inteligência Artificial (ChatGPT)!
            </p>
            <ul style="margin:0 0 20px 0; padding-left:20px; opacity:0.9;">
                <li>✅ Preenchimento automático de Title, Description e Focus Keyword</li>
                <li>✅ Geração de sugestões com ChatGPT</li>
                <li>✅ Auto-aplicar ao publicar posts</li>
                <li>✅ Open Graph para redes sociais</li>
                <li>✅ Suporte prioritário</li>
            </ul>
            <a href="<?php echo admin_url('options-general.php?page=ai-seo-upgrade'); ?>" 
               style="display:inline-block; background:#fff; color:#667eea; padding:12px 25px; border-radius:6px; text-decoration:none; font-weight:bold;">
                🛒 Adquirir Versão PRO
            </a>
        </div>

        <!-- Funcionalidades da versão Free -->
        <div style="background:#fff; border:1px solid #ccd0d4; padding:20px; border-radius:8px; margin:20px 0;">
            <h2 style="margin-top:0;">📊 Funcionalidades da Versão Gratuita</h2>
            <table class="widefat" style="margin-top:15px;">
                <thead>
                    <tr>
                        <th>Funcionalidade</th>
                        <th style="text-align:center;">Free</th>
                        <th style="text-align:center;">Pro</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Análise técnica da página (H1, H2, palavras, links)</td>
                        <td style="text-align:center;">✅</td>
                        <td style="text-align:center;">✅</td>
                    </tr>
                    <tr>
                        <td>Detecção de imagens sem ALT</td>
                        <td style="text-align:center;">✅</td>
                        <td style="text-align:center;">✅</td>
                    </tr>
                    <tr>
                        <td>Verificação de Schema (JSON-LD)</td>
                        <td style="text-align:center;">✅</td>
                        <td style="text-align:center;">✅</td>
                    </tr>
                    <tr>
                        <td>Quick tips de SEO</td>
                        <td style="text-align:center;">✅</td>
                        <td style="text-align:center;">✅</td>
                    </tr>
                    <tr>
                        <td>Metabox no editor</td>
                        <td style="text-align:center;">✅</td>
                        <td style="text-align:center;">✅</td>
                    </tr>
                    <tr style="background:#f0f7fc;">
                        <td><strong>Geração de Title/Description com IA</strong></td>
                        <td style="text-align:center;">❌</td>
                        <td style="text-align:center;">✅</td>
                    </tr>
                    <tr style="background:#f0f7fc;">
                        <td><strong>Preenchimento automático do Rank Math</strong></td>
                        <td style="text-align:center;">❌</td>
                        <td style="text-align:center;">✅</td>
                    </tr>
                    <tr style="background:#f0f7fc;">
                        <td><strong>Auto-aplicar ao publicar</strong></td>
                        <td style="text-align:center;">❌</td>
                        <td style="text-align:center;">✅</td>
                    </tr>
                    <tr style="background:#f0f7fc;">
                        <td><strong>Open Graph (Facebook/Twitter)</strong></td>
                        <td style="text-align:center;">❌</td>
                        <td style="text-align:center;">✅</td>
                    </tr>
                    <tr style="background:#f0f7fc;">
                        <td><strong>Brief/Contexto de SEO</strong></td>
                        <td style="text-align:center;">❌</td>
                        <td style="text-align:center;">✅</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Como usar -->
        <div style="background:#fff; border:1px solid #ccd0d4; padding:20px; border-radius:8px; margin:20px 0;">
            <h2 style="margin-top:0;">📖 Como Usar</h2>
            <ol style="font-size:14px; line-height:1.8;">
                <li>Abra qualquer <strong>post ou página</strong> no editor</li>
                <li>Localize o metabox <strong>"AI SEO (Rank Math)"</strong> na sidebar direita</li>
                <li>Clique em <strong>"Analisar Página"</strong></li>
                <li>Veja a análise técnica e as sugestões de melhoria</li>
            </ol>
        </div>
    </div>
    <?php
}

// === Metabox =================================================================
add_action('add_meta_boxes', function() {
    foreach (['post', 'page'] as $scr) {
        add_meta_box(
            'ai_seo_rm_box',
            '🤖 AI SEO (Rank Math)',
            'ai_seo_rm_metabox_cb',
            $scr,
            'side',
            'high'
        );
    }
});

function ai_seo_rm_metabox_cb($post) {
    wp_nonce_field('ai_seo_rm_nonce', 'ai_seo_rm_nonce_field');
    ?>
    <div id="ai-seo-rm-box">
        <p style="margin-top:0;">Analisa a <strong>página renderizada</strong> e mostra sugestões de SEO.</p>
        
        <p>
            <button type="button" class="button button-primary" id="ai-seo-rm-analyze" style="width:100%;">
                🔍 Analisar Página
            </button>
        </p>
        
        <div id="ai-seo-rm-result" style="margin-top:10px; max-height:300px; overflow:auto; background:#fff; border:1px solid #ccd0d4; padding:10px; display:none;"></div>
        
        <!-- Upgrade CTA -->
        <div style="background:#f0f7fc; border:1px solid #c3d9ed; border-radius:6px; padding:12px; margin-top:15px; text-align:center;">
            <p style="margin:0 0 8px 0; font-size:12px; color:#1e3a5f;">
                <strong>🚀 Quer preenchimento automático?</strong>
            </p>
            <a href="<?php echo admin_url('options-general.php?page=ai-seo-upgrade'); ?>" 
               style="font-size:11px; color:#2271b1; text-decoration:none;">
                Upgrade para PRO →
            </a>
        </div>
    </div>
    <?php
}

// === Enqueue JS ==============================================================
add_action('admin_enqueue_scripts', function($hook) {
    if (!in_array($hook, ['post.php', 'post-new.php', 'settings_page_ai-seo-rankmath'])) return;
    
    wp_enqueue_script(
        'ai-seo-rm-js',
        AI_SEO_RM_PLUGIN_URL . 'admin.js',
        ['jquery'],
        AI_SEO_RM_VERSION,
        true
    );
    
    wp_localize_script('ai-seo-rm-js', 'AISEO_RM', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('ai_seo_rm_ajax'),
        'is_pro'  => false,
    ]);
});

// === AJAX: Analyze Page ======================================================
add_action('wp_ajax_ai_seo_rm_analyze', function() {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }
    check_ajax_referer('ai_seo_rm_ajax', 'nonce');

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $post = get_post($post_id);
    
    if (!$post) {
        wp_send_json_error(['message' => 'Post inválido.']);
    }

    // Busca HTML renderizado
    $permalink = get_permalink($post_id);
    $html = '';
    
    if ($permalink && get_post_status($post_id) === 'publish') {
        $res = wp_remote_get($permalink, [
            'timeout' => 20,
            'redirection' => 3,
            'sslverify' => false
        ]);
        if (!is_wp_error($res)) {
            $html = wp_remote_retrieve_body($res);
        }
    }
    
    if (!$html) {
        $html = '<html><body><h1>' . esc_html(get_the_title($post_id)) . '</h1>' . 
                apply_filters('the_content', $post->post_content) . '</body></html>';
    }

    // Analisa o HTML
    $analysis = ai_seo_rm_analyze_html($html, $post_id);

    wp_send_json_success([
        'analysis' => $analysis,
        'message'  => 'Análise concluída!'
    ]);
});

// === HTML Analyzer ===========================================================
function ai_seo_rm_analyze_html($html, $post_id = 0) {
    $report = [
        'post_id'            => $post_id,
        'permalink'          => $post_id ? get_permalink($post_id) : '',
        'word_count'         => 0,
        'h1_count'           => 0,
        'h2_count'           => 0,
        'images_total'       => 0,
        'images_missing_alt' => 0,
        'links_internal'     => 0,
        'links_external'     => 0,
        'has_ld_json'        => false,
        'title_tag'          => '',
        'meta_description'   => ''
    ];

    // Extrai title e meta description
    if (preg_match('/<title>(.*?)<\/title>/is', $html, $m)) {
        $report['title_tag'] = wp_strip_all_tags($m[1]);
    }
    if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/is', $html, $m2)) {
        $report['meta_description'] = wp_strip_all_tags($m2[1]);
    }

    // Contagem de palavras
    $text = wp_strip_all_tags($html);
    $report['word_count'] = str_word_count($text, 0, 'ÁÀÂÃÉÈÊÍÌÎÓÒÔÕÚÙÛáàâãéèêíìîóòôõúùûçÇ');

    // Análise com DOMDocument
    if (class_exists('DOMDocument')) {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        
        if ($loaded) {
            $xpath = new DOMXPath($dom);

            // H1 e H2
            $h1s = $xpath->query('//h1');
            $report['h1_count'] = $h1s ? $h1s->length : 0;

            $h2s = $xpath->query('//h2');
            $report['h2_count'] = $h2s ? $h2s->length : 0;

            // Imagens
            $imgs = $xpath->query('//img');
            $report['images_total'] = $imgs ? $imgs->length : 0;
            $missing = 0;
            if ($imgs) {
                foreach ($imgs as $img) {
                    $alt = $img->getAttribute('alt');
                    if ($alt === null || $alt === '') $missing++;
                }
            }
            $report['images_missing_alt'] = $missing;

            // Links
            $links = $xpath->query('//a[@href]');
            $internal = 0;
            $external = 0;
            $home = home_url();
            if ($links) {
                foreach ($links as $a) {
                    $href = $a->getAttribute('href');
                    if (strpos($href, $home) === 0 || (isset($href[0]) && $href[0] == '/')) {
                        $internal++;
                    } else {
                        $external++;
                    }
                }
            }
            $report['links_internal'] = $internal;
            $report['links_external'] = $external;

            // JSON-LD
            $ldjson = $xpath->query('//script[@type="application/ld+json"]');
            $report['has_ld_json'] = $ldjson && $ldjson->length > 0;
        }
        libxml_clear_errors();
    }

    // Dados do Rank Math
    $report['rank_math'] = [
        'title'       => $post_id ? get_post_meta($post_id, 'rank_math_title', true) : '',
        'description' => $post_id ? get_post_meta($post_id, 'rank_math_description', true) : '',
        'focus_kw'    => $post_id ? get_post_meta($post_id, 'rank_math_focus_keyword', true) : ''
    ];

    // Quick tips
    $tips = [];
    if ($report['h1_count'] == 0) {
        $tips[] = '❌ Nenhum H1 encontrado. Adicione um título principal.';
    } elseif ($report['h1_count'] > 1) {
        $tips[] = '⚠️ Múltiplos H1 (' . $report['h1_count'] . '). Use apenas um H1 por página.';
    } else {
        $tips[] = '✅ H1 correto (apenas 1).';
    }
    
    if ($report['word_count'] < 300) {
        $tips[] = '⚠️ Conteúdo curto (' . $report['word_count'] . ' palavras). Considere ampliar para 600+.';
    } else {
        $tips[] = '✅ Bom tamanho de conteúdo (' . $report['word_count'] . ' palavras).';
    }
    
    if ($report['images_missing_alt'] > 0) {
        $tips[] = '❌ ' . $report['images_missing_alt'] . ' imagem(ns) sem atributo ALT.';
    } elseif ($report['images_total'] > 0) {
        $tips[] = '✅ Todas as imagens têm ALT.';
    }
    
    if (empty($report['rank_math']['title'])) {
        $tips[] = '⚠️ Rank Math Title vazio.';
    }
    if (empty($report['rank_math']['description'])) {
        $tips[] = '⚠️ Rank Math Description vazio.';
    }
    if (empty($report['rank_math']['focus_kw'])) {
        $tips[] = '⚠️ Palavra-chave foco não definida.';
    }
    
    if (!$report['has_ld_json']) {
        $tips[] = '💡 Considere adicionar Schema (JSON-LD) para rich snippets.';
    } else {
        $tips[] = '✅ Schema (JSON-LD) detectado.';
    }

    $report['quick_tips'] = $tips;

    return $report;
}

// === Plugin Links ============================================================
add_filter('plugin_row_meta', function($links, $file) {
    if (strpos($file, 'ai-seo-rankmath.php') !== false) {
        $links[] = '<a href="' . admin_url('options-general.php?page=ai-seo-upgrade') . '" style="color:#2271b1; font-weight:bold;">🚀 Upgrade para PRO</a>';
    }
    return $links;
}, 10, 2);

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=ai-seo-rankmath') . '">Configurações</a>';
    $pro_link = '<a href="' . admin_url('options-general.php?page=ai-seo-upgrade') . '" style="color:#2271b1; font-weight:bold;">PRO</a>';
    array_unshift($links, $settings_link, $pro_link);
    return $links;
});
