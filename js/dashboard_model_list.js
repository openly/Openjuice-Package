$(function(){
	var loader = $('#ccm-search-loading');
	$('.ccm-pane').on('click','.ccm-pane-body #model-result-list a.ajax',reloadResults);
	$('.ccm-pane').on('click','.ccm-pane-footer .pagination a',reloadResults);
	$('.ccm-pane-options .ccm-pane-options-permanent-search form').submit(function(e){
		e.preventDefault();
		if(typeof(history.pushState) == 'function')
			history.pushState({},'Results page',e.target.action +'?'+ $(this).serialize());
		loader.show();
		$.get('?'+$(this).serialize()+'&ajax=true',function(result){
			$('.ccm-pane-body,.ccm-pane-footer').remove();
			$('.ccm-pane-options').after(result);
			loader.hide();
		})
	});

	function reloadResults(e){
		loader.show();
		e.preventDefault();
		if(typeof(history.pushState) == 'function')
			history.pushState({},'Results page',e.target.href);
		var url = e.target.href;
		$.get(url,{ajax:true},function(result){
			loader.hide();
			$('.ccm-pane-body,.ccm-pane-footer').remove();
			if($('.ccm-pane-options').length < 1){
				$('.ccm-pane-header').after(result);
			}
			else{
				$('.ccm-pane-options').after(result);
			}
		});
	}
})