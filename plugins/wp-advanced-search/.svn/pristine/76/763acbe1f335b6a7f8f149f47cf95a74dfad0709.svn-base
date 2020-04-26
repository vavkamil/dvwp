<?php
/*
Plugin Name: WP-Advanced-Search
Plugin URI: http://blog.internet-formation.fr/2013/10/wp-advanced-search/
Description: Moteur de recherche avancé pour WordPress à la place du moteur initial (mise en surbrillance, trois types de recherche, styles, paginations, algorithme de pertinence optionnel...). (<em>Plugin adds a advanced search engine for WordPress with a lot of options (three type of search, bloded request, three method for pagination, relevancy algorithm...</em>).
Author: Mathieu Chartier
Version: 3.3.3
Author URI: http://blog.internet-formation.fr
Text Domain: wp-advanced-search
Domain Path: /lang
*/

// Instanciation des variables globales
global $wpdb, $table_WP_Advanced_Search, $tableName, $WP_Advanced_Search_Version;
$tableName = 'advsh'; // Nom de la table
$table_WP_Advanced_Search = $wpdb->prefix.$tableName;

// Version du plugin
$WP_Advanced_Search_Version = "3.3.3";

function WP_Advanced_Search_Lang() {
	load_plugin_textdomain('wp-advanced-search', false, dirname(plugin_basename( __FILE__ )).'/lang/');
}
add_action('plugins_loaded', 'WP_Advanced_Search_Lang' );

// Fonction lancée lors de l'activation ou de la desactivation de l'extension
register_activation_hook( __FILE__, 'WP_Advanced_Search_install');
register_deactivation_hook( __FILE__, 'WP_Advanced_Search_desinstall');

