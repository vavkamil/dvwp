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
	$.fn.ajaxTrigger = function(args, options) {	
		// Paramètres par défaut (récupérés dans la fonction avec args.NOM_ARGUMENT)
		args = $.extend({
			target: 'resultatsAjax.php',	// Cible contenant le contenu à charger (boucle PHP/MySQL en général)
			limit: 0,						// Nombre de résultats à afficher par chargement
			nbResult: '',					// Nombre total de résultats (10 par défaut...)
			duration: 300,					// Durée d'affichage de l'image de chargement (en ms) --> 0 pour annuler !
			classLast: '.results',			// Class des résultats affichés (obligatoire pour fonctionner !)
			loadImg: 'loadingBlue.gif',		// Image de chargement ('' pour ne pas afficher d'image)
			idImg: 'imgLoading',			// ID du bloc contenant l'image de chargement
			attrID: 'id',					// Attribut contenant le numéro du résultat affiché ('id' conseillé !)
			evt: 'click',					// Type d'événement Javascript pour lancer la fonction
		}, args);
		
		// Variable globale pour récupérer l'élément sur lequel est appliqué le plugin
		loader = this;

		// On affiche l'image de chargement...params.nb + params.limit >= args.nbResult
		if(parseInt($(args.classLast+':last').attr(args.attrID)) < parseInt(args.nbResult)) {
			loader.show();
		}
		
		// Cache le bouton de chargement si le nombre de résultats est faussé
		if(args.nbResult == '' || args.nbResult == 0) {
			loader.remove();
		}

		loader.on(args.evt, function() {
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
			
			// Appel Ajax
			$.ajax({
				url: args.target+'?'+options.queryNameAS,
				data: options,
				// Si Ajax répond bien !
				success: function(data) {
					//Affiche l'image de chargement si elle existe
					if(args.loadImg != '') {
						loader.before('<div id="'+args.idImg+'"><img src="'+args.loadImg+'" alt="loading..." /></div>');
						$('#'+args.idImg).show();
					}
					
					// On cache le loader provisoirement
					loader.hide();
					
					// Gère le temps d'attente avant de lancer la fonction
					setTimeout(function() {
						// Ajoute les nouveaux résultats
						$(args.classLast+':last').after(data);
						
						// Supprimer l'image de chargement si elle existe
						if(args.loadImg != '') {
							$('#'+args.idImg).remove();
						}
					}, args.duration);

					// Affiche à nouveau le loader
					loader.show();

					// Cache le loader à la fin
					if(params.nb + params.limit >= args.nbResult) {
						loader.remove();
					}
				},
				// En cas d'erreur Ajax
				error: function(req, err) {
					console.log('Error: '+err);
				}
			});
		});
		return this; // termine la "boucle" pour jQuery
	}; 
})(jQuery)