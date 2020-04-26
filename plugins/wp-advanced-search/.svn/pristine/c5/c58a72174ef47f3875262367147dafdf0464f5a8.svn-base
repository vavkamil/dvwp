(function($j) {
	// Tableau des arguments optionnels (ici les valeurs par défaut)
	var args = {
		target: ASTrigger.ajaxurl,		// Cible contenant le contenu à charger (boucle PHP/MySQL en général)
		limit: ASTrigger.limitR,		// Nombre de résultats à afficher par chargement
		nbResult: jQuery('.WPAdvancedSearch').attr('id'), // Nombre total de résultats (récupéré dynamiquement)
		duration: ASTrigger.duration,	// Durée d'affichage de l'image de chargement (en ms) --> 0 pour annuler !
		classLast: '.WPBlockSearch',	// Class des résultats affichés (obligatoire pour fonctionner !)
		loadImg: ASTrigger.loadImg,		// Image de chargement ('' pour ne pas afficher d'image)
		idImg: 'imgLoading',			// ID du bloc contenant l'image de chargement
		attrID: 'id',					// Attribut contenant le numéro du résultat affiché ('id' conseillé !)
		evt: 'click',					// Type d'événement Javascript pour lancer la fonction
	};

	// Options complémentaires (requête de recherche par exemple ici --> Totalement personnalisable !)
	var options = {
		action: 'ajaxTrigger', // Nécessaire pour WordPress
		queryNameAS: ASTrigger.nameSearch+"="+ASTrigger.query, // Requête + Name de recherche
	};

	// Lancement de la fonction sur l'élément "Afficher plus"
	$j('#loadMore').ajaxTrigger(args, options);
})(jQuery);