function WP_Advanced_Search_install() {	
	global $wpdb, $table_WP_Advanced_Search, $tableName, $WP_Advanced_Search_Version;

	// Pour le multisite
	if(function_exists('is_multisite') && is_multisite()) {
        $original_blog_id = $wpdb->blogid;
        // Obtient les autres ID du multisites
        $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        foreach($blogids as $blog_id) {
            switch_to_blog($blog_id);
            WP_Advanced_Search_install_data($wpdb->prefix.$tableName);
        }
        switch_to_blog($original_blog_id);  
    } else { // Sinon...
		WP_Advanced_Search_install_data($table_WP_Advanced_Search);
	}
	
	// Prise en compte de la version en cours
	add_site_option("wp_advanced_search_version", $WP_Advanced_Search_Version);
}
function WP_Advanced_Search_install_data($table, $instance = array()) {		
	global $wpdb;

	// Création de la table de base
	$sql = "CREATE TABLE IF NOT EXISTS ".$table." (
		id INT(3) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		db VARCHAR(50) NOT NULL,
		tables VARCHAR(30) NOT NULL,
		nameField VARCHAR(30) NOT NULL,
		colonnesWhere TEXT NOT NULL, 
		typeSearch VARCHAR(8) NOT NULL,
		encoding VARCHAR(25) NOT NULL,
		exactSearch BOOLEAN NOT NULL,
		accents BOOLEAN NOT NULL,
		exclusionWords TEXT,
		stopWords BOOLEAN NOT NULL,
		nbResultsOK BOOLEAN NOT NULL,
		NumberOK BOOLEAN NOT NULL,
		NumberPerPage INT(2),
		idform VARCHAR(200),
		placeholder VARCHAR(200),
		Style VARCHAR(10) NOT NULL,
		formatageDate VARCHAR(25),
		DateOK BOOLEAN NOT NULL,
		AuthorOK BOOLEAN NOT NULL,
		CategoryOK BOOLEAN NOT NULL,
		TitleOK BOOLEAN NOT NULL,
		ArticleOK VARCHAR(12) NOT NULL,
		CommentOK BOOLEAN NOT NULL,
		ImageOK BOOLEAN NOT NULL,
		BlocOrder VARCHAR(10) NOT NULL,
		strongWords VARCHAR(10) NOT NULL,
		OrderOK BOOLEAN NOT NULL,
		OrderColumn VARCHAR(25) NOT NULL,
		AscDesc VARCHAR(4) NOT NULL,
		AlgoOK BOOLEAN NOT NULL,
		paginationActive BOOLEAN NOT NULL,
		paginationStyle VARCHAR(30) NOT NULL,
		paginationFirstLast BOOLEAN NOT NULL,
		paginationPrevNext BOOLEAN NOT NULL,
		paginationFirstPage VARCHAR(50) NOT NULL,
		paginationLastPage VARCHAR(50) NOT NULL,
		paginationPrevText VARCHAR(50) NOT NULL,
		paginationNextText VARCHAR(50) NOT NULL,
		paginationType VARCHAR(50) NOT NULL,
		paginationNbLimit INT NOT NULL,
		paginationDuration INT NOT NULL,
		paginationText VARCHAR(250) NOT NULL,
		postType VARCHAR(8) NOT NULL,
		categories TEXT,
		ResultText TEXT,
		ErrorText TEXT,
		autoCompleteActive BOOLEAN NOT NULL,
		autoCompleteSelector VARCHAR(50) NOT NULL,
		autoCompleteAutofocus BOOLEAN NOT NULL,
		autoCompleteType TINYINT,
		autoCompleteNumber TINYINT,
		autoCompleteTypeSuggest BOOLEAN NOT NULL,
		autoCompleteCreate BOOLEAN NOT NULL,
		autoCompleteTable VARCHAR(50) NOT NULL,
		autoCompleteColumn VARCHAR(50) NOT NULL,
		autoCompleteGenerate BOOLEAN NOT NULL,
		autoCompleteSizeMin TINYINT,
		autoCorrectActive BOOLEAN NOT NULL,
		autoCorrectType TINYINT,
		autoCorrectMethod BOOLEAN NOT NULL,
		autoCorrectString TEXT NOT NULL,
		autoCorrectCreate BOOLEAN NOT NULL
		) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";
	require_once(ABSPATH.'wp-admin/includes/upgrade.php');
	dbDelta($sql);

	// Récupération automatique du nom de la base de données
	$databaseNameSearch = $wpdb->get_results("SELECT DATABASE()");
	foreach($databaseNameSearch[0] as $databaseSearch) {
		// Insertion de valeurs par défaut (premier enregistrement)
		$defaut = array(
			"db" => $databaseSearch,
			"tables" => $wpdb->posts,
			"nameField" => 's',
			"colonnesWhere" => 'post_title, post_content, post_excerpt',
			"typeSearch" => "REGEXP",
			"encoding" => "utf-8",
			"exactSearch" => true,
			"accents" => false,
			"exclusionWords" => 1,
			"stopWords" => true,
			"nbResultsOK" => false,
			"NumberOK" => true,
			"NumberPerPage" => 10,
			"idform" => 's',
			"placeholder" => '',
			"Style" => 'aucun',
			"formatageDate" => 'j F Y',
			"DateOK" => true,
			"AuthorOK" => true,
			"CategoryOK" => true,
			"TitleOK" => true,
			"ArticleOK" => 'aucun',
			"CommentOK" => true,
			"ImageOK" => false,
			"BlocOrder" => "D-A-C",
			"strongWords" => "exact",
			"OrderOK" => true,
			"OrderColumn" => 'post_date',
			"AscDesc" => 'DESC',
			"AlgoOK" => false,
			"paginationActive" => true,
			"paginationStyle" => "aucun",
			"paginationFirstLast" => true,
			"paginationPrevNext" => true,
			"paginationFirstPage" => "Première page",
			"paginationLastPage" => "Dernière page",
			"paginationPrevText" => "&laquo; Précédent",
			"paginationNextText" => "Suivant &raquo;",
			"paginationType" => "trigger",
			"paginationNbLimit" => 5,
			"paginationDuration" => 300,
			"paginationText" => __('Afficher plus de résultats','wp-advanced-search'),
			"postType" => 'pagepost',
			"categories" => serialize(array('toutes')),
			"ResultText" => __('Résultats de la recherche :','wp-advanced-search'),
			"ErrorText" => __('Aucun résultat, veuillez effectuer une autre recherche !','wp-advanced-search'),
			"autoCompleteActive" => true,
			"autoCompleteSelector" => ".search-field",
			"autoCompleteAutofocus" => false,
			"autoCompleteType" => 0,
			"autoCompleteNumber" => 5,
			"autoCompleteTypeSuggest" => true,
			"autoCompleteCreate" => false,
			"autoCompleteTable" => $wpdb->prefix."autosuggest",
			"autoCompleteColumn" => "words",
			"autoCompleteGenerate" => true,
			"autoCompleteSizeMin" => 2,
			"autoCorrectActive" => true,
			"autoCorrectType" => 2,
			"autoCorrectMethod" => true,
			"autoCorrectString" => __('Tentez avec une autre orthographe : ','wp-advanced-search'),
			"autoCorrectCreate" => false
		);
		$champ = wp_parse_args($instance, $defaut);
		$default = $wpdb->insert($table, array('db' => $champ['db'], 'tables' => $champ['tables'], 'nameField' => $champ['nameField'], 'colonnesWhere' => $champ['colonnesWhere'], 'typeSearch' => $champ['typeSearch'], 'encoding' => $champ['encoding'], 'exactSearch' => $champ['exactSearch'], 'accents' => $champ['accents'], 'exclusionWords' => $champ['exclusionWords'], 'stopWords' => $champ['stopWords'], 'nbResultsOK' => $champ['nbResultsOK'], 'NumberOK' => $champ['NumberOK'], 'NumberPerPage' => $champ['NumberPerPage'], 'idform' => $champ['idform'], 'placeholder' => $champ['placeholder'], 'Style' => $champ['Style'], 'formatageDate' => $champ['formatageDate'], 'DateOK' => $champ['DateOK'], 'AuthorOK' => $champ['AuthorOK'], 'CategoryOK' => $champ['CategoryOK'], 'TitleOK' => $champ['TitleOK'], 'ArticleOK' => $champ['ArticleOK'], 'CommentOK' => $champ['CommentOK'], 'ImageOK' => $champ['ImageOK'], 'BlocOrder' => $champ['BlocOrder'], 'strongWords' => $champ['strongWords'], 'OrderOK' => $champ['OrderOK'], 'OrderColumn' => $champ['OrderColumn'], 'AscDesc' => $champ['AscDesc'], 'AlgoOK' => $champ['AlgoOK'], 'paginationActive' => $champ['paginationActive'], 'paginationStyle' => $champ['paginationStyle'], 'paginationFirstLast' => $champ['paginationFirstLast'], 'paginationPrevNext' => $champ['paginationPrevNext'], 'paginationFirstPage' => $champ['paginationFirstPage'], 'paginationLastPage' => $champ['paginationLastPage'], 'paginationPrevText' => $champ['paginationPrevText'], 'paginationNextText' => $champ['paginationNextText'], 'paginationType' => $champ['paginationType'], 'paginationNbLimit' => $champ['paginationNbLimit'], 'paginationDuration' => $champ['paginationDuration'], 'paginationText' => $champ['paginationText'], 'postType' => $champ['postType'], 'categories' => $champ['categories'], 'ResultText' => $champ['ResultText'], 'ErrorText' => $champ['ErrorText'], 'autoCompleteActive' => $champ['autoCompleteActive'], 'autoCompleteSelector' => $champ['autoCompleteSelector'], 'autoCompleteAutofocus' => $champ['autoCompleteAutofocus'], 'autoCompleteType' => $champ['autoCompleteType'], 'autoCompleteNumber' => $champ['autoCompleteNumber'], 'autoCompleteCreate' => $champ['autoCompleteCreate'], 'autoCompleteTable' => $champ['autoCompleteTable'], 'autoCompleteColumn' => $champ['autoCompleteColumn'], 'autoCompleteTypeSuggest' => $champ['autoCompleteTypeSuggest'], 'autoCompleteGenerate' => $champ['autoCompleteGenerate'], 'autoCompleteSizeMin' => $champ['autoCompleteSizeMin'], 'autoCorrectActive' => $champ['autoCorrectActive'], 'autoCorrectType' => $champ['autoCorrectType'], 'autoCorrectMethod' => $champ['autoCorrectMethod'], 'autoCorrectString' => $champ['autoCorrectString'], 'autoCorrectCreate' => $champ['autoCorrectCreate']));

		// Création de l'index inversé par défaut (pour l'autocomplétion)
		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$champ['autoCompleteTable']." (
					idindex INT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					".$champ['autoCompleteColumn']." VARCHAR(250) NOT NULL) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci"
		);
	}
}
// Quand un nouveau site (en multisites) est ajouté
add_action('wpmu_new_blog', 'nouveau_site', 10, 6);        
function nouveau_site($blog_id, $user_id, $domain, $path, $site_id, $meta ) {
    global $wpdb, $tableName;
    $original_blog_id = $wpdb->blogid;
    switch_to_blog($blog_id);
    WP_Advanced_Search_install_data($wpdb->prefix.$tableName); // Créé les données pour le nouveau site
    switch_to_blog($original_blog_id);
}


