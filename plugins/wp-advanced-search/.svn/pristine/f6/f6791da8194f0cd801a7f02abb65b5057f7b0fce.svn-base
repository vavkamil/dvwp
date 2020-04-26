<?php
// Fonction d'affichage de la page d'aide et de réglages de l'extension
function WP_Advanced_Search_Callback_Pagination() {
	global $wpdb, $tableName; // insérer les variables globales

	// Déclencher la fonction de mise à jour (upload)
	if(isset($_POST['wp_advanced_search_action']) && $_POST['wp_advanced_search_action'] == __('Enregistrer' , 'wp-advanced-search')) {
		WP_Advanced_Search_update_pagination();
	}

	/* --------------------------------------------------------------------- */
	/* ------------------------ Affichage de la page ----------------------- */
	/* --------------------------------------------------------------------- */
	echo '<div class="wrap advanced-search-admin">';
	echo '<div class="icon32 icon"><br /></div>';
	echo '<h2>'; _e('Gestion des SERP (résultats de recherche)','wp-advanced-search'); echo '</h2><br/>';
	echo '<div class="text">';
	_e('<strong>WP-Advanced-Search</strong> permet d\'activer une pagination personnalisable, un système de scroll infini ou tout simplement un système de "trigger" pour afficher les résultats après un clic sur un lien.', 'wp-advanced-search'); echo '<br/>';
	_e('Modifiez les options de votre choix, le style final et les libellés associés pour obtenir un résultat adéquat.', 'wp-advanced-search');	echo '<br/>';
	echo '</div>';
	
	// Sélection des données dans la base de données		
	$select = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix.$tableName." WHERE id=1");
