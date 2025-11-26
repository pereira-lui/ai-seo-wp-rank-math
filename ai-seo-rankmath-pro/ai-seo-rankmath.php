<?php
/**
 * Plugin Name: AI SEO Assistant for Rank Math PRO
 * Plugin URI: https://github.com/pereira-lui/ai-seo-wp-rank-math
 * Update URI: https://github.com/pereira-lui/ai-seo-wp-rank-math
 * Description: Preenche automaticamente os campos do Rank Math SEO usando Inteligência Artificial (ChatGPT). Versão PRO com todas as funcionalidades.
 * Version: 2.1.0
 * Author: Lui
 * Author URI: https://github.com/pereira-lui
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-seo-rankmath
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * GitHub Plugin URI: pereira-lui/ai-seo-wp-rank-math
 * Primary Branch: main
 * 
 * @package AI_SEO_RankMath_Pro
 */

if (!defined('ABSPATH')) exit;

// === Plugin Constants ========================================================
define('AI_SEO_RM_VERSION', '2.1.0');
define('AI_SEO_RM_PLUGIN_FILE', __FILE__);
define('AI_SEO_RM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_SEO_RM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_SEO_RM_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('AI_SEO_RM_IS_PRO', true);

// === Load Classes ============================================================
require_once AI_SEO_RM_PLUGIN_DIR . 'includes/class-license-manager.php';
require_once AI_SEO_RM_PLUGIN_DIR . 'includes/class-asaas-integration.php';
require_once AI_SEO_RM_PLUGIN_DIR . 'includes/purchase-page.php';

// === Updater embutido (GitHub Releases) ======================================
require_once AI_SEO_RM_PLUGIN_DIR . 'vendor/mini-puc/mini-puc.php';

add_action('plugins_loaded', function(){
    // Inicializa o updater (usa Releases do GitHub)
    new MiniPUC_GitHubUpdater(__FILE__, 'ai-seo-rankmath-pro', 'pereira-lui/ai-seo-wp-rank-math', 'main');
    
    // Inicializa o gerenciador de licenças
    ai_seo_rm_license();
    
    // Inicializa integração com Asaas
    ai_seo_rm_asaas();
    
    // Load text domain para traduções
    load_plugin_textdomain('ai-seo-rankmath', false, dirname(AI_SEO_RM_PLUGIN_BASENAME) . '/languages');
});

// ------- Helpers: API Key retrieval & normalization -------
function ai_seo_rm_raw_key() {
    if (defined('OPENAI_API_KEY') && OPENAI_API_KEY) return OPENAI_API_KEY;
    return get_option('ai_seo_rm_api_key', '');
}

function ai_seo_rm_normalize_key($key){
    if (!is_string($key)) return '';
    $key = preg_replace('/[\x{200B}-\x{200D}\x{2060}\x{FEFF}\x{00A0}]/u', '', $key);
    $key = trim($key);
    if ((substr($key,0,1)=='"' && substr($key,-1)=='"') || (substr($key,0,1)=="'" && substr($key,-1)=="'")) {
        $key = substr($key, 1, -1);
    }
    if (strpos($key, 'sk-') !== false) {
        if (preg_match('/(sk-[A-Za-z0-9_\-\.]+)/', $key, $m)) {
            $key = $m[1];
        } else {
            $pos = strpos($key, 'sk-');
            $tail = substr($key, $pos);
            $parts = preg_split('/\s/', $tail);
            $key = $parts[0];
        }
    }
    if (stripos($key, 'Bearer ') === 0) {
        $key = trim(substr($key, 7));
    }
    return $key;
}

function ai_seo_rm_get_api_key() {
    $raw = ai_seo_rm_raw_key();
    return ai_seo_rm_normalize_key($raw);
}

function ai_seo_rm_mask_key($key){
    if (!$key) return '';
    $len = strlen($key);
    if ($len <= 10) return str_repeat('*', $len);
    return substr($key,0,6) . str_repeat('*', max(0,$len-10)) . substr($key,-4);
}

function ai_seo_rm_key_source(){
    if (defined('OPENAI_API_KEY') && OPENAI_API_KEY) return 'constant';
    if (get_option('ai_seo_rm_api_key', '')) return 'option';
    return 'none';
}

// ------- Utilities: robust JSON extraction -------
function ai_seo_rm_extract_json($text){
    if (!is_string($text)) return null;
    $data = json_decode($text, true);
    if (is_array($data)) return $data;

    $text2 = trim($text);
    $text2 = preg_replace('/^```(?:json)?\s*/i', '', $text2);
    $text2 = preg_replace('/\s*```$/', '', $text2);
    $data = json_decode($text2, true);
    if (is_array($data)) return $data;

    $start = strpos($text, '{'); $end = strrpos($text, '}');
    if ($start !== false && $end !== false && $end > $start) {
        $slice = substr($text, $start, $end - $start + 1);
        $data = json_decode($slice, true);
        if (is_array($data)) return $data;
    }
    return null;
}

// ------- Settings Page (com brief de SEO e Licença) -------
add_action('admin_menu', function() {
    // Página principal de configurações
    add_options_page(
        'AI SEO (Rank Math)',
        'AI SEO (Rank Math)',
        'manage_options',
        'ai-seo-rankmath',
        'ai_seo_rm_settings_page'
    );
    
    // Subpágina de compra (aparece no menu também)
    add_submenu_page(
        'options-general.php',
        'Comprar Licença - AI SEO',
        '',  // Esconde do menu
        'manage_options',
        'ai-seo-rankmath-purchase',
        'ai_seo_rm_purchase_page_wrapper'
    );
});

function ai_seo_rm_purchase_page_wrapper() {
    echo '<div class="wrap">';
    ai_seo_rm_render_purchase_page();
    echo '</div>';
}

function ai_seo_rm_settings_page() {
    if (!current_user_can('manage_options')) return;

    $license_manager = ai_seo_rm_license();
    $license_message = '';
    $license_message_type = '';

    // Handle license activation/deactivation
    if (isset($_POST['ai_seo_rm_activate_license']) && check_admin_referer('ai_seo_rm_license_action')) {
        $license_key = sanitize_text_field($_POST['ai_seo_rm_license_key_input'] ?? '');
        $result = $license_manager->activate_license($license_key);
        $license_message = $result['message'];
        $license_message_type = $result['success'] ? 'success' : 'error';
    }

    if (isset($_POST['ai_seo_rm_deactivate_license']) && check_admin_referer('ai_seo_rm_license_action')) {
        $result = $license_manager->deactivate_license();
        $license_message = $result['message'];
        $license_message_type = $result['success'] ? 'success' : 'error';
    }

    // Handle settings save
    if (isset($_POST['ai_seo_rm_api_key']) && check_admin_referer('ai_seo_rm_save_settings')) {
        update_option('ai_seo_rm_api_key', sanitize_text_field($_POST['ai_seo_rm_api_key']));
        if (isset($_POST['ai_seo_rm_seo_brief'])) {
            update_option('ai_seo_rm_seo_brief', sanitize_textarea_field($_POST['ai_seo_rm_seo_brief']));
        }
        echo '<div class="updated"><p>Configurações salvas.</p></div>';
    }

    $raw_key  = ai_seo_rm_raw_key();
    $active   = ai_seo_rm_get_api_key();
    $src      = ai_seo_rm_key_source();
    $mask     = ai_seo_rm_mask_key($active);
    $using_const = $src === 'constant';
    $hint = '';
    if ($raw_key && $raw_key !== $active) {
        $hint = 'Detectamos texto extra na chave; usando token extraído automaticamente.';
    }
    $seo_brief = get_option('ai_seo_rm_seo_brief', '');
    
    // License info
    $license_info = $license_manager->get_license_info();
    $can_use = $license_manager->can_use_plugin();
    ?>
    <div class="wrap">
        <h1>🤖 AI SEO Assistant for Rank Math <small style="font-size:12px; color:#666;">v<?php echo AI_SEO_RM_VERSION; ?></small></h1>
        
        <!-- License Section -->
        <div style="background:#fff; border:1px solid #ccd0d4; border-left:4px solid <?php echo $license_info['is_active'] ? '#00a32a' : ($license_info['is_trial'] ? '#dba617' : '#d63638'); ?>; padding:15px 20px; margin:20px 0;">
            <h2 style="margin-top:0;">🔐 Licença</h2>
            
            <?php if ($license_message): ?>
                <div class="notice notice-<?php echo $license_message_type; ?> inline" style="margin:10px 0;">
                    <p><?php echo esc_html($license_message); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($license_info['is_active']): ?>
                <p style="color:#00a32a; font-weight:bold;">✅ Licença Ativa</p>
                <table class="form-table" style="margin:0;">
                    <tr><th>Status:</th><td><span style="color:#00a32a; font-weight:bold;"><?php echo esc_html($license_info['status_label']); ?></span></td></tr>
                    <tr><th>Chave:</th><td><code><?php echo esc_html($license_info['key_masked']); ?></code></td></tr>
                    <?php if ($license_info['expires']): ?>
                    <tr><th>Expira em:</th><td><?php echo esc_html($license_info['expires']); ?></td></tr>
                    <?php endif; ?>
                    <tr><th>Ativações:</th><td><?php echo esc_html($license_info['activations'] . '/' . $license_info['activations_limit']); ?></td></tr>
                </table>
                <form method="post" style="margin-top:15px;">
                    <?php wp_nonce_field('ai_seo_rm_license_action'); ?>
                    <button type="submit" name="ai_seo_rm_deactivate_license" class="button">Desativar Licença</button>
                </form>
            <?php elseif ($license_info['is_trial']): ?>
                <p style="color:#dba617; font-weight:bold;">⏳ Modo Trial - <?php echo $license_info['trial_days']; ?> dia(s) restante(s)</p>
                <p>Você está usando o período de testes gratuito. Adquira uma licença para uso contínuo.</p>
                <p style="margin-top:15px;">
                    <a href="<?php echo admin_url('options-general.php?page=ai-seo-rankmath-purchase'); ?>" class="button button-primary">🛒 Comprar Licença</a>
                </p>
                <hr style="margin:20px 0;">
                <p><strong>Já tem uma licença?</strong> Ative abaixo:</p>
                <form method="post">
                    <?php wp_nonce_field('ai_seo_rm_license_action'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="ai_seo_rm_license_key_input">Chave de Licença:</label></th>
                            <td>
                                <input type="text" id="ai_seo_rm_license_key_input" name="ai_seo_rm_license_key_input" class="regular-text" placeholder="AISEO-XXXX-XXXX-XXXX">
                                <button type="submit" name="ai_seo_rm_activate_license" class="button button-primary">Ativar Licença</button>
                            </td>
                        </tr>
                    </table>
                </form>
            <?php else: ?>
                <p style="color:#d63638; font-weight:bold;">❌ Licença Inativa - Período de trial expirado</p>
                <p>Para continuar usando o plugin, adquira uma licença.</p>
                <p style="margin-top:15px;">
                    <a href="<?php echo admin_url('options-general.php?page=ai-seo-rankmath-purchase'); ?>" class="button button-primary button-hero">🛒 Comprar Licença Agora</a>
                </p>
                <hr style="margin:20px 0;">
                <p><strong>Já tem uma licença?</strong> Ative abaixo:</p>
                <form method="post">
                    <?php wp_nonce_field('ai_seo_rm_license_action'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="ai_seo_rm_license_key_input">Chave de Licença:</label></th>
                            <td>
                                <input type="text" id="ai_seo_rm_license_key_input" name="ai_seo_rm_license_key_input" class="regular-text" placeholder="AISEO-XXXX-XXXX-XXXX">
                                <button type="submit" name="ai_seo_rm_activate_license" class="button button-primary">Ativar Licença</button>
                            </td>
                        </tr>
                    </table>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($can_use): ?>
        <!-- API Settings Section -->
        <div style="background:#fff; border:1px solid #ccd0d4; padding:15px 20px; margin:20px 0;">
            <h2 style="margin-top:0;">⚙️ Configurações da API</h2>
            <p>Informe sua chave da OpenAI ou defina no wp-config.php como <code>define('OPENAI_API_KEY','sk-...');</code>.</p>
            <p><strong>Fonte ativa:</strong> <?php echo esc_html(strtoupper($src)); ?><?php if($mask){ echo ' — <code>'.$mask.'</code>'; } ?><?php if($hint){ echo '<br><em style="color:#cc0000">'.$hint.'</em>'; } ?></p>
            <?php if ($using_const): ?>
                <p style="color:#cc0000"><strong>Aviso:</strong> Como OPENAI_API_KEY está definida no <code>wp-config.php</code>, a chave abaixo (opção) será ignorada.</p>
            <?php endif; ?>
            <form method="post">
                <?php wp_nonce_field('ai_seo_rm_save_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="ai_seo_rm_api_key">OpenAI API Key (opção)</label></th>
                        <td>
                            <input type="password" id="ai_seo_rm_api_key" name="ai_seo_rm_api_key" class="regular-text" value="<?php echo esc_attr(get_option('ai_seo_rm_api_key','')); ?>" placeholder="sk-...">
                            <p class="description">Cole apenas o token. O plugin extrai automaticamente o primeiro <code>sk-...</code> se vier com texto extra.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ai_seo_rm_seo_brief">Brief/Contexto inicial de SEO</label></th>
                        <td>
                            <textarea id="ai_seo_rm_seo_brief" name="ai_seo_rm_seo_brief" class="large-text" rows="4" placeholder="Ex.: Foco: scooters elétricas em Passo Fundo, com atendimento no Brasil inteiro."><?php echo esc_textarea($seo_brief); ?></textarea>
                            <p class="description">Guia global para títulos/descrições/palavra foco gerados pela IA.</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary">Salvar Configurações</button>
                    <button type="button" id="ai-seo-test-key" class="button">Testar chave agora</button>
                </p>
                <div id="ai-seo-test-result" style="margin-top:10px;"></div>
            </form>
        </div>
        <?php else: ?>
        <div style="background:#fff3cd; border:1px solid #ffc107; padding:15px 20px; margin:20px 0;">
            <p><strong>⚠️ Ative uma licença para acessar as configurações e funcionalidades do plugin.</strong></p>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

// AJAX: test key
add_action('wp_ajax_ai_seo_rm_test_key', function(){
    if (!current_user_can('manage_options')) wp_send_json_error(['message'=>'Sem permissão']);
    check_ajax_referer('ai_seo_rm_ajax', 'nonce');
    $key = ai_seo_rm_get_api_key();
    if (!$key) wp_send_json_error(['message'=>'Nenhuma chave configurada.']);

    $resp = wp_remote_get('https://api.openai.com/v1/models', [
        'headers'=>['Authorization' => 'Bearer '.$key],
        'timeout'=>20
    ]);
    if (is_wp_error($resp)) {
        wp_send_json_error(['message'=>'Erro: '.$resp->get_error_message()]);
    }
    $code = wp_remote_retrieve_response_code($resp);
    $body = wp_remote_retrieve_body($resp);
    if ($code === 200) {
        wp_send_json_success(['message'=>'Chave válida ✅']);
    } else {
        $data = json_decode($body, true);
        $err  = isset($data['error']['message']) ? $data['error']['message'] : 'HTTP '.$code;
        wp_send_json_error(['message'=>'Falha ❌: '.$err, 'http_code'=>$code, 'preview'=>mb_substr($body,0,500)]);
    }
});

// ------- Metabox -------
add_action('add_meta_boxes', function() {
    // Verifica se pode usar o plugin
    if (!ai_seo_rm_license()->can_use_plugin()) return;
    
    foreach (['post','page'] as $scr) {
        add_meta_box('ai_seo_rm_box','AI SEO (Rank Math)','ai_seo_rm_metabox_cb',$scr,'side','high');
    }
});

function ai_seo_rm_metabox_cb($post) {
    wp_nonce_field('ai_seo_rm_nonce', 'ai_seo_rm_nonce_field');
    $auto = get_post_meta($post->ID, '_ai_seo_rm_auto_apply', true);
    ?>
    <div id="ai-seo-rm-box">
        <p>Analisa a <strong>página renderizada</strong> e preenche Rank Math (Title, Description, Focus) quando vazios.</p>
        <p><label><input type="checkbox" id="ai-seo-rm-auto" <?php checked($auto, '1'); ?>/> Aplicar automaticamente</label></p>
        <p><button type="button" class="button button-primary" id="ai-seo-rm-run">Analisar página e preencher</button></p>
        <div id="ai-seo-rm-result" style="margin-top:10px; max-height:220px; overflow:auto; background:#fff; border:1px solid #ccd0d4; padding:8px;"></div>
    </div>
    <?php
}

// Save auto apply flag
add_action('save_post', function($post_id){
    if (isset($_POST['ai_seo_rm_nonce_field']) && wp_verify_nonce($_POST['ai_seo_rm_nonce_field'], 'ai_seo_rm_nonce')) {
        $auto = isset($_POST['ai-seo-rm-auto-hidden']) && $_POST['ai-seo-rm-auto-hidden'] === '1' ? '1' : '';
        if ($auto) update_post_meta($post_id, '_ai_seo_rm_auto_apply', '1');
        else delete_post_meta($post_id, '_ai_seo_rm_auto_apply');
    }
});

// Enqueue JS
add_action('admin_enqueue_scripts', function($hook){
    if (!in_array($hook, ['settings_page_ai-seo-rankmath','post.php','post-new.php'])) return;
    wp_enqueue_script('ai-seo-rm-js', AI_SEO_RM_PLUGIN_URL.'admin.js', ['jquery'], AI_SEO_RM_VERSION, true);
    wp_localize_script('ai-seo-rm-js', 'AISEO_RM', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('ai_seo_rm_ajax'),
        'version' => AI_SEO_RM_VERSION,
        'can_use' => ai_seo_rm_license()->can_use_plugin(),
    ]);
});

// ------- Prompt helper (injeta Brief) -------
function ai_seo_rm_build_prompt($analysis, $text_content){
    $locale = get_locale(); $locale = $locale ? $locale : 'pt_BR';
    $brief  = trim(get_option('ai_seo_rm_seo_brief',''));

    $rules = "Regras:\n".
             "- Use PT-BR natural e termos do nicho.\n".
             "- Retorne SOMENTE JSON.\n".
             "- title <= 60 chars; description <= 160 chars (com CTA).\n".
             "- slug em kebab-case curto (sem acentos).\n";

    $prompt  = "";
    if ($brief) {
        $prompt .= "Brief/Contexto global do site (seguir quando fizer sentido): ".$brief."\n\n";
    }
    $prompt .= "Atue como especialista de SEO para WordPress (Rank Math) em {$locale}.\n".
               "Analise o resumo técnico e gere JSON com: focus_keyword, title, description, slug, og_title (opc), og_description (opc), suggestions.\n\n".
               "Resumo técnico:\n". json_encode($analysis, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) ."\n\n".
               "Texto base (HTML removido):\n\"\"\"\n{$text_content}\n\"\"\"\n\n".$rules;

    return $prompt;
}

// ------- AJAX Handler (analyze/fill) -------
add_action('wp_ajax_ai_seo_rm_analyze_fill', function(){
    if (!current_user_can('edit_posts')) wp_send_json_error(['message'=>'Permissão negada.']);
    check_ajax_referer('ai_seo_rm_ajax', 'nonce');

    // Verifica licença
    if (!ai_seo_rm_license()->can_use_plugin()) {
        wp_send_json_error(['message'=>'Licença inativa ou trial expirado. Ative uma licença em Configurações → AI SEO.']);
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $apply   = isset($_POST['apply']) ? boolval($_POST['apply']) : true;
    $post    = get_post($post_id);
    if (!$post) wp_send_json_error(['message'=>'Post inválido.']);

    $api_key = ai_seo_rm_get_api_key();
    if (!$api_key) wp_send_json_error(['message'=>'Defina sua OpenAI API Key em Configurações > AI SEO.']);

    $permalink = get_permalink($post_id);
    $html = '';
    if ($permalink) {
        $res = wp_remote_get($permalink, ['timeout'=>20, 'redirection'=>3, 'sslverify'=>false]);
        if (!is_wp_error($res)) $html = wp_remote_retrieve_body($res);
    }
    if (!$html) {
        $html = '<html><body><h1>'.esc_html(get_the_title($post_id)).'</h1>'.apply_filters('the_content', $post->post_content).'</body></html>';
    }

    $analysis = ai_seo_rm_analyze_html($html, $post_id);

    $text_content = wp_strip_all_tags($html);
    if (mb_strlen($text_content) > 7000) $text_content = mb_substr($text_content, 0, 7000);

    $prompt_user = ai_seo_rm_build_prompt($analysis, $text_content);

    $body = [
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role'=>'system','content'=>'Você retorna apenas JSON válido.'],
            ['role'=>'user','content'=>$prompt_user]
        ],
        'temperature' => 0.2,
        'max_tokens' => 500,
        'response_format' => ['type'=>'json_object']
    ];

    $resp = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers'=>[
            'Authorization' => 'Bearer '.$api_key,
            'Content-Type'  => 'application/json'
        ],
        'body' => wp_json_encode($body),
        'timeout'=> 60
    ]);

    if (is_wp_error($resp)) {
        wp_send_json_error(['message'=>'Erro na chamada OpenAI: '.$resp->get_error_message(), 'analysis'=>$analysis]);
    }

    $http_code = wp_remote_retrieve_response_code($resp);
    $raw_body  = wp_remote_retrieve_body($resp);

    if ($http_code === 401) {
        $data_ = json_decode($raw_body, true);
        $errm  = isset($data_['error']['message']) ? $data_['error']['message'] : 'API key inválida.';
        wp_send_json_error(['message'=>'401 Unauthorized: '.$errm.' Verifique sua chave em Configurações → AI SEO.', 'http_code'=>401]);
    }

    $json = json_decode($raw_body, true);
    $content = isset($json['choices'][0]['message']['content']) ? $json['choices'][0]['message']['content'] : '';

    $data = ai_seo_rm_extract_json($content);

    if (!is_array($data)) {
        wp_send_json_error([
            'message'=>'Resposta da IA inválida (não JSON).',
            'http_code'=> $http_code,
            'raw_preview'=> mb_substr($content ? $content : $raw_body, 0, 800),
            'analysis'=>$analysis
        ]);
    }

    $updates = [];
    $has_title = get_post_meta($post_id, 'rank_math_title', true);
    $has_desc  = get_post_meta($post_id, 'rank_math_description', true);

    if ($apply) {
        if (!$has_title && !empty($data['title'])) {
            update_post_meta($post_id, 'rank_math_title', wp_strip_all_tags($data['title']));
            $updates['rank_math_title'] = $data['title'];
        }
        if (!$has_desc && !empty($data['description'])) {
            update_post_meta($post_id, 'rank_math_description', wp_strip_all_tags($data['description']));
            $updates['rank_math_description'] = $data['description'];
        }
        if (!empty($data['focus_keyword'])) {
            $fk = is_array($data['focus_keyword']) ? implode(', ', array_map('sanitize_text_field',$data['focus_keyword'])) : sanitize_text_field($data['focus_keyword']);
            update_post_meta($post_id, 'rank_math_focus_keyword', $fk);
            $updates['rank_math_focus_keyword'] = $fk;
        }
        if (!empty($data['og_title']) && !get_post_meta($post_id, 'rank_math_facebook_title', true)) {
            update_post_meta($post_id, 'rank_math_facebook_title', wp_strip_all_tags($data['og_title']));
            update_post_meta($post_id, 'rank_math_twitter_title', wp_strip_all_tags($data['og_title']));
        }
        if (!empty($data['og_description']) && !get_post_meta($post_id, 'rank_math_facebook_description', true)) {
            update_post_meta($post_id, 'rank_math_facebook_description', wp_strip_all_tags($data['og_description']));
            update_post_meta($post_id, 'rank_math_twitter_description', wp_strip_all_tags($data['og_description']));
        }
    }

    wp_send_json_success([
        'analysis'    => $analysis,
        'ai'          => $data,
        'applied'     => $apply ? $updates : new stdClass(),
        'message'     => 'Análise concluída' . ($apply ? ' e dados aplicados (quando vazios).' : '.')
    ]);
});

// ------- Auto-apply on publish/update if flagged -------
add_action('save_post', function($post_id, $post){
    if (wp_is_post_revision($post_id)) return;
    
    // Verifica licença
    if (!ai_seo_rm_license()->can_use_plugin()) return;
    
    $auto = get_post_meta($post_id, '_ai_seo_rm_auto_apply', true);
    if (!$auto) return;
    if ('publish' !== get_post_status($post_id)) return;

    $api_key = ai_seo_rm_get_api_key();
    if (!$api_key) return;

    $permalink = get_permalink($post_id);
    $html = '';
    if ($permalink) {
        $res = wp_remote_get($permalink, ['timeout'=>20, 'redirection'=>3, 'sslverify'=>false]);
        if (!is_wp_error($res)) $html = wp_remote_retrieve_body($res);
    }
    if (!$html) {
        $html = '<html><body><h1>'.esc_html(get_the_title($post_id)).'</h1>'.apply_filters('the_content', $post->post_content).'</body></html>';
    }
    $analysis = ai_seo_rm_analyze_html($html, $post_id);
    $text_content = wp_strip_all_tags($html);
    if (mb_strlen($text_content) > 7000) $text_content = mb_substr($text_content, 0, 7000);

    $prompt_user = ai_seo_rm_build_prompt($analysis, $text_content);

    $body = [
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role'=>'system','content'=>'Você retorna apenas JSON válido.'],
            ['role'=>'user','content'=>$prompt_user]
        ],
        'temperature' => 0.2,
        'max_tokens' => 450,
        'response_format' => ['type'=>'json_object']
    ];
    $resp = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers'=>[ 'Authorization' => 'Bearer '.$api_key, 'Content-Type'  => 'application/json' ],
        'body' => wp_json_encode($body),'timeout'=> 60
    ]);
    if (is_wp_error($resp)) return;
    $raw = wp_remote_retrieve_body($resp);
    $json = json_decode($raw, true);
    $content = $json['choices'][0]['message']['content'] ?? '';
    $data = ai_seo_rm_extract_json($content);
    if (!is_array($data)) return;

    $has_title = get_post_meta($post_id, 'rank_math_title', true);
    $has_desc  = get_post_meta($post_id, 'rank_math_description', true);

    if (!$has_title && !empty($data['title'])) {
        update_post_meta($post_id, 'rank_math_title', wp_strip_all_tags($data['title']));
    }
    if (!$has_desc && !empty($data['description'])) {
        update_post_meta($post_id, 'rank_math_description', wp_strip_all_tags($data['description']));
    }
    if (!empty($data['focus_keyword'])) {
        $fk = is_array($data['focus_keyword']) ? implode(', ', array_map('sanitize_text_field',$data['focus_keyword'])) : sanitize_text_field($data['focus_keyword']);
        update_post_meta($post_id, 'rank_math_focus_keyword', $fk);
    }
}, 20, 2);