// Quand ça désactive l'extension, la table est supprimée...
function WP_Advanced_Search_desinstall() {
	global $wpdb, $table_WP_Advanced_Search, $tableName;

	// Pour le multisite (désinstallation pour chaque site)
	if(function_exists('is_multisite') && is_multisite()) {
        $original_blog_id = $wpdb->blogid;
        // Obtient les autres ID du multisites
        $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        foreach($blogids as $blog_id) {
            switch_to_blog($blog_id);
            WP_Advanced_Search_desinstall_data($wpdb->prefix.$tableName);
        }
        switch_to_blog($original_blog_id);  
    } else { // Sinon...
		WP_Advanced_Search_desinstall_data($table_WP_Advanced_Search);
	}
}
function WP_Advanced_Search_desinstall_data($table) {
	global $wpdb;

	// Sélectionne les données de la table
	$select = $wpdb->get_row("SELECT autoCompleteTable FROM ".$table." WHERE id=1");
	
	// Suppression de l'index inversé si existant
	$wpdb->query("DROP TABLE IF EXISTS ".$select->autoCompleteTable);
	
	// Suppression de la table de base
	$wpdb->query("DROP TABLE IF EXISTS ".$table);
}


// Quand le plugin est mise à jour, on relance la fonction
function WP_Advanced_Search_Upgrade() {
    global $wpdb, $WP_Advanced_Search_Version;
    if(get_site_option('wp_advanced_search_version') != $WP_Advanced_Search_Version) { 
		// Pour le multisite
		if(function_exists('is_multisite') && is_multisite()) {
	        $original_blog_id = $wpdb->blogid;
	        // Obtient les autres ID du multisites
	        $blogids = $wpdb->get_col("SELECT blog_id FROM ".$wpdb->blogs);
	        foreach($blogids as $blog_id) {
	            switch_to_blog($blog_id);
	            WP_Advanced_Search_install_update();
	        }
	        switch_to_blog($original_blog_id);
	        return;
	    }
		WP_Advanced_Search_install_update();

		// Mise à jour de la version
		update_site_option("wp_advanced_search_version", $WP_Advanced_Search_Version);
    }
}
add_action('plugins_loaded', 'WP_Advanced_Search_Upgrade');

