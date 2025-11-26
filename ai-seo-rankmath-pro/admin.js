(function($){
    // Settings page: test key
    $(document).on('click', '#ai-seo-test-key', function(){
        var $res = $('#ai-seo-test-result');
        $res.text('Testando chave...');
        $.post(AISEO_RM.ajaxurl, {
            action: 'ai_seo_rm_test_key',
            nonce: AISEO_RM.nonce
        }).done(function(resp){
            if (resp && resp.success){
                $res.html('<span style="color:#2b8a3e;">'+ resp.data.message +'</span>');
            } else {
                var msg = resp && resp.data && resp.data.message ? resp.data.message : 'Falha';
                var code = resp && resp.data && resp.data.http_code ? ' (HTTP '+resp.data.http_code+')' : '';
                var prev = resp && resp.data && resp.data.preview ? '<pre style="white-space:pre-wrap">'+resp.data.preview+'</pre>' : '';
                $res.html('<span style="color:#b32d2e;">'+ msg + code +'</span>'+prev);
            }
        }).fail(function(){
            $res.html('<span style="color:#b32d2e;">Erro ao testar.</span>');
        });
    });

    // Função para atualizar campos do Rank Math na interface
    function updateRankMathFields(aiData) {
        // Verifica se o Rank Math está disponível (Gutenberg)
        if (typeof rankMathEditor !== 'undefined' && rankMathEditor.assessor) {
            // Rank Math no Gutenberg
            if (aiData.title) {
                if (typeof rankMathEditor.updateSerpTitle === 'function') {
                    rankMathEditor.updateSerpTitle(aiData.title);
                }
            }
            if (aiData.description) {
                if (typeof rankMathEditor.updateSerpDescription === 'function') {
                    rankMathEditor.updateSerpDescription(aiData.description);
                }
            }
            if (aiData.focus_keyword) {
                var fk = Array.isArray(aiData.focus_keyword) ? aiData.focus_keyword.join(', ') : aiData.focus_keyword;
                if (typeof rankMathEditor.updateKeywords === 'function') {
                    rankMathEditor.updateKeywords(fk);
                }
            }
        }

        // Tenta atualizar via wp.data (Gutenberg/Block Editor)
        if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
            var dispatch = wp.data.dispatch('rank-math');
            var select = wp.data.select('rank-math');
            
            if (dispatch) {
                if (aiData.title && dispatch.updateSerpTitle) {
                    dispatch.updateSerpTitle(aiData.title);
                }
                if (aiData.description && dispatch.updateSerpDescription) {
                    dispatch.updateSerpDescription(aiData.description);
                }
                if (aiData.focus_keyword) {
                    var fk = Array.isArray(aiData.focus_keyword) ? aiData.focus_keyword.join(', ') : aiData.focus_keyword;
                    if (dispatch.updateKeywords) {
                        dispatch.updateKeywords(fk);
                    }
                }
                // Atualiza o slug se disponível
                if (aiData.slug && dispatch.updatePermalink) {
                    dispatch.updatePermalink(aiData.slug);
                }
            }
        }

        // Editor Clássico - atualiza campos diretamente
        // Title
        var $titleField = $('input[name="rank_math_title"], #rank_math_title, input[id*="rank-math"][id*="title"]');
        if ($titleField.length && aiData.title) {
            $titleField.val(aiData.title).trigger('change').trigger('input');
        }
        
        // Description
        var $descField = $('textarea[name="rank_math_description"], #rank_math_description, textarea[id*="rank-math"][id*="description"]');
        if ($descField.length && aiData.description) {
            $descField.val(aiData.description).trigger('change').trigger('input');
        }
        
        // Focus Keyword
        var $fkField = $('input[name="rank_math_focus_keyword"], #rank_math_focus_keyword, input[id*="rank-math"][id*="focus"]');
        if ($fkField.length && aiData.focus_keyword) {
            var fk = Array.isArray(aiData.focus_keyword) ? aiData.focus_keyword.join(', ') : aiData.focus_keyword;
            $fkField.val(fk).trigger('change').trigger('input');
        }

        // Tenta forçar refresh do Rank Math via evento personalizado
        $(document).trigger('rank-math-data-updated');
        
        // Dispara evento de mudança para React detectar
        if (typeof window.dispatchEvent === 'function') {
            window.dispatchEvent(new Event('rankmath_updated'));
        }
    }

    // Post editor: analyze/fill
    $(document).on('click', '#ai-seo-rm-run', function(){
        var $res = $('#ai-seo-rm-result');
        var $btn = $(this);
        $btn.prop('disabled', true).text('Analisando...');
        $res.html('<span style="color:#666;">🔄 Analisando página e consultando IA...</span>');

        $.post(AISEO_RM.ajaxurl, {
            action: 'ai_seo_rm_analyze_fill',
            nonce: AISEO_RM.nonce,
            post_id: $('#post_ID').val() || (wp && wp.data && wp.data.select('core/editor') ? wp.data.select('core/editor').getCurrentPostId() : 0),
            apply: true
        }).done(function(resp){
            $btn.prop('disabled', false).text('Analisar página e preencher');
            
            if (!resp || !resp.success) {
                var msg = (resp && resp.data && resp.data.message) ? resp.data.message : 'Falha desconhecida';
                var preview = (resp && resp.data && resp.data.raw_preview) ? resp.data.raw_preview : '';
                var code = (resp && resp.data && resp.data.http_code) ? resp.data.http_code : '';
                $res.html('<span style="color:#b32d2e;">❌ Erro: '+ msg + (code ? ' (HTTP '+code+')' : '') +'</span>' + (preview ? '<pre style="white-space:pre-wrap; margin-top:8px;">'+ preview +'</pre>' : ''));
                return;
            }
            var d = resp.data;
            
            // Atualiza os campos do Rank Math na interface
            if (d.ai) {
                updateRankMathFields(d.ai);
            }
            
            var html = '';
            html += '<strong style="color:#2b8a3e;">✅ '+ (d.message || 'Concluído') +'</strong><br><br>';
            
            if (d.ai){
                html += '<div><strong>📝 Sugestão da IA</strong></div>';
                html += '<table style="width:100%; font-size:12px; margin:8px 0;">';
                if (d.ai.title) html += '<tr><td><strong>Title:</strong></td><td>'+ d.ai.title +'</td></tr>';
                if (d.ai.description) html += '<tr><td><strong>Descrição:</strong></td><td>'+ d.ai.description +'</td></tr>';
                if (d.ai.focus_keyword) html += '<tr><td><strong>Keyword:</strong></td><td>'+ (Array.isArray(d.ai.focus_keyword) ? d.ai.focus_keyword.join(', ') : d.ai.focus_keyword) +'</td></tr>';
                if (d.ai.slug) html += '<tr><td><strong>Slug:</strong></td><td>'+ d.ai.slug +'</td></tr>';
                html += '</table>';
                
                if (d.ai.suggestions && d.ai.suggestions.length) {
                    html += '<div><strong>💡 Sugestões:</strong></div><ul style="margin:4px 0; padding-left:18px; font-size:11px;">';
                    d.ai.suggestions.forEach(function(s){ html += '<li>'+ s +'</li>'; });
                    html += '</ul>';
                }
            }
            
            if (d.applied && Object.keys(d.applied).length > 0){
                html += '<div style="margin-top:8px; padding:6px; background:#e8f5e9; border-radius:4px; font-size:11px;">';
                html += '<strong>✅ Campos atualizados:</strong> ';
                html += Object.keys(d.applied).map(function(k){ return k.replace('rank_math_',''); }).join(', ');
                html += '</div>';
            }
            
            html += '<p style="margin-top:10px; font-size:11px; color:#666;">Os campos do Rank Math foram atualizados. Se não aparecerem, salve/atualize o post.</p>';
            
            $res.html(html);
        }).fail(function(){
            $btn.prop('disabled', false).text('Analisar página e preencher');
            $res.html('<span style="color:#b32d2e;">❌ Erro na chamada AJAX.</span>');
        });
    });
})(jQuery);