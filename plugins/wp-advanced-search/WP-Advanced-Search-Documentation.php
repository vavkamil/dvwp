<?php
// Fonction d'affichage de la page d'aide et de réglages de l'extension
function WP_Advanced_Search_Callback_Documentation() {

	// Déclencher la fonction de mise à jour des index FULLTEXT (upload)
	if(isset($_POST['wp_advanced_search_fulltext'])) {
		WP_Advanced_Search_FullText_Doc();
	}

	/* --------------------------------------------------------------------- */
	/* ------------------------ Affichage de la page ----------------------- */
	/* --------------------------------------------------------------------- */
	echo '<div class="wrap advanced-search-admin">';
	echo '<div class="icon32 icon"><br /></div>';
	echo '<h2>'; _e('Documentation','wp-advanced-search'); echo '</h2><br/>';
	echo '<div class="text">';
	_e('<strong>WP Advanced Search</strong> est un moteur de recherche complet pour WordPress qui corrige de nombreuses failles du moteur initial.', 'wp-advanced-search');
	echo '<br/>';
	_e('La documentation ci-dessous explique l\'installation et le fonctionnement global du plugin de recherche avancé.', 'wp-advanced-search');
	echo '<br/>';
	_e('<em>N.B. : n\'hésitez pas à contacter <a href="http://blog.internet-formation.fr/2013/10/wp-advanced-search-moteur-de-recherche-avance-pour-wordpress/" target="_blank">Mathieu Chartier</a>, le créateur du plugin, pour de plus amples informations.</em>', 'wp-advanced-search'); echo '<br/>';
	echo '</div>';
?>
    <div class="block clear">
        <div class="col">
           	<h4><?php _e('Installation du plugin','wp-advanced-search'); ?></h4>
        	<p class="tr"><?php _e('1. Préparez le formulaire de recherche (exemple : search-form.php)','wp-advanced-search'); ?></p>
            <div class="tr-info">
            	<p><?php _e('Repérez le formulaire de recherche WordPress','wp-advanced-search') ?></p>
                <ol>
                    <li><?php _e('Localisez l\'attribut "name" du champ de recherche ("s" par défaut) et le modifiez si désiré.','wp-advanced-search') ?></li>
                    <li><?php _e('S\'assurer que le formulaire pointe vers la page de recherche (search.php par défaut).','wp-advanced-search') ?></li>
                </ol>
            </div>
        </div>
        <div class="col">
        	<h4><?php _e('Capture d\'écran','wp-advanced-search'); ?></h4>
        	<p class="tr"><img src="<?php echo plugins_url('img/screenshot-1.png',__FILE__); ?>" alt="Capture WP Advanced Search - 1" /></p>
        </div>
    </div>
    <div class="block clear">
        <div class="col">
        	<p class="tr">
            	<?php _e('2. Préparez la page de résultats (exemple : search.php)','wp-advanced-search'); ?>
                <br/>
                <?php _e('&nbsp;&nbsp;&nbsp;&nbsp;Ajouter le code <strong>&lt;?php WP_Advanced_Search(); ?&gt;</strong> pour afficher les résultats','wp-advanced-search'); ?>
            </p>
            <div class="tr-info">
            	<p><?php _e('Installation simple et rapide !','wp-advanced-search') ?></p>
                <ol>
                    <li><?php _e('Supprimez toute la boucle d\'affichage initiale des résultats de recherche.','wp-advanced-search') ?></li>
                    <li><?php _e('Placez-vous dans le bloc qui doit recevoir les résultats de recherche.','wp-advanced-search') ?></li>
                    <li><?php _e('Remplacez la <a href="https://codex.wordpress.org/The_Loop" target="_blank">boucle WordPress</a> par le code <strong>&lt;?php WP_Advanced_Search(); ?&gt;</strong>.','wp-advanced-search') ?></li>
                </ol>
            </div>
        </div>
        <div class="col">
            <p class="tr"><img src="<?php echo plugins_url('img/screenshot-2.png',__FILE__); ?>" alt="Capture WP Advanced Search - 2" /></p>
        </div>
    </div>
    <div class="block clear">
        <div class="col">
        	<p class="tr"><?php _e('3. Paramétrez le moteur à votre guise','wp-advanced-search'); ?></p>
            <div class="tr-info">
            	<p><?php _e('Les réglages par défaut répondent aux fonctionnalités essentielles du moteur de recherche.','wp-advanced-search') ?></p>
                <ol>
                    <li><?php _e('Entrez la valeur de l\'attribut "name" du champ de recherche ("s" par défaut) et celui des tables dans lesquelles rechercher (laissez par défaut si vous avez des doutes)','wp-advanced-search') ?></li>
                    <li><?php _e('Choisissez le type de recherche : FULLTEXT (texte intégral), REGEXP (relativement précis), LIKE (recherche approchante)','wp-advanced-search') ?></li>
                    <li><?php _e('Paramétrez l\'ordre d\'affichage des résultats du moteur en choisissant la colonne de classement (dates des articles et pages par défaut) et/ou en activant l\'algorithme de pertinence (il affiche les résultats qui répondent le plus à la recherche).','wp-advanced-search') ?></li>
                    <li><?php _e('Activez ou non la surbrillance des mots-clés recherchés.','wp-advanced-search') ?></li>
                    <li><?php _e('Activez ou non les "stop words", c\'est-à-dire l\'exclusion des mots vides lors des recherches. Il est aussi possible d\'exclure les mots qui ne dépassent pas un certain nombre de caractères.','wp-advanced-search') ?></li>
                </ol>
            </div>
        </div>
        <div class="col">
			<p class="tr"><img src="<?php echo plugins_url('img/screenshot-3.png',__FILE__); ?>" alt="Capture WP Advanced Search - 3" /></p>
        </div>
    </div>
    <div class="block clear">
        <div class="col">
        	<p class="tr"><?php _e('<strong>N.B. : particularités des index FULLTEXT !</strong>','wp-advanced-search'); ?></p>
            <div class="tr-info">
            	<p><?php _e('La recherche en texte intégrale (FULLTEXT) est la plus aboutie mais demande quelques paramétrages.','wp-advanced-search') ?></p>
                <ol>
                    <li><?php _e('<strong>Installation d\'index FULLTEXT</strong> sur les tables de recherche. Cliquez sur ce lien pour gagner du temps : <a href="#" onclick="','wp-advanced-search'); ?>getElementById('WP-Advanced-Search-Form').submit()<?php _e('">créez les index FULLTEXT</a>.','wp-advanced-search'); ?></li>
                    <li><?php _e('La recherche FULLTEXT répond à certains paramètres du fichier my.ini de MySQL, il convient d\'avoir accès à ce fichier pour gérer totalement la recherche en texte intégrale...','wp-advanced-search') ?></li>
                    <li><?php _e('Par défaut, la recherche FULLTEXT exclut quelques "stopwords" mais aussi les mots courts (de 1 à 3 caractères), ce qui peut poser problème. Il faut modifier le <strong>paramètre ft_min_word_len</strong> si vous avez un serveur dédié. Si vous détenez un serveur mutualisé, il est conseillé d\'opter pour la recherche REGEXP ou LIKE si vous voulez que tous les mots offrent des résultats...','wp-advanced-search') ?></li>
                </ol>
            </div>
        </div>
        <div class="col">
			<p class="tr"><img src="<?php echo plugins_url('img/screenshot-4.png',__FILE__); ?>" alt="Capture WP Advanced Search - 3" /></p>
        </div>
    </div>
    <div class="block clear">
        <div class="col">
        	<p class="tr"><?php _e('4. Stylisez l\'ensemble et les résultats de recherche','wp-advanced-search'); ?></p>
            <div class="tr-info">
            	<p><?php _e('Plusieurs options disponibles pour personnaliser l\'affichage des résultats.','wp-advanced-search') ?></p>
                <ol>
                    <li><?php _e('Choisissez les blocs à afficher (tout est modulable).','wp-advanced-search') ?></li>
                    <li><?php _e('Choisissez un thème dans la liste ou désactivez les thèmes (style personnalisé).','wp-advanced-search') ?></li>
                    <li><?php _e('Paramétrez les classes CSS selon vos envies.','wp-advanced-search') ?></li>
                    <li><?php _e('Formatez la date comme bon vous semble (si vous souhaitez l\'afficher).','wp-advanced-search') ?></li>
                    <li><?php _e('Numérotez ou non les résultats de recherche.','wp-advanced-search') ?></li>
                </ol>
            </div>
        </div>
        <div class="col">
			<p class="tr"><img src="<?php echo plugins_url('img/screenshot-5.png',__FILE__); ?>" alt="Capture WP Advanced Search - 4" /></p>
        </div>
    </div>
    <div class="block clear">
        <div class="col">
        	<p class="tr"><?php _e('5. Réglez la pagination (optionnel)','wp-advanced-search'); ?></p>
            <div class="tr-info">
            	<p><?php _e('Plusieurs options de personnalisation de la pagination.','wp-advanced-search') ?></p>
                <ol>
                    <li><?php _e('Activez ou non la pagination (affiche tous les résultats si désactivé).','wp-advanced-search') ?></li>
                    <li><?php _e('Sélectionnez les liens à afficher dans la pagination.','wp-advanced-search') ?></li>
                    <li><?php _e('Choisissez un thème ou non pour la pagination (plusieurs couleurs disponibles).','wp-advanced-search') ?></li>
                    <li><?php _e('Modifiez les libellés de la pagination si besoin.','wp-advanced-search') ?></li>
                </ol>
            </div>
        </div>
        <div class="col">
			<p class="tr"><img src="<?php echo plugins_url('img/screenshot-6.png',__FILE__); ?>" alt="Capture WP Advanced Search - 5" /></p>
        </div>
    </div>
    <div class="block clear">
        <div class="col">
        	<p class="tr"><?php _e('6. Réglez l\'autocomplétion (optionnel)','wp-advanced-search'); ?></p>
            <div class="tr-info">
            	<p><?php _e('Plusieurs options de personnalisation pour les autosuggestions.','wp-advanced-search') ?></p>
                <ol>
                    <li><?php _e('Activez ou non l\'autocomplétion.','wp-advanced-search') ?></li>
                    <li><?php _e('Important : opter pour le bon sélecteur du champ de recherche (#id ou .class en fonction --> .search-field pour le thème Twenty Thourteen par exemple).','wp-advanced-search') ?></li>
                    <li><?php _e('Important : créer la table d\'index inversé pour rendre le système fonctionnel.','wp-advanced-search') ?></li>
                    <li><?php _e('Adaptez les options à vos envies.','wp-advanced-search') ?></li>
                    <li><?php _e('N.B. : il est recommandé d\'ajouter soi-même des mots et expressions au début','wp-advanced-search') ?></li>
                </ol>
            </div>
        </div>
        <div class="col">
			<p class="tr"><img src="<?php echo plugins_url('img/screenshot-7.png',__FILE__); ?>" alt="Capture WP Advanced Search - 5" /></p>
        </div>
    </div>
    
    <!-- Formulaire pour installer les index FULLTEXT (si activé en cliquant sur le lien) -->
    <form id="WP-Advanced-Search-Form" method="post">
        <input type="hidden" name="wp_advanced_search_fulltext" value="" />
    </form>
<?php
	echo '</div>';
} // Fin de la fonction Callback

function WP_Advanced_Search_FullText_Doc() {
	global $wpdb, $tableName;
	$select = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix.$tableName." WHERE id=1");
	
	// Récupération des valeurs des variables utiles
	$columnSelectSearch = $select->colonnesWhere;
	$databaseSearch = $select->db;
	$tableSearch = $select->tables;

	// Inclusion des class du moteur de recherche
	include('class.inc/moteur-php5.5.class-inc.php');

	$alterTable = new alterTableFullText($wpdb, $databaseSearch, $tableSearch, $columnSelectSearch);
	echo '<script type="application/javascript">alert("'.__('Index FULLTEXT créés avec succès !\nVous pouvez utiliser le type FULLTEXT dorénavant...','wp-advanced-search').'");</script>';
}
?>