// Fonction d'update v1.2 vers 3.2
function WP_Advanced_Search_install_update() {
	global $wpdb, $tableName;

	$encodeSQL = $wpdb->query("ALTER TABLE ".$wpdb->prefix.$tableName." DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
	
	$sqlShow = $wpdb->query("SHOW COLUMNS FROM ".$wpdb->prefix.$tableName." LIKE 'categories'");
	if($sqlShow != 1) {
		$sqlUpgrade = $wpdb->query("ALTER TABLE ".$wpdb->prefix.$tableName." ADD categories TEXT");
		// Mise à jour des des nouvelles valeurs par défaut
		$defautUpgrade = array(
			"categories" => serialize(array('toutes'))
		);
		$chp = wp_parse_args($instance, $defautUpgrade);
		$defaultUpgrade = $wpdb->update($wpdb->prefix.$tableName, array('categories' => $chp['categories']), array('id' => 1));
	}
	
	$sqlShowTIS = $wpdb->query("SHOW COLUMNS FROM ".$wpdb->prefix.$tableName." LIKE 'paginationType'");
	if($sqlShowTIS != 1) {
		$tableTISUpgrade = $wpdb->query("ALTER TABLE ".$wpdb->prefix.$tableName." ADD (
			paginationType VARCHAR(50) NOT NULL,
			paginationNbLimit INT NOT NULL,
			paginationDuration INT NOT NULL,
			paginationText VARCHAR(250) NOT NULL
		)");
		$defautsTIS = array(
			"paginationType" => "trigger",
			"paginationNbLimit" => 5,
			"paginationDuration" => 300,
			"paginationText" => "Afficher plus de résultats"
		);
		$fldTIS = wp_parse_args($instance, $defautsTIS);
		$TISUpgrade = $wpdb->update($wpdb->prefix.$tableName, array('paginationType' => $fldTIS['paginationType'], 'paginationNbLimit' => $fldTIS['paginationNbLimit'], 'paginationDuration' => $fldTIS['paginationDuration'], 'paginationText' => $fldTIS['paginationText']), array('id' => 1));
	}
	
	$sqlShowAC = $wpdb->query("SHOW COLUMNS FROM ".$wpdb->prefix.$tableName." LIKE 'autoCompleteActive'");
	if($sqlShowAC != 1) {
		$tableUpgrade = $wpdb->query("ALTER TABLE ".$wpdb->prefix.$tableName." ADD (
		autoCompleteActive BOOLEAN NOT NULL,
		autoCompleteSelector VARCHAR(50) NOT NULL,
		autoCompleteAutofocus BOOLEAN NOT NULL,
		autoCompleteType INT,
		autoCompleteNumber INT,
		autoCompleteTypeSuggest BOOLEAN NOT NULL,
		autoCompleteCreate BOOLEAN NOT NULL,
		autoCompleteTable VARCHAR(50) NOT NULL,
		autoCompleteColumn VARCHAR(50) NOT NULL,
		autoCompleteGenerate BOOLEAN NOT NULL,
		autoCompleteSizeMin TINYINT
		)");
		$defauts = array(
			"autoCompleteActive" => true,
			"autoCompleteSelector" => ".search-field",
			"autoCompleteAutofocus" => false,
			"autoCompleteType" => 0,
			"autoCompleteNumber" => 5,
			"autoCompleteTypeSuggest" => true,
			"autoCompleteCreate" => false,
			"autoCompleteTable" => $wpdb->prefix."autosuggest",
			"autoCompleteColumn" => "words",
			"autoCompleteGenerate" => true,
			"autoCompleteSizeMin" => 2
		);
		$fld = wp_parse_args($instance, $defauts);
		$autoCompleteUpgrade = $wpdb->update($wpdb->prefix.$tableName, array('autoCompleteActive' => $fld['autoCompleteActive'], 'autoCompleteSelector' => $fld['autoCompleteSelector'], 'autoCompleteAutofocus' => $fld['autoCompleteAutofocus'], 'autoCompleteType' => $fld['autoCompleteType'], 'autoCompleteNumber' => $fld['autoCompleteNumber'], 'autoCompleteTypeSuggest' => $fld['autoCompleteTypeSuggest'], 'autoCompleteCreate' => $fld['autoCompleteCreate'], 'autoCompleteTable' => $fld['autoCompleteTable'], 'autoCompleteColumn' => $fld['autoCompleteColumn'], 'autoCompleteGenerate' => $fld['autoCompleteGenerate'], 'autoCompleteSizeMin' => $fld['autoCompleteSizeMin']), array('id' => 1));
		
		// Création de l'index inversé par défaut (pour l'autocomplétion)
		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$fld['autoCompleteTable']." (
					 idindex INT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					 ".$fld['autoCompleteColumn']." VARCHAR(250) NOT NULL)
					 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci"
		);
	}
	
	$sqlShowPH = $wpdb->query("SHOW COLUMNS FROM ".$wpdb->prefix.$tableName." LIKE 'placeholder'");
	if($sqlShowPH != 1) {
		$tablePHUpgrade = $wpdb->query("ALTER TABLE ".$wpdb->prefix.$tableName." ADD (placeholder VARCHAR(200))");
		$defautsPH = array("placeholder" => "");
		$fldPH = wp_parse_args($instance, $defautsPH);
		$PHUpgrade = $wpdb->update($wpdb->prefix.$tableName, array('placeholder' => $fldPH['placeholder']), array('id' => 1));
	}

	$sqlShowID = $wpdb->query("SHOW COLUMNS FROM ".$wpdb->prefix.$tableName." LIKE 'idform'");
	if($sqlShowID != 1) {
		$tableIDUpgrade = $wpdb->query("ALTER TABLE ".$wpdb->prefix.$tableName." ADD (idform VARCHAR(200))");
		$defautsID = array("idform" => "s");
		$fldID = wp_parse_args($instance, $defautsID);
		$IDUpgrade = $wpdb->update($wpdb->prefix.$tableName, array('idform' => $fldID['idform']), array('id' => 1));
	}
	
	$sqlShowCorrect = $wpdb->query("SHOW COLUMNS FROM ".$wpdb->prefix.$tableName." LIKE 'autoCorrectActive'");
	if($sqlShowCorrect != 1) {
		$tableUpgrade = $wpdb->query("ALTER TABLE ".$wpdb->prefix.$tableName." ADD (
			autoCorrectActive BOOLEAN NOT NULL,
			autoCorrectType TINYINT,
			autoCorrectMethod BOOLEAN NOT NULL,
			autoCorrectString TEXT NOT NULL,
			autoCorrectCreate BOOLEAN NOT NULL
		)");
		$defauts = array(
			"autoCorrectActive" => true,
			"autoCorrectType" => 2,
			"autoCorrectMethod" => true,
			"autoCorrectString" => __('Tentez avec une autre orthographe : ', 'wp-advanced-search'),
			"autoCorrectCreate" => false
		);
		$fld = wp_parse_args($instance, $defauts);
		$autoCompleteUpgrade = $wpdb->update($wpdb->prefix.$tableName, array('autoCorrectActive' => $fld['autoCorrectActive'], 'autoCorrectType' => $fld['autoCorrectType'], 'autoCorrectMethod' => $fld['autoCorrectMethod'], 'autoCorrectString' => $fld['autoCorrectString'], 'autoCorrectCreate' => $fld['autoCorrectCreate']), array('id' => 1));
		
		// Création de l'index inversé par défaut (pour l'autocomplétion)
		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."autocorrectindex (
					 idWord INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					 word VARCHAR(200) NOT NULL,
					 metaphone VARCHAR(200) NOT NULL,
					 soundex VARCHAR(200) NOT NULL,
					 theme VARCHAR(200) NOT NULL,
					 coefficient FLOAT(4,1) NOT NULL DEFAULT '1.0')
					 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
	}
}