// ------- Analyzer -------
function ai_seo_rm_analyze_html($html, $post_id=0){
    $report = [
        'post_id' => $post_id,
        'permalink' => $post_id ? get_permalink($post_id) : '',
        'word_count' => 0,
        'h1_count' => 0,
        'h2_count' => 0,
        'images_total' => 0,
        'images_missing_alt' => 0,
        'links_internal' => 0,
        'links_external' => 0,
        'has_ld_json' => false,
        'title_tag' => '',
        'meta_description' => ''
    ];

    if (preg_match('/<title>(.*?)<\/title>/is', $html, $m)) { $report['title_tag'] = wp_strip_all_tags($m[1]); }
    if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/is', $html, $m2)) { $report['meta_description'] = wp_strip_all_tags($m2[1]); }

    $text = wp_strip_all_tags($html);
    $report['word_count'] = str_word_count($text, 0, 'ÁÀÂÃÉÈÊÍÌÎÓÒÔÕÚÙÛáàâãéèêíìîóòôõúùûçÇ');
    
    if (class_exists('DOMDocument')) {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        if ($loaded) {
            $xpath = new DOMXPath($dom);
            
            $h1s = $xpath->query('//h1');
            $report['h1_count'] = $h1s ? $h1s->length : 0;
            
            $h2s = $xpath->query('//h2');
            $report['h2_count'] = $h2s ? $h2s->length : 0;

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

            $links = $xpath->query('//a[@href]');
            $internal = 0; $external = 0;
            $home = home_url();
            if ($links) {
                foreach ($links as $a) {
                    $href = $a->getAttribute('href');
                    if (strpos($href, $home) === 0 || (isset($href[0]) && $href[0] == '/')) $internal++;
                    else $external++;
                }
            }
            $report['links_internal'] = $internal;
            $report['links_external'] = $external;

            $ldjson = $xpath->query('//script[@type="application/ld+json"]');
            $report['has_ld_json'] = $ldjson && $ldjson->length > 0;
        }
        libxml_clear_errors();
    }

    $report['rank_math'] = [
        'title'       => $post_id ? get_post_meta($post_id, 'rank_math_title', true) : '',
        'description' => $post_id ? get_post_meta($post_id, 'rank_math_description', true) : '',
        'focus_kw'    => $post_id ? get_post_meta($post_id, 'rank_math_focus_keyword', true) : ''
    ];

    $tips = [];
    if ($report['h1_count'] != 1) $tips[] = 'Use exatamente um H1 por página.';
    if ($report['word_count'] < 300) $tips[] = 'Conteúdo curto: considere ampliar para >= 600 palavras.';
    if ($report['images_missing_alt'] > 0) $tips[] = 'Imagens sem ALT: adicione descrições.';
    if (empty($report['rank_math']['title'])) $tips[] = 'Rank Math Title vazio.';
    if (empty($report['rank_math']['description'])) $tips[] = 'Rank Math Description vazio.';
    if (empty($report['rank_math']['focus_kw'])) $tips[] = 'Defina uma palavra-chave foco.';
    $report['quick_tips'] = $tips;

    return $report;
}

// --- Links GitHub na linha do plugin
add_filter('plugin_row_meta', function($links, $file){
    if (strpos($file, 'ai-seo-rankmath.php') !== false) {
        $links[] = '<a href="https://github.com/pereira-lui/ai-seo-wp-rank-math" target="_blank" rel="noopener">GitHub</a>';
        $links[] = '<a href="https://github.com/pereira-lui/ai-seo-wp-rank-math/releases" target="_blank" rel="noopener">Releases</a>';
    }
    return $links;
}, 10, 2);
