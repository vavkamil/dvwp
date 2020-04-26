(function($j) {
	var selector = ac_param.selector;
	var urlDestination = ac_param.urlDestination;
	var autoFocus = ac_param.autoFocus;
	var limitDisplay = ac_param.limitDisplay;
	var multiple = ac_param.multiple;
	$j(selector).autocomplete(urlDestination, { selectFirst:autoFocus, max:limitDisplay, multiple:multiple, multipleSeparator:' ', delay:50, noRecord:''});

	// Nettoyer la requête (supprimer les espaces inutiles)
	var form = $j(selector).parents('form:first');
	$j(form).on('submit', function() {
		$j(selector).val($j.trim($j(selector).val()));
	});
})(jQuery);