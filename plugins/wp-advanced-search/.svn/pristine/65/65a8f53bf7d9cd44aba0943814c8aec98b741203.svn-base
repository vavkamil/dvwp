/*-------------------------------------------------------------*/
// Project name : AjaxTrigger-jQuery
// Author: Mathieu Chartier
// Website: http://blog.internet-formation.fr
// Origin: France
// Date: July 31th 2014
// Version: 1.2
// More informations: it works perfectly with jQuery 1.7.2
/*-------------------------------------------------------------*/
(function($){
	$.fn.ajaxInfiniteScroll = function(args, options) {
		// Paramètres par défaut (récupérés dans la fonction avec args.NOM_ARGUMENT)
		args = $.extend({
			target: 'resultatsAjax.php',	// Cible contenant le contenu à charger (boucle PHP/MySQL en général)
			limit: 10,						// Nombre de résultats à afficher par chargement
			nbResult: '',					// Nombre total de résultats (10 par défaut...)
			duration: 500,					// Durée d'affichage de l'image de chargement (en ms) --> 0 pour annuler !
			classLast: '.results',			// Class des résultats affichés (obligatoire pour fonctionner !)
			loadMore: '#loadMore',			// Sélecteur de l'image de chargement
			attrID: 'id',					// Attribut contenant le numéro du résultat affiché ('id' conseillé !)
			evt: 'scroll',					// Type d'événement Javascript pour lancer la fonction
		}, args);
		
		// Variable globale pour récupérer l'élément sur lequel est appliqué le plugin
		loader = this;

		// Pas de chargement pas défaut
		var load = false;
			
		// Lancement de la fonction (au scroll ou autre)
		loader.bind(args.evt, function(e) {
			// Récupération des variables utiles pour développer le nombre de résultats affiché
			var nb = parseInt($(args.classLast+':last').attr(args.attrID));
			var page = Math.ceil((nb + 1) / parseInt(args.limit));

			// Initialisation du nombre de résultats par "tranche"
			if(nb <= parseInt(args.limit)) {
				var limit = parseInt(nb);
				var page = page + 1;
			} else {
				var limit = parseInt(args.limit);			
			}

			// Paramètres implicites de base
			params = {
				page: page,
				nb: nb,
				limit: limit
			};
			
			// Liste étendue de paramètres implicites à rajouter si besoin
			options = $.extend(options, params);
			
			// On affiche l'image de chargement...
			if(params.nb >= params.limit) {
				$(args.loadMore).show();
			}
			$(args.loadMore).show();

			// "Blocage" du script si nécessaire + variable de limitation
			if(parseInt(args.nbResult) == '' || parseInt(args.nbResult) == 0) {
				var stopping = (params.limit * params.page);
				if(parseInt($(args.classLast+':last').attr(args.attrID)) >= parseInt(args.nbResult)) {
				//if((stopping < parseInt(params.limit)) || (parseInt(params.nb) > stopping)) {
					$(args.loadMore).remove();
					loader.unbind(args.evt);
					load = true;
				}
			} else {
				var stopping = parseInt(args.nbResult);
				if(parseInt($(args.classLast+':last').attr(args.attrID)) >= parseInt(args.nbResult)) {
				//if((stopping < parseInt(params.limit)) || (parseInt(params.nb) > stopping)) {
					$(args.loadMore).remove();
					loader.unbind(args.evt);
					load = true;
				}
			}

			var classOffset = parseInt($("#loadMoreIS").offset().top);
			
			// Si on arrive en bas de la fenêtre, le scroll actif déclenche la fonction
			if((classOffset - $(window).height() <= $(window).scrollTop()) && load === false && (params.nb >= params.limit) && (params.nb <= stopping)) {
			// if(($(window).scrollTop() >= $(document).height() - $(window).height()) && (params.nb >= params.limit) && (params.nb <= stopping)) {
			// Alternative : if(((loader.scrollTop() + $(window).height()) == $(document).height()) && (params.nb >= params.limit) && (params.nb <= stopping)) {
				// la valeur passe à vrai, on va charger
				load = true;

				// Appel Ajax
				$.ajax({
					url: args.target+'?'+options.queryNameAS,
					data: options,
					// Si Ajax répond bien !
					success: function(data) {
						// Effet sur le bloc d'image de chargement
						$(args.loadMore).fadeOut(args.duration);
						
						// Gère le temps d'attente avant de lancer la fonction
						setTimeout(function() {
							// Ajoute les nouveaux résultats
							$(args.classLast+':last').after(data);
						}, args.duration);
						
						
					},
					complete: function(req, status) {
						setTimeout(function() {
							if(status == "success") {
								load = false;
							}
						}, args.duration);
					},
					// En cas d'erreur Ajax
					error: function(req, err) {
						console.log('Error: '+err);
					}
				});
			} // Fin du scroll
		});
		return this; // termine la "boucle" pour jQuery
	}; 
})(jQuery)