// Ajout du menu et des sous-menus associés
function WP_Advanced_Search_admin() {
	$page_title		= 'Aide et réglages de WP-Advanced-Search';		// Titre interne à la page de réglages
	$menu_title		= 'Advanced Search';							// Titre du sous-menu
	$capability		= 'manage_options';								// Rôle d'administration qui a accès au sous-menu
	$menu_slug		= 'wp-advanced-search';							// Alias (slug) de la page
	$function		= 'WP_Advanced_Search_Callback';				// Fonction appelée pour afficher la page de réglages
	$function2		= 'WP_Advanced_Search_Callback_Styles';			// Fonction appelée pour afficher la page de gestion des styles
	$function3		= 'WP_Advanced_Search_Callback_Pagination';		// Fonction appelée pour afficher la page d'options pour la pagination
	$function4		= 'WP_Advanced_Search_Callback_Autocorrection';	// Fonction appelée pour afficher la page d'options pour la correction automatique
	$function5		= 'WP_Advanced_Search_Callback_Autocompletion';	// Fonction appelée pour afficher la page d'options pour l'autocomplétion
	$function6		= 'WP_Advanced_Search_Callback_ExportImport';	// Fonction appelée pour afficher la page d'importation et d'exportation
	$function7		= 'WP_Advanced_Search_Callback_Documentation';	// Fonction appelée pour afficher la page de documentation

	add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, plugins_url('img/icon-16.png',__FILE__), 200);
	add_submenu_page($menu_slug, __('Thèmes et styles','wp-advanced-search'), __('Thèmes et styles','wp-advanced-search'), $capability, $function2, $function2);
	add_submenu_page($menu_slug, __('Options des SERP','wp-advanced-search'), __('Options des SERP','wp-advanced-search'), $capability, $function3, $function3);
	add_submenu_page($menu_slug, __('Correction automatique','wp-advanced-search'), __('Correction automatique','wp-advanced-search'), $capability, $function4, $function4);
	add_submenu_page($menu_slug, __('Autocomplétion','wp-advanced-search'), __('Autocomplétion','wp-advanced-search'), $capability, $function5, $function5);
	add_submenu_page($menu_slug, __('Importer/Exporter','wp-advanced-search'), __('Importer/Exporter','wp-advanced-search'), $capability, $function6, $function6);
	add_submenu_page($menu_slug, __('Documentation','wp-advanced-search'), __('Documentation','wp-advanced-search'), $capability, $function7, $function7);
}
add_action('admin_menu', 'WP_Advanced_Search_admin');

