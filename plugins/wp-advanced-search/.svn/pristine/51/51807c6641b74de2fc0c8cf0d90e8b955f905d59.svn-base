<?php
// Fonction d'affichage de la page d'option pour la correction automatique
function WP_Advanced_Search_Callback_Autocorrection() {
	global $wpdb, $tableName; // insérer les variables globales

	if(isset($_POST['wp_advanced_search_action']) && $_POST['wp_advanced_search_action'] == __('Enregistrer', 'wp-advanced-search')) {
		// Lancement de la fonction de mise à jour des données
		WP_Advanced_Search_update_autocorrection();
		
		// Création de la table d'index inversé si l'option de création est sur "oui"
		if(isset($_POST['wp_advanced_search_autocorrection_create']) && $_POST['wp_advanced_search_autocorrection_create'] == true) {
			$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."autocorrectindex (
						 idWord INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
						 word VARCHAR(200) NOT NULL,
						 metaphone VARCHAR(200) NOT NULL,
						 soundex VARCHAR(200) NOT NULL,
						 theme VARCHAR(200) NOT NULL,
						 coefficient FLOAT(4,1) NOT NULL DEFAULT '1.0')
						 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
		}
		
		// Suppression de la table d'index inversé si l'option de suppression est sur "oui"
		if(isset($_POST['wp_advanced_search_autocorrection_delete']) && $_POST['wp_advanced_search_autocorrection_delete'] == true) {
			$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."autocorrectindex");
		}
	}
	
	// Ajout des mots clés dans l'index
	if(isset($_POST['wp_advanced_search_action_addwords'])) {
		WP_Advanced_Search_Autocorrection_AddWords($_POST['wp_advanced_search_autocorrection_addwords']);
	}

	// Ajout des tags dans l'index
    if(isset($_POST['wp_advanced_search_action_addtags'])) {
        WP_Advanced_Search_Autocorrection_AddWords($_POST['wp_advanced_search_autocorrection_addtags']);
    }
	
	// Déclencher la fonction de suppression des extraits
	if(isset($_POST['wp_advanced_search_action_deletewords'])) {
		WP_Advanced_Search_Autocorrection_DeleteWords();
	}

	/* --------------------------------------------------------------------- */
	/* ------------------------ Affichage de la page ----------------------- */
	/* --------------------------------------------------------------------- */
	echo '<div class="wrap advanced-search-admin">';
	echo '<div class="icon32 icon"><br /></div>';
	echo '<h2>'; _e('Gestion des corrections automatiques des recherches','wp-advanced-search'); echo '</h2><br/>';
	echo '<div class="text">';
	_e('<strong>WP-Advanced-Search</strong> dispose d\'un système de correction orthographique automatique pour vos recherches.', 'wp-advanced-search'); echo '<br/>';
	_e('Modifiez les options pour obtenir un résultat adéquat.', 'wp-advanced-search');	echo '<br/>';
	echo '</div>';

	// Sélection des données dans la base de données		
	$select = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix.$tableName." WHERE id=1");
