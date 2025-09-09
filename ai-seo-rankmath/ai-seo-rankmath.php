<?php
/*
Plugin Name: AI SEO Assistant for Rank Math
Plugin URI: https://github.com/pereira-lui/ai-seo-wp-rank-math
Update URI: https://github.com/pereira-lui/ai-seo-wp-rank-math
Description: Analisa a página (HTML renderizada) e preenche automaticamente os campos do Rank Math SEO usando OpenAI. Inclui um metabox no editor para Analisar & Preencher.
Version: 1.0.7
Author: Lui
License: GPLv2 or later
GitHub Plugin URI: pereira-lui/ai-seo-wp-rank-math
Primary Branch: main
*/

if (!defined('ABSPATH')) exit;

// === Option B: Updater embutido (GitHub Releases) ============================
require_once __DIR__ . '/vendor/mini-puc/mini-puc.php';
// Inicializa o updater para este plugin (usa Releases do GitHub)
// Observação: mantenha o asset ai-seo-rankmath.zip em cada Release.
add_action('plugins_loaded', function(){
    new MiniPUC_GitHubUpdater(__FILE__, 'ai-seo-rankmath', 'pereira-lui/ai-seo-wp-rank-math', 'main');
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

// ------- Settings Page (com brief de SEO) -------
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

    if (isset($_POST['ai_seo_rm_api_key']) && check_admin_referer('ai_seo_rm_save_settings')) {
        update_option('ai_seo_rm_api_key', sanitize_text_field($_POST['ai_seo_rm_api_key']));
        if (isset($_POST['ai_seo_rm_seo_brief'])) {
            update_option('ai_seo_rm_seo_brief', sanitize_textarea_field($_POST['ai_seo_rm_seo_brief']));
        }
        echo '<div class="updated"><p>Configurações salvas.</p></div>';
    }
    $raw_key  = ai_seo_rm_raw_key();
    $active   = ai_seo_rm_get_api_key(); // normalizada
    $src      = ai_seo_rm_key_source();
    $mask     = ai_seo_rm_mask_key($active);
    $using_const = $src === 'constant';
    $hint = '';
    if ($raw_key && $raw_key !== $active) {
        $hint = 'Detectamos texto extra na chave; usando token extraído automaticamente.';
    }
    $seo_brief = get_option('ai_seo_rm_seo_brief', '');
    ?>
    <div class="wrap">
        <h1>AI SEO Assistant (Rank Math)</h1>
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
                <button type="submit" class="button button-primary">Salvar</button>
                <button type="button" id="ai-seo-test-key" class="button">Testar chave agora</button>
            </p>
            <div id="ai-seo-test-result" style="margin-top:10px;"></div>
        </form>
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
    wp_enqueue_script('ai-seo-rm-js', plugin_dir_url(__FILE__).'admin.js', ['jquery'], '1.0.7', true);
    wp_localize_script('ai-seo-rm-js', 'AISEO_RM', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('ai_seo_rm_ajax'),
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
