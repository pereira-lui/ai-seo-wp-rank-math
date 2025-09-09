(function($){
  $(document).on('click', '#ai-seo-test-key', function(){
    var $res = $('#ai-seo-test-result'); $res.text('Testando chave...');
    $.post(AISEO_RM.ajaxurl, { action: 'ai_seo_rm_test_key', nonce: AISEO_RM.nonce })
    .done(function(resp){
      if (resp && resp.success){ $res.html('<span style="color:#2b8a3e;">'+ resp.data.message +'</span>'); }
      else {
        var msg = resp && resp.data && resp.data.message ? resp.data.message : 'Falha';
        var code = resp && resp.data && resp.data.http_code ? ' (HTTP '+resp.data.http_code+')' : '';
        var prev = resp && resp.data && resp.data.preview ? '<pre style="white-space:pre-wrap">'+resp.data.preview+'</pre>' : '';
        $res.html('<span style="color:#b32d2e;">'+ msg + code +'</span>'+prev);
      }
    }).fail(function(){ $res.html('<span style="color:#b32d2e;">Erro ao testar.</span>'); });
  });

  $(document).on('click', '#ai-seo-rm-run', function(){
    var $res = $('#ai-seo-rm-result'); $res.text('Analisando...');
    $.post(AISEO_RM.ajaxurl, {
      action: 'ai_seo_rm_analyze_fill', nonce: AISEO_RM.nonce, post_id: $('#post_ID').val(), apply: true
    }).done(function(resp){
      if (!resp || !resp.success){
        var msg = (resp && resp.data && resp.data.message) ? resp.data.message : 'Falha desconhecida';
        var preview = (resp && resp.data && resp.data.raw_preview) ? resp.data.raw_preview : '';
        var code = (resp && resp.data && resp.data.http_code) ? resp.data.http_code : '';
        $res.html('<span style="color:#b32d2e;">Erro: '+ msg + (code ? ' (HTTP '+code+')' : '') +'</span>' + (preview ? '<pre style="white-space:pre-wrap; margin-top:8px;">'+ preview +'</pre>' : ''));
        return;
      }
      var d = resp.data, html = '';
      html += '<strong>'+(d.message || 'Concluído') +'</strong><br><br>';
      if (d.analysis){ html += '<div><strong>Resumo técnico</strong></div><pre style="white-space:pre-wrap;">'+ JSON.stringify(d.analysis, null, 2) +'</pre>'; }
      if (d.ai){ html += '<div><strong>Sugestão da IA</strong></div><pre style="white-space:pre-wrap;">'+ JSON.stringify(d.ai, null, 2) +'</pre>'; }
      if (d.applied){ html += '<div><strong>Aplicado</strong></div><pre style="white-space:pre-wrap;">'+ JSON.stringify(d.applied, null, 2) +'</pre>'; }
      $res.html(html);
    }).fail(function(){ $res.html('<span style="color:#b32d2e;">Erro na chamada.</span>'); });
  });
})(jQuery);