?> 
        <form method="post" action="">
       	<div class="block">
            <div class="col">
                <h4><?php _e('Options générales','wp-advanced-search'); ?></h4>
                <p class="tr">
                    <select name="wp_advanced_search_autocorrection_active" id="wp_advanced_search_autocorrection_active">
                        <option value="1" <?php if($select->autoCorrectActive == true) { echo 'selected="selected"'; } ?>><?php _e('Oui','wp-advanced-search'); ?></option>
                        <option value="0" <?php if($select->autoCorrectActive == false) { echo 'selected="selected"'; } ?>><?php _e('Non','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_autocorrection_active"><strong><?php _e('Activer la correction automatique ?','wp-advanced-search'); ?></strong></label>
                </p>
                <p class="tr">
                    <select name="wp_advanced_search_autocorrection_type" id="wp_advanced_search_autocorrection_type">
                        <option value="0" <?php if($select->autoCorrectType == 0) { echo 'selected="selected"'; } ?>><?php _e('Proposition des corrections','wp-advanced-search'); ?></option>
                        <option value="1" <?php if($select->autoCorrectType == 1) { echo 'selected="selected"'; } ?>><?php _e('Pages de résultats corrigées','wp-advanced-search'); ?></option>
                        <option value="2" <?php if($select->autoCorrectType == 2) { echo 'selected="selected"'; } ?>><?php _e('Corrections + SERP corrigées','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_autocorrection_type"><strong><?php _e('Choix de l\'affichage final des corrections','wp-advanced-search'); ?></strong></label>
                    <br/><em><?php _e('Affiche la requête corrigée, les résultats de recherche corrigés (SERP) ou les deux options en même temps','wp-advanced-search'); ?></em>
                </p>
                <p class="tr">
                    <select name="wp_advanced_search_autocorrection_method" id="wp_advanced_search_autocorrection_method">
                        <option value="1" <?php if($select->autoCorrectActive == true) { echo 'selected="selected"'; } ?>><?php _e('Oui','wp-advanced-search'); ?></option>
                        <option value="0" <?php if($select->autoCorrectActive == false) { echo 'selected="selected"'; } ?>><?php _e('Non','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_autocorrection_method"><strong><?php _e('Utiliser les correspondances directes ?','wp-advanced-search'); ?></strong></label>
                    <br/><em><?php _e('L\'option permet d\'améliorer les performances quand l\'index inversé est bien rempli','wp-advanced-search'); ?></em>
                </p>
                <p class="tr">
                    <input value="<?php echo $select->autoCorrectString; ?>" name="wp_advanced_search_autocorrection_string" id="wp_advanced_search_autocorrection_string" type="text" />
                    <label for="wp_advanced_search_autocorrection_string"><strong><?php _e('Phrase qui précède la requête corrigée','wp-advanced-search'); ?></strong></label>
                </p>

                <h4><?php _e('Personnalisation technique (optionnel)','wp-advanced-search'); ?></h4>
				<?php
					// Vérifie que l'index inversé existe ou non et affiche des options de création ou de suppression en fonction...
                    $sqlShow = $wpdb->get_results("SELECT COUNT(*) FROM ".$wpdb->prefix."autocorrectindex");
					$rowCount = $wpdb->num_rows;
                    if($rowCount == 0) {
                ?>
                <p class="tr">
                    <select name="wp_advanced_search_autocorrection_create" id="wp_advanced_search_autocorrection_create">
                        <option value="1"><?php _e('Oui','wp-advanced-search'); ?></option>
                        <option value="0" selected="selected"><?php _e('Non','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_autocorrection_create"><strong><?php _e('Créer l\'index inversé ?','wp-advanced-search'); ?></strong></label>
                    <br/><em><?php _e('Cette option s\'active uniquement quand l\'index inversé n\'est pas créé (recommandé)','wp-advanced-search'); ?></em>
                </p>
				<?php
                    } else {
                ?>
                <p class="tr">
                    <select name="wp_advanced_search_autocorrection_delete" id="wp_advanced_search_autocorrection_delete">
                        <option value="1"><?php _e('Oui','wp-advanced-search'); ?></option>
                        <option value="0" selected="selected"><?php _e('Non','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_autocorrection_delete" class="autoCorrectDelete"><strong><?php _e('Supprimer l\'index inversé ?','wp-advanced-search'); ?></strong></label>
                    <br/><em><?php _e('Cette option s\'active uniquement si l\'index inversé existe (déconseillé)','wp-advanced-search'); ?></em>
                </p>

				<p class="clear"></p>
                <p><input type="submit" name="wp_advanced_search_action" class="button-primary" value="<?php _e('Enregistrer' , 'wp-advanced-search'); ?>" /></p>
            </div>
            <div class="col">
                <h4><?php _e('Gestion des mots et expressions','wp-advanced-search'); ?></h4>
                <p class="tr2">
                	<label for="wp_advanced_search_autocorrection_addwords"><strong><?php _e('Ajouter des mots et expressions bien orthographiés dans l\'index','wp-advanced-search'); ?></strong></label>
                    <textarea name="wp_advanced_search_autocorrection_addwords" id="wp_advanced_search_autocorrection_addwords"></textarea>
                    <br/><em><?php _e('Séparez les mots ou expressions par des virgules !<br/>Exemple --> moteur, moteur de recherche, recherche, advanced, search<br/>N.B. : les doublons ne s\'ajoutent pas...','wp-advanced-search'); ?></em>
                    <br/><input type="submit" name="wp_advanced_search_action_addwords" class="button-primary" value="<?php _e('Ajouter à l\'index', 'wp-advanced-search'); ?>" />
                </p>
                <p class="tr2">
                    <label for="wp_advanced_search_autocorrection_addtags"><strong><?php _e('Ajouter des tags existants dans l\'index inversé','wp-advanced-search'); ?></strong></label>
                    <select name="wp_advanced_search_autocorrection_addtags[]" multiple="multiple" id="deleteSelect" size="10">
                    <?php
                        $tags = get_tags();
                        foreach($tags as $tag) {
                    ?>
                        <option value="<?php echo $tag->name; ?>"><?php echo $tag->name; ?></option>
                    <?php
                        }
                    ?>
                    </select>
                    <br/><em><?php _e('Sélectionner les tags qui vous intéressent pour les ajouter à l\'index inversé','wp-advanced-search'); ?></em>
                    <br/><input type="submit" name="wp_advanced_search_action_addtags" class="button-primary" value="<?php _e('Ajouter à l\'index', 'wp-advanced-search'); ?>" />
                </p>
                <p class="tr2">
                	<label for="wp_advanced_search_autocorrection_deletewords"><strong><?php _e('Supprimer des mots et expressions dans l\'index (si nécessaire)','wp-advanced-search'); ?></strong></label>
                    <select name="wp_advanced_search_autocorrection_deletewords[]" multiple="multiple" id="deleteSelect" size="10">
					<?php
						$expressions = $wpdb->get_results("SELECT word FROM ".$wpdb->prefix."autocorrectindex", ARRAY_N);
						$words = array();
						foreach($expressions as $word) {
							if(preg_match('/["]{1}([^"]+[^"]+)+["]{1}/i', $word[0])) {
								$words[] = substr($word[0], 1, strlen($word[0])-2);
							} else {
								$words[] = $word[0];
							}
						}
						natcasesort($words); // Réordonne les résultats naturellement
						foreach($words as $word) {
					?>
						<option value="<?php echo $word; ?>"><?php echo $word; ?></option>
					<?php
						}
					?>
					</select>
                    <br/><em><?php _e('Sélectionner les mots et expressions qui ne vous conviennent pas et supprimer-les...','wp-advanced-search'); ?></em>
                    <br/><input type="submit" name="wp_advanced_search_action_deletewords" onclick="javascript:return(confirm('<?php _e('&Ecirc;tes-vous sûrs de vouloir supprimer ces mots et expressions ?','WP-Advanced-Search'); ?>'));" class="button-primary" value="<?php _e('Supprimer de l\'index', 'wp-advanced-search'); ?>" />
                </p>
                <?php
					}
				?>
            </div>
        </div>
        </form>
<?php
	echo '</div>'; // Fin de la page d'admin
} // Fin de la fonction Callback

// Mise à jour des données par défaut
function WP_Advanced_Search_update_autocorrection() {
	global $wpdb, $tableName; // insérer les variables globales

	// Pagination
	$wp_advanced_search_autocorrection_active	= $_POST['wp_advanced_search_autocorrection_active'];
	$wp_advanced_search_autocorrection_type		= $_POST['wp_advanced_search_autocorrection_type'];
	$wp_advanced_search_autocorrection_method	= $_POST['wp_advanced_search_autocorrection_method'];
	$wp_advanced_search_autocorrection_string	= $_POST['wp_advanced_search_autocorrection_string'];
	$wp_advanced_search_autocorrection_create	= $_POST['wp_advanced_search_autocorrection_create'];
		
	$wp_advanced_search_update = $wpdb->update(
		$wpdb->prefix.$tableName,
		array(
			"autoCorrectActive" => $wp_advanced_search_autocorrection_active,
			"autoCorrectType" => $wp_advanced_search_autocorrection_type,
			"autoCorrectMethod" => $wp_advanced_search_autocorrection_method,
			"autoCorrectString" => $wp_advanced_search_autocorrection_string,
			"autoCorrectCreate" => $wp_advanced_search_autocorrection_create
		), 
		array('id' => 1)
	);
}

// Function d'ajout des mots clés dans l'index (si rempli !)
function WP_Advanced_Search_Autocorrection_AddWords($datas) {
	global $wpdb; // insérer les variables globales

	// Sélection des données dans les tables de la base de données		
	$selectWords = $wpdb->get_results("SELECT word FROM ".$wpdb->prefix."autocorrectindex", ARRAY_A);

	// Récupération des mots et expressions dans un tableau de données
    if(is_string($datas)) { // Si c'est une chaîne séparé par des virgules
        $expressions = array_map('trim', explode(',',htmlspecialchars($datas)));
    } elseif(is_array($datas)) { // Si c'est un tableau de mots (tags, etc.)
        $expressions = $datas;
    }

	// Récupération des mots dans l'index inversé
	$selected = array();
	foreach($selectWords as $w) {
		$selected[] = $w['word'];
	}

	foreach($expressions as $word) {
		if(strlen($word) > 1) {
			// Adapte les expressions précises pour les ajouter comme prévu dans l'index
			if(preg_match("#[[:blank:]]+#i", trim($word))) {
				$word = '"'.$word.'"';
			}

			// N'ajoute que si le mot ou l'expression n'existe pas
			if(!in_array($word, $selected)) {
				// Mesure les valeurs "métaphone" et "soundex" pour chaque mot
				$metaphone = metaphone($word);
				$soundex = soundex($word);

				// Ajoute les données dans la table de l'index
				$prepare = $wpdb->prepare("INSERT INTO ".$wpdb->prefix."autocorrectindex (word, metaphone, soundex) VALUES (%s, %s, %s)", array($word, $metaphone, $soundex));
				$wpdb->query($prepare);
			}
		}
	}
}

// Suppression des extraits sélectionnés
function WP_Advanced_Search_Autocorrection_DeleteWords() {
	global $wpdb; // insérer les variables globales

	$tabWords = $_POST['wp_advanced_search_autocorrection_deletewords'];
	foreach($tabWords as $word) {
		$wpdb->delete($wpdb->prefix."autocorrectindex", array("word" => esc_sql($word)));
	}
}
?>