?> 
        <form method="post" action="">
       	<div class="block">
            <div class="col">
                <h4><?php _e('Réglages et Styles généraux','wp-advanced-search'); ?></h4>
                <p class="tr">
                    <select name="wp_advanced_search_pagination_active" id="wp_advanced_search_pagination_active">
                        <option value="1" <?php if($select->paginationActive == true) { echo 'selected="selected"'; } ?>><?php _e('Oui','wp-advanced-search'); ?></option>
                        <option value="0" <?php if($select->paginationActive == false) { echo 'selected="selected"'; } ?>><?php _e('Non','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_pagination_active"><strong><?php _e('Activer la pagination','wp-advanced-search'); ?></strong></label>
                </p>
				<p class="tr">
                    <select name="wp_advanced_search_pagination_type" id="wp_advanced_search_pagination_type">
                        <option value="classic" <?php if($select->paginationType == "classic") { echo 'selected="selected"'; } ?>><?php _e('Pagination classique','wp-advanced-search'); ?></option>
                        <option value="trigger" <?php if($select->paginationType == "trigger") { echo 'selected="selected"'; } ?>><?php _e('Trigger (au clic)','wp-advanced-search'); ?></option>
						<option value="infinite" <?php if($select->paginationType == "infinite") { echo 'selected="selected"'; } ?>><?php _e('Scroll infini','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_pagination_type"><strong><?php _e('Type d\'affichage des résultats','wp-advanced-search'); ?></strong></label>
                </p>
                <p class="tr">
                	<select name="wp_advanced_search_pagination_style" id="wp_advanced_search_pagination_style">
                        <option value="aucun" <?php if($select->paginationStyle == "aucun") { echo 'selected="selected"'; } ?>><?php _e('Aucun style CSS','wp-advanced-search'); ?></option>
                        <option value="vide" <?php if($select->paginationStyle == "vide") { echo 'selected="selected"'; } ?>><?php _e('Feuille CSS Vide','wp-advanced-search'); ?></option>
                        <option value="c-blue" <?php if($select->paginationStyle == "c-blue") { echo 'selected="selected"'; } ?>><?php _e('Classic blue','wp-advanced-search'); ?></option>
                        <option value="c-red" <?php if($select->paginationStyle == "c-red") { echo 'selected="selected"'; } ?>><?php _e('Classic red','wp-advanced-search'); ?></option>
                        <option value="c-black" <?php if($select->paginationStyle == "c-black") { echo 'selected="selected"'; } ?>><?php _e('Classic black','wp-advanced-search'); ?></option>
                        <option value="geek-zone" <?php if($select->paginationStyle == "geek-zone") { echo 'selected="selected"'; } ?>><?php _e('Geek zone','wp-advanced-search'); ?></option>
                        <option value="flat" <?php if($select->paginationStyle == "flat") { echo 'selected="selected"'; } ?>><?php _e('Sober flat design','wp-advanced-search'); ?></option>
                        <option value="flat-2" <?php if($select->paginationStyle == "flat-2") { echo 'selected="selected"'; } ?>><?php _e('Sober flat design blue','wp-advanced-search'); ?></option>
                        <option value="flat-color" <?php if($select->paginationStyle == "flat-color") { echo 'selected="selected"'; } ?>><?php _e('Colored flat design','wp-advanced-search'); ?></option>
						<option value="orange-grey" <?php if($select->paginationStyle == "orange-grey") { echo 'selected="selected"'; } ?>><?php _e('New Orange Grey','wp-advanced-search'); ?></option>
						<option value="google" <?php if($select->paginationStyle == "google") { echo 'selected="selected"'; } ?>><?php _e('Google style','wp-advanced-search'); ?></option>
						<option value="twocol" <?php if($select->paginationStyle == "twocol") { echo 'selected="selected"'; } ?>><?php _e('2 columns style','wp-advanced-search'); ?></option>
						<option value="threecol" <?php if($select->paginationStyle == "threecol") { echo 'selected="selected"'; } ?>><?php _e('3 columns style','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_pagination_style"><strong><?php _e('Style CSS pour la pagination','wp-advanced-search'); ?></strong></label>
                </p>
				<h4><?php _e('Options pour le trigger ou le scroll infini','wp-advanced-search'); ?></h4>
                <p class="tr">
                    <select name="wp_advanced_search_pagination_nbLimit" id="wp_advanced_search_pagination_nbLimit">
                        <?php for($i = 1; $i < 21; $i++) { ?>
							<option value="<?php echo $i; ?>" <?php if($select->paginationNbLimit == $i) { echo 'selected="selected"'; } ?>><?php echo $i; ?></option>
						<?php }	?>
                    </select>
                    <label for="wp_advanced_search_pagination_nbLimit"><strong><?php _e('Nombre de résultats par palier affiché','wp-advanced-search'); ?></strong></label>
                </p>
				<p class="tr">
                    <select name="wp_advanced_search_pagination_duration" id="wp_advanced_search_pagination_duration">
                        <?php for($i = 2000; $i >= 0; $i = $i - 100) { ?>
							<option value="<?php echo $i; ?>" <?php if($select->paginationDuration == $i) { echo 'selected="selected"'; } ?>><?php echo $i." ms"; ?></option>
						<?php }	?>
                    </select>
                    <label for="wp_advanced_search_pagination_duration"><strong><?php _e('Délai de traitement (en ms)','wp-advanced-search'); ?></strong></label>
                </p>
				<p class="tr">
                    <input value="<?php echo $select->paginationText; ?>" type="text" name="wp_advanced_search_pagination_text" id="wp_advanced_search_pagination_text" />
                    <label for="wp_advanced_search_pagination_text"><strong><?php _e('Texte affiché pour le trigger','wp-advanced-search'); ?></strong></label>
                </p>
            </div>
            <div class="col">
                <h4><?php _e('Libellés et options de la pagination classique','wp-advanced-search'); ?></h4>
                <p class="tr">
                    <select name="wp_advanced_search_pagination_firstlast" id="wp_advanced_search_pagination_firstlast">
                        <option value="1" <?php if($select->paginationFirstLast == true) { echo 'selected="selected"'; } ?>><?php _e('Oui','wp-advanced-search'); ?></option>
                        <option value="0" <?php if($select->paginationFirstLast == false) { echo 'selected="selected"'; } ?>><?php _e('Non','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_pagination_firstlast"><strong><?php _e('Affichage de "première page" et "dernière page" ?','wp-advanced-search'); ?></strong></label>
                </p>
                <p class="tr">
                    <select name="wp_advanced_search_pagination_prevnext" id="wp_advanced_search_pagination_prevnext">
                        <option value="1" <?php if($select->paginationPrevNext == true) { echo 'selected="selected"'; } ?>><?php _e('Oui','wp-advanced-search'); ?></option>
                        <option value="0" <?php if($select->paginationPrevNext == false) { echo 'selected="selected"'; } ?>><?php _e('Non','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_pagination_prevnext"><strong><?php _e('Affichage de "précédent" et "suivant" ?','wp-advanced-search'); ?></strong></label>
                </p>
                <p class="tr">
                    <input value="<?php echo $select->paginationFirstPage; ?>" name="wp_advanced_search_pagination_firstpage" id="wp_advanced_search_pagination_firstpage" type="text" />
                    <label for="wp_advanced_search_pagination_firstpage"><strong><?php _e('Texte pour "première page"','wp-advanced-search'); ?></strong></label>
                </p>
                <p class="tr">
                    <input value="<?php echo $select->paginationLastPage; ?>" name="wp_advanced_search_pagination_lastpage" id="wp_advanced_search_pagination_lastpage" type="text" />
                    <label for="wp_advanced_search_pagination_lastpage"><strong><?php _e('Texte pour "dernière page"','wp-advanced-search'); ?></strong></label>
                </p>
                <p class="tr">
                    <input value="<?php echo $select->paginationPrevText; ?>" name="wp_advanced_search_pagination_prevtext" id="wp_advanced_search_pagination_prevtext" type="text" />
                    <label for="wp_advanced_search_pagination_prevtext"><strong><?php _e('Texte pour "précédent"','wp-advanced-search'); ?></strong></label>
                </p>
                <p class="tr">
                    <input value="<?php echo $select->paginationNextText; ?>" name="wp_advanced_search_pagination_nexttext" id="wp_advanced_search_pagination_nexttext" type="text" />
                    <label for="wp_advanced_search_pagination_nexttext"><strong><?php _e('Texte pour "suivant"','wp-advanced-search'); ?></strong></label>
                </p>
            </div>
			<p class="clear"></p>
			<p><input type="submit" name="wp_advanced_search_action" class="button-primary" value="<?php _e('Enregistrer' , 'wp-advanced-search'); ?>" /></p>
        </div>
		<p class="clear"></p>
        </form>
<?php
	echo '</div>'; // Fin de la page d'admin
} // Fin de la fonction Callback

// Mise à jour des données par défaut
function WP_Advanced_Search_update_pagination() {
	global $wpdb, $tableName; // insérer les variables globales

	// Pagination
	$wp_advanced_search_pagination_active		= $_POST['wp_advanced_search_pagination_active'];
	$wp_advanced_search_pagination_style		= $_POST['wp_advanced_search_pagination_style'];
	$wp_advanced_search_pagination_firstlast	= $_POST['wp_advanced_search_pagination_firstlast'];
	$wp_advanced_search_pagination_prevnext		= $_POST['wp_advanced_search_pagination_prevnext'];
	$wp_advanced_search_pagination_firstpage	= $_POST['wp_advanced_search_pagination_firstpage'];
	$wp_advanced_search_pagination_lastpage		= $_POST['wp_advanced_search_pagination_lastpage'];
	$wp_advanced_search_pagination_prevtext		= $_POST['wp_advanced_search_pagination_prevtext'];
	$wp_advanced_search_pagination_nexttext		= $_POST['wp_advanced_search_pagination_nexttext'];
	$wp_advanced_search_pagination_type			= $_POST['wp_advanced_search_pagination_type'];
	$wp_advanced_search_pagination_nbLimit		= $_POST['wp_advanced_search_pagination_nbLimit'];
	$wp_advanced_search_pagination_duration		= $_POST['wp_advanced_search_pagination_duration'];
	$wp_advanced_search_pagination_text			= $_POST['wp_advanced_search_pagination_text'];
		
	$wp_advanced_search_update = $wpdb->update(
		$wpdb->prefix.$tableName,
		array(
			"paginationActive" => $wp_advanced_search_pagination_active,
			"paginationStyle" => $wp_advanced_search_pagination_style,
			"paginationFirstLast" => $wp_advanced_search_pagination_firstlast,
			"paginationPrevNext" => $wp_advanced_search_pagination_prevnext,
			"paginationFirstPage" => $wp_advanced_search_pagination_firstpage,
			"paginationLastPage" => $wp_advanced_search_pagination_lastpage,
			"paginationPrevText" => $wp_advanced_search_pagination_prevtext,
			"paginationNextText" => $wp_advanced_search_pagination_nexttext,
			"paginationType" => $wp_advanced_search_pagination_type,
			"paginationNbLimit" => $wp_advanced_search_pagination_nbLimit,
			"paginationDuration" => $wp_advanced_search_pagination_duration,
			"paginationText" => $wp_advanced_search_pagination_text
		), 
		array('id' => 1)
	);
}
?>