// Blocage de la soumission du formulaire (avec ou sans placeholder)
function WP_Advanced_Search_Stop_Form($form) {
	global $wpdb, $tableName;

	// Sélection des données dans la base de données		
	$select = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix.$tableName." WHERE id=1");
	
	// Variables utiles (name et placeholder)
	$idSearch = $select->idform;
	$placeholder = $select->placeholder;

	// Ajoute le code Javascript bloquant
	if(!empty($idSearch)) {
		if(empty($placeholder)) {
			$form = str_ireplace('<form', '<form onsubmit="if(document.getElementById(\''.$idSearch.'\').value == \'\') { return false; }"', $form);
		} else {
			$form = str_ireplace('<form', '<form onsubmit="if(document.getElementById(\''.$idSearch.'\').value == \''.$placeholder.'\' || (document.getElementById(\''.$idSearch.'\').placeholder == \''.$placeholder.'\' && document.getElementById(\''.$idSearch.'\').value == \'\')) { return false; }"', $form);
		}
	}
	
	// Retourne le "nouveau" formulaire
	return $form;
}
add_filter('get_search_form', 'WP_Advanced_Search_Stop_Form');

// Ajout d'une feuille de style pour l'admin
function WP_Advanced_Search_Admin_CSS() {
	$handle = 'admin_css';
	$style	= plugins_url('css/wp-advanced-search-admin.css', __FILE__);
	wp_enqueue_style($handle, $style, 15);
}
add_action('admin_enqueue_scripts', 'WP_Advanced_Search_Admin_CSS');

