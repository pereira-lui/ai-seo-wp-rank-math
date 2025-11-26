(function($){
    
    // Analyze button click
    $(document).on('click', '#ai-seo-rm-analyze', function(){
        var $btn = $(this);
        var $result = $('#ai-seo-rm-result');
        
        $btn.prop('disabled', true).text('🔄 Analisando...');
        $result.hide();

        // Get post ID
        var postId = $('#post_ID').val();
        if (!postId && typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
            postId = wp.data.select('core/editor').getCurrentPostId();
        }

        $.post(AISEO_RM.ajaxurl, {
            action: 'ai_seo_rm_analyze',
            nonce: AISEO_RM.nonce,
            post_id: postId
        }).done(function(resp){
            $btn.prop('disabled', false).text('🔍 Analisar Página');
            
            if (!resp || !resp.success) {
                var msg = (resp && resp.data && resp.data.message) ? resp.data.message : 'Erro desconhecido';
                $result.html('<p style="color:#d63638;">❌ ' + msg + '</p>').show();
                return;
            }

            var d = resp.data;
            var html = '<h4 style="margin:0 0 10px 0;">📊 Análise Técnica</h4>';
            
            // Stats
            html += '<table style="width:100%; font-size:12px; border-collapse:collapse;">';
            html += '<tr><td style="padding:4px 0;">📝 Palavras:</td><td style="text-align:right;"><strong>' + d.analysis.word_count + '</strong></td></tr>';
            html += '<tr><td style="padding:4px 0;">🏷️ H1:</td><td style="text-align:right;"><strong>' + d.analysis.h1_count + '</strong></td></tr>';
            html += '<tr><td style="padding:4px 0;">📌 H2:</td><td style="text-align:right;"><strong>' + d.analysis.h2_count + '</strong></td></tr>';
            html += '<tr><td style="padding:4px 0;">🖼️ Imagens:</td><td style="text-align:right;"><strong>' + d.analysis.images_total + '</strong></td></tr>';
            html += '<tr><td style="padding:4px 0;">🔗 Links internos:</td><td style="text-align:right;"><strong>' + d.analysis.links_internal + '</strong></td></tr>';
            html += '<tr><td style="padding:4px 0;">🌐 Links externos:</td><td style="text-align:right;"><strong>' + d.analysis.links_external + '</strong></td></tr>';
            html += '</table>';
            
            // Quick tips
            if (d.analysis.quick_tips && d.analysis.quick_tips.length) {
                html += '<h4 style="margin:15px 0 10px 0;">💡 Sugestões</h4>';
                html += '<ul style="margin:0; padding-left:15px; font-size:11px; line-height:1.6;">';
                d.analysis.quick_tips.forEach(function(tip){
                    html += '<li>' + tip + '</li>';
                });
                html += '</ul>';
            }
            
            // Rank Math status
            html += '<h4 style="margin:15px 0 10px 0;">🎯 Rank Math</h4>';
            html += '<ul style="margin:0; padding-left:15px; font-size:11px;">';
            html += '<li>Title: ' + (d.analysis.rank_math.title ? '✅' : '❌ Vazio') + '</li>';
            html += '<li>Description: ' + (d.analysis.rank_math.description ? '✅' : '❌ Vazio') + '</li>';
            html += '<li>Focus Keyword: ' + (d.analysis.rank_math.focus_kw ? '✅' : '❌ Vazio') + '</li>';
            html += '</ul>';
            
            // PRO CTA
            if (!AISEO_RM.is_pro) {
                html += '<div style="background:#fff3cd; border:1px solid #ffc107; border-radius:4px; padding:10px; margin-top:15px; text-align:center; font-size:11px;">';
                html += '<strong>🚀 Versão PRO:</strong> Preenche esses campos automaticamente com IA!';
                html += '</div>';
            }
            
            $result.html(html).show();
            
        }).fail(function(){
            $btn.prop('disabled', false).text('🔍 Analisar Página');
            $result.html('<p style="color:#d63638;">❌ Erro na conexão.</p>').show();
        });
    });

})(jQuery);
