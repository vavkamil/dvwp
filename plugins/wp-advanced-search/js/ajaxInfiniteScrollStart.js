(function($j) {
	// Tableau des arguments optionnels (ici les valeurs par défaut)
	var args = {
		target: ASInfiniteScroll.ajaxurl,		// Cible contenant le contenu à charger (boucle PHP/MySQL en général)
		limit: ASInfiniteScroll.limitR,			// Nombre de résultats à afficher par chargement
		nbResult: jQuery('.WPAdvancedSearch').attr('id'), // Nombre total de résultats (récupéré dynamiquement)
		duration: ASInfiniteScroll.duration,	// Durée d'affichage de l'image de chargement (en ms) --> 0 pour annuler !
		classLast: '.WPBlockSearch',			// Class des résultats affichés (obligatoire pour fonctionner !)
		loadMore: '#loadMoreIS',				// Sélecteur de l'image de chargement
		attrID: 'id',							// Attribut contenant le numéro du résultat affiché ('id' conseillé !)
		evt: 'scroll'							// Type d'événement Javascript pour lancer la fonction
	};

	// Options complémentaires (requête de recherche par exemple ici --> Totalement personnalisable !)
	var options = {
		action: 'ajaxInfiniteScroll', // Nécessaire pour WordPress
		queryNameAS: ASInfiniteScroll.nameSearch+"="+ASInfiniteScroll.query, // Requête + Name de recherche
	};
	
	// Lancement de la fonction sur l'élément "Afficher plus"
	$j(window).ajaxInfiniteScroll(args, options);
})(jQuery);