// Ajout conditionné d'une feuille de style personnalisée pour la pagination
function WP_Advanced_Search_CSS($bool) {
	if($bool == "vide") {
		$url = plugins_url('css/templates/style-empty.css',__FILE__);
		wp_register_style('style-empty', $url);
		wp_enqueue_style('style-empty');
	} else
	if($bool == "c-blue") {
		$url = plugins_url('css/templates/classic-blue/style-bleu.css',__FILE__);
		wp_register_style('style-bleu', $url);
		wp_enqueue_style('style-bleu');
	} else
	if($bool == "c-black") {
		$url = plugins_url('css/templates/classic-black/style-noir.css',__FILE__);
		wp_register_style('style-noir', $url);
		wp_enqueue_style('style-noir');
	} else
	if($bool == "c-red") {
		$url = plugins_url('css/templates/classic-red/style-rouge.css',__FILE__);
		wp_register_style('style-rouge', $url);
		wp_enqueue_style('style-rouge');
	} else
	if($bool == "geek-zone") {
		$url = plugins_url('css/templates/geek-zone/style-geek-zone.css',__FILE__);
		wp_register_style('style-geek-zone', $url);
		wp_enqueue_style('style-geek-zone');
	} else
	if($bool == "flat") {
		$url = plugins_url('css/templates/flat-design/style-flat-design.css',__FILE__);
		wp_register_style('style-flat-design', $url);
		wp_enqueue_style('style-flat-design');
	} else
	if($bool == "flat-2") {
		$url = plugins_url('css/templates/flat-design-blue/style-flat-design-blue.css',__FILE__);
		wp_register_style('style-flat-design-blue', $url);
		wp_enqueue_style('style-flat-design-blue');
	} else
	if($bool == "flat-color") {
		$url = plugins_url('css/templates/colored-flat-design/style-colored-flat-design.css',__FILE__);
		wp_register_style('style-colored-flat-design', $url);
		wp_enqueue_style('style-colored-flat-design');
	}
	if($bool == "o-grey") {
		$url = plugins_url('css/templates/orange-grey/style-orange-design.css',__FILE__);
		wp_register_style('style-orange-grey', $url);
		wp_enqueue_style('style-orange-grey');
	}
	if($bool == "google") {
		$url = plugins_url('css/templates/google-style/style-google.css',__FILE__);
		wp_register_style('style-google', $url);
		wp_enqueue_style('style-google');
	}
	if($bool == "twocol") {
		$url = plugins_url('css/templates/n-columns/style-2-columns.css',__FILE__);
		wp_register_style('style-2-columns', $url);
		wp_enqueue_style('style-2-columns');
	}
	if($bool == "threecol") {
		$url = plugins_url('css/templates/n-columns/style-3-columns.css',__FILE__);
		wp_register_style('style-3-columns', $url);
		wp_enqueue_style('style-3-columns');
	}
	add_action('wp_enqueue_scripts', 'WP_Advanced_Search_CSS');
}

// Ajout conditionné d'une feuille de style personnalisée pour la pagination
function WP_Advanced_Search_Pagination_CSS($bool) {
	if($bool == "vide") {
		$url = plugins_url('css/pagination/style-pagination-empty.css',__FILE__);
		wp_register_style('style-pagination-empty', $url);
		wp_enqueue_style('style-pagination-empty');
	} else
	if($bool == "c-blue") {
		$url = plugins_url('css/pagination/style-pagination-bleu.css',__FILE__);
		wp_register_style('style-pagination-bleu', $url);
		wp_enqueue_style('style-pagination-bleu');
	} else
	if($bool == "c-black") {
		$url = plugins_url('css/pagination/style-pagination-noir.css',__FILE__);
		wp_register_style('style-pagination-noir', $url);
		wp_enqueue_style('style-pagination-noir');
	} else
	if($bool == "c-red") {
		$url = plugins_url('css/pagination/style-pagination-rouge.css',__FILE__);
		wp_register_style('style-pagination-rouge', $url);
		wp_enqueue_style('style-pagination-rouge');
	} else
	if($bool == "geek-zone") {
		$url = plugins_url('css/pagination/geek-zone.css',__FILE__);
		wp_register_style('style-pagination-geek-zone', $url);
		wp_enqueue_style('style-pagination-geek-zone');
	} else
	if($bool == "flat") {
		$url = plugins_url('css/pagination/flat-design.css',__FILE__);
		wp_register_style('style-pagination-flat-design', $url);
		wp_enqueue_style('style-pagination-flat-design');
	} else
	if($bool == "flat-2") {
		$url = plugins_url('css/pagination/flat-design-blue.css',__FILE__);
		wp_register_style('style-pagination-flat-design-blue', $url);
		wp_enqueue_style('style-pagination-flat-design-blue');
	} else
	if($bool == "flat-color") {
		$url = plugins_url('css/pagination/colored-flat-design.css',__FILE__);
		wp_register_style('style-pagination-colored-flat-design', $url);
		wp_enqueue_style('style-pagination-colored-flat-design');
	}
	if($bool == "orange-grey") {
		$url = plugins_url('css/pagination/orange-design.css',__FILE__);
		wp_register_style('style-pagination-orange-design', $url);
		wp_enqueue_style('style-pagination-orange-design');
	}
	if($bool == "google") {
		$url = plugins_url('css/pagination/style-pagination-google-style.css',__FILE__);
		wp_register_style('style-pagination-google', $url);
		wp_enqueue_style('style-pagination-google');
	}
	if($bool == "twocol" || $bool == "threecol") {
		$url = plugins_url('css/pagination/style-pagination-n-columns.css',__FILE__);
		wp_register_style('style-pagination-n-columns', $url);
		wp_enqueue_style('style-pagination-n-columns');
	}
	add_action('wp_enqueue_scripts', 'WP_Advanced_Search_Pagination_CSS');
}

// Inclusion des options de réglages
include('WP-Advanced-Search-Options.php');

// Inclusion des options de style
include_once('WP-Advanced-Search-Styles.php');

// Inclusion des options de pagination
include_once('WP-Advanced-Search-Pagination.php');

// Inclusion des options pour la correction automatique
include_once('WP-Advanced-Search-Autocorrection.php');

// Inclusion des options pour l'autocomplétion
include_once('WP-Advanced-Search-Autocompletion.php');

// Inclusion des options d'importation et exportation'
include_once('WP-Advanced-Search-ImportExport.php');

// Inclusion des options de documentation
include_once('WP-Advanced-Search-Documentation.php');

// Inclusion de la fonction finale
include_once('WP-Advanced-Search-Function.php');

// Inclusion des fichiers utiles (trigger, scroll infini et autocomplétion)
include('WP-Advanced-Search-Includes.php');
?>