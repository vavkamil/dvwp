<?php
// Fonction d'affichage de la page d'option pour l'autocomplétion
function WP_Advanced_Search_Callback_Autocompletion() {
	global $wpdb, $tableName; // insérer les variables globales

	if(isset($_POST['wp_advanced_search_action']) && $_POST['wp_advanced_search_action'] == __('Enregistrer', 'wp-advanced-search')) {
		// Lancement de la fonction de mise à jour des données
		WP_Advanced_Search_update_autocompletion();
		
		// Création de la table d'index inversé si l'option de création est sur "oui"
		if(isset($_POST['wp_advanced_search_autocompletion_create']) && $_POST['wp_advanced_search_autocompletion_create'] == true) {
			$wpdb->query("CREATE TABLE IF NOT EXISTS ".$_POST['wp_advanced_search_autocompletion_table']." (
						 idindex INT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY,
						 ".$_POST['wp_advanced_search_autocompletion_column']." VARCHAR(250) NOT NULL)
						 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci"
			);
		}
		
		// Suppression de la table d'index inversé si l'option de suppression est sur "oui"
		if(isset($_POST['wp_advanced_search_autocompletion_delete']) && $_POST['wp_advanced_search_autocompletion_delete'] == true) {
			$wpdb->query("DROP TABLE IF EXISTS ".$_POST['wp_advanced_search_autocompletion_table']);
		}
	}
	
	// Ajout des mots clés dans l'index
	if(isset($_POST['wp_advanced_search_action_addwords'])) {
		WP_Advanced_Search_Autocompletion_AddWords($_POST['wp_advanced_search_autocompletion_addwords']);
	}

    // Ajout des tags dans l'index
    if(isset($_POST['wp_advanced_search_action_addtags'])) {
        WP_Advanced_Search_Autocompletion_AddWords($_POST['wp_advanced_search_autocompletion_addtags']);
    }
	
	// Déclencher la fonction de suppression des extraits
	if(isset($_POST['wp_advanced_search_action_deletewords'])) {
		WP_Advanced_Search_Autocompletion_DeleteWords();
	}

	/* --------------------------------------------------------------------- */
	/* ------------------------ Affichage de la page ----------------------- */
	/* --------------------------------------------------------------------- */
	echo '<div class="wrap advanced-search-admin">';
	echo '<div class="icon32 icon"><br /></div>';
	echo '<h2>'; _e('Gestion de l\'autocomplétion','wp-advanced-search'); echo '</h2><br/>';
	echo '<div class="text">';
	_e('<strong>WP-Advanced-Search</strong> permet d\'activer un système d\'autocomplétion paramétrable.', 'wp-advanced-search'); echo '<br/>';
	_e('Modifiez les options pour obtenir un résultat adéquat.', 'wp-advanced-search');	echo '<br/>';
	echo '</div>';

	// Sélection des données dans la base de données		
	$select = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix.$tableName." WHERE id=1");
?> 
        <form method="post" action="">
       	<div class="block">
            <div class="col">
                <h4><?php _e('Options pour l\'autocomplétion','wp-advanced-search'); ?></h4>
                <p class="tr">
                    <select name="wp_advanced_search_autocompletion_active" id="wp_advanced_search_autocompletion_active">
                        <option value="1" <?php if($select->autoCompleteActive == true) { echo 'selected="selected"'; } ?>><?php _e('Oui','wp-advanced-search'); ?></option>
                        <option value="0" <?php if($select->autoCompleteActive == false) { echo 'selected="selected"'; } ?>><?php _e('Non','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_autocompletion_active"><strong><?php _e('Activer l\'autocomplétion ?','wp-advanced-search'); ?></strong></label>
                </p>
                <p class="tr">
                    <input value="<?php echo $select->autoCompleteSelector; ?>" name="wp_advanced_search_autocompletion_selector" id="wp_advanced_search_autocompletion_selector" type="text" />
                    <label for="wp_advanced_search_autocompletion_selector"><strong><?php _e('Sélecteur jQuery du champ de recherche','wp-advanced-search'); ?></strong></label>
                    <br/><em><?php _e('Champ obligatoire ("#id" ou ".class" du champ de recherche)','wp-advanced-search'); ?></em>
                </p>
                <p class="tr">
                    <select name="wp_advanced_search_autocompletion_autofocus" id="wp_advanced_search_autocompletion_autofocus">
                        <option value="1" <?php if($select->autoCompleteAutofocus == true) { echo 'selected="selected"'; } ?>><?php _e('Oui','wp-advanced-search'); ?></option>
                        <option value="0" <?php if($select->autoCompleteAutofocus == false) { echo 'selected="selected"'; } ?>><?php _e('Non','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_autocompletion_autofocus"><strong><?php _e('Sélectionner automatiquement une suggestion ?','wp-advanced-search'); ?></strong></label>
                    <br/><em><?php _e('Se place automatiquement sur la première suggestion ou non','wp-advanced-search'); ?></em>
                </p>
                <p class="tr">
                    <select name="wp_advanced_search_autocompletion_type" id="wp_advanced_search_autocompletion_type">
                        <option value="0" <?php if($select->autoCompleteType == 0) { echo 'selected="selected"'; } ?>><?php _e('Début de mot','wp-advanced-search'); ?></option>
                        <option value="1" <?php if($select->autoCompleteType == 1) { echo 'selected="selected"'; } ?>><?php _e('Contenu dans le mot','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_autocompletion_type"><strong><?php _e('Type d\'autocomplétion','wp-advanced-search'); ?></strong></label>
                    <br/><em><?php _e('Suggère des mots contenant les lettres tapées ou commençant par ces lettres','wp-advanced-search'); ?></em>
                </p>
                <p class="tr">
                	<select name="wp_advanced_search_autocompletion_number" id="wp_advanced_search_autocompletion_number">
                    	<?php for($i = 1; $i < 11; $i++) { ?>
                        <option value="<?php echo $i; ?>" <?php if($select->autoCompleteNumber == $i) { echo 'selected="selected"'; } ?>><?php echo $i; ?></option>
                        <?php } ?>
                    </select>
                    <label for="wp_advanced_search_autocompletion_number"><strong><?php _e('Nombre de suggestions affichées','wp-advanced-search'); ?></strong></label>
                </p>
                <p class="tr">
                	<select name="wp_advanced_search_autocompletion_typesuggest" id="wp_advanced_search_autocompletion_typesuggest">
                        <option value="0" <?php if($select->autoCompleteTypeSuggest == false) { echo 'selected="selected"'; } ?>><?php _e('Pour le premier mot','wp-advanced-search'); ?></option>
                        <option value="1" <?php if($select->autoCompleteTypeSuggest == true) { echo 'selected="selected"'; } ?>><?php _e('Pour chaque mot','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_autocompletion_typesuggest"><strong><?php _e('Type d\'utilisation des auto-suggestions','wp-advanced-search'); ?></strong></label>
                </p>

                <h4><?php _e('Personnalisation technique (optionnel)','wp-advanced-search'); ?></h4>
                <?php
                    // Vérifie que l'index inversé existe ou non et affiche des options de création ou de suppression en fonction...
                    $sqlShow = $wpdb->get_results("SELECT COUNT(*) FROM ".$select->autoCompleteTable);
                    $rowCount = $wpdb->num_rows;
                    if($rowCount == 0) {
                ?>
                <p class="tr">
                    <select name="wp_advanced_search_autocompletion_create" id="wp_advanced_search_autocompletion_create">
                        <option value="1"><?php _e('Oui','wp-advanced-search'); ?></option>
                        <option value="0" selected="selected"><?php _e('Non','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_autocompletion_create"><strong><?php _e('Créer la table de l\'index inversé ?','wp-advanced-search'); ?></strong></label>
                    <br/><em><?php _e('Cette option s\'active uniquement quand l\'index inversé n\'est pas créé (recommandé)','wp-advanced-search'); ?></em>
                </p>
                <p class="tr">
                    <input value="<?php echo $select->autoCompleteTable; ?>" name="wp_advanced_search_autocompletion_table" id="wp_advanced_search_autocompletion_table" type="text" />
                    <label for="wp_advanced_search_autocompletion_table"><strong><?php _e('Nom de la table de l\'index inversé','wp-advanced-search'); ?></strong></label>
                </p>
                <p class="tr">
                    <input value="<?php echo $select->autoCompleteColumn; ?>" name="wp_advanced_search_autocompletion_column" id="wp_advanced_search_autocompletion_column" type="text" />
                    <label for="wp_advanced_search_autocompletion_column"><strong><?php _e('Nom de la colonne de l\'index inversé','wp-advanced-search'); ?></strong></label>
                </p>
                    <input type="hidden" name="wp_advanced_search_autocompletion_generate" value="<?php echo $select->autoCompleteGenerate; ?>" />
                    <input type="hidden" name="wp_advanced_search_autocompletion_sizemin" value="<?php echo $select->autoCompleteSizeMin; ?>" />
                <?php
                    } else {
                ?>
                <p class="tr">
                    <select name="wp_advanced_search_autocompletion_delete" id="wp_advanced_search_autocompletion_delete">
                        <option value="1"><?php _e('Oui','wp-advanced-search'); ?></option>
                        <option value="0" selected="selected"><?php _e('Non','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_autocompletion_delete" class="autoCompleteDelete"><strong><?php _e('Supprimer la table de l\'index inversé ?','wp-advanced-search'); ?></strong></label>
                    <br/><em><?php _e('Cette option s\'active uniquement si l\'index inversé existe (déconseillé)','wp-advanced-search'); ?></em>
                    <input type="hidden" name="wp_advanced_search_autocompletion_table" value="<?php echo $select->autoCompleteTable; ?>" />
                    <input type="hidden" name="wp_advanced_search_autocompletion_column" value="<?php echo $select->autoCompleteColumn; ?>" />
                </p>
                <p class="tr">
                    <select name="wp_advanced_search_autocompletion_generate" id="wp_advanced_search_autocompletion_generate">
                        <option value="1" <?php if($select->autoCompleteGenerate == true) { echo 'selected="selected"'; } ?>><?php _e('Oui','wp-advanced-search'); ?></option>
                        <option value="0" <?php if($select->autoCompleteGenerate == false) { echo 'selected="selected"'; } ?>><?php _e('Non','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_autocompletion_generate"><strong><?php _e('Générer automatiquement l\'index inversé ?','wp-advanced-search'); ?></strong></label>
                    <br/><em><?php _e('Ajoute automatiquement de nouvelles suggestions au fur et à mesure des recherches des internautes pour compléter l\'index de mots clés existant','wp-advanced-search'); ?></em>
                </p>
                <p class="tr">
                    <select name="wp_advanced_search_autocompletion_sizemin" id="wp_advanced_search_autocompletion_sizemin">
                        <?php for($i = 2; $i < 10; $i++) { ?>
                        <option value="<?php echo $i; ?>" <?php if($i == $select->autoCompleteSizeMin) { echo 'selected="selected"'; } ?>><?php echo $i.__(' lettres','wp-advanced-search'); ?></option>
                        <?php } ?>
                    </select>
                    <label for="wp_advanced_search_autocompletion_sizemin"><strong><?php _e('Taille minimale des mots ajoutés','wp-advanced-search'); ?></strong></label>
                </p>

                <p class="clear"></p>
                <p><input type="submit" name="wp_advanced_search_action" class="button-primary" value="<?php _e('Enregistrer' , 'wp-advanced-search'); ?>" /></p>
            </div>
            <div class="col">
                <h4><?php _e('Ajout de mots et expressions','wp-advanced-search'); ?></h4>
                <p class="tr2">
                	<label for="wp_advanced_search_autocompletion_addwords"><strong><?php _e('Ajouter des mots et expressions dans l\'index (optionnel)','wp-advanced-search'); ?></strong></label>
                    <textarea name="wp_advanced_search_autocompletion_addwords" id="wp_advanced_search_autocompletion_addwords"></textarea>
                    <br/><em><?php _e('Séparez les mots ou expressions par des virgules !<br/>Exemple --> moteur, moteur de recherche, recherche, advanced, search<br/>N.B. : les doublons ne s\'ajoutent pas...','wp-advanced-search'); ?></em>
                    <br/><input type="submit" name="wp_advanced_search_action_addwords" class="button-primary" value="<?php _e('Ajouter à l\'index', 'wp-advanced-search'); ?>" />
                </p>
                <h4><?php _e('Ajout des tags existants','wp-advanced-search'); ?></h4>
                <p class="tr2">
                    <label for="wp_advanced_search_autocompletion_addtags"><strong><?php _e('Ajouter des tags existants dans l\'index inversé','wp-advanced-search'); ?></strong></label>
                    <select name="wp_advanced_search_autocompletion_addtags[]" multiple="multiple" id="deleteSelect" size="12">
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
				<h4><?php _e('Gestion de l\'index existant','wp-advanced-search'); ?></h4>
                <p class="tr2">
                	<label for="wp_advanced_search_autocompletion_deletewords"><strong><?php _e('Supprimer des mots et expressions dans l\'index (si nécessaire)','wp-advanced-search'); ?></strong></label>
                    <select name="wp_advanced_search_autocompletion_deletewords[]" multiple="multiple" id="deleteSelect" size="12">
					<?php
						$expressions = $wpdb->get_results("SELECT ".$select->autoCompleteColumn." FROM ".$select->autoCompleteTable." ORDER BY ".$select->autoCompleteColumn." ASC", ARRAY_N);
						foreach($expressions as $word) {
					?>
						<option value="<?php echo $word[0]; ?>"><?php echo $word[0]; ?></option>
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
function WP_Advanced_Search_update_autocompletion() {
	global $wpdb, $tableName; // insérer les variables globales

	// Pagination
	$wp_advanced_search_autocompletion_active		= $_POST['wp_advanced_search_autocompletion_active'];
	$wp_advanced_search_autocompletion_selector		= $_POST['wp_advanced_search_autocompletion_selector'];
	$wp_advanced_search_autocompletion_autofocus	= $_POST['wp_advanced_search_autocompletion_autofocus'];
	$wp_advanced_search_autocompletion_type			= $_POST['wp_advanced_search_autocompletion_type'];
	$wp_advanced_search_autocompletion_number		= $_POST['wp_advanced_search_autocompletion_number'];
	$wp_advanced_search_autocompletion_typesuggest	= $_POST['wp_advanced_search_autocompletion_typesuggest'];
	$wp_advanced_search_autocompletion_create		= $_POST['wp_advanced_search_autocompletion_create'];
	$wp_advanced_search_autocompletion_table		= $_POST['wp_advanced_search_autocompletion_table'];
	$wp_advanced_search_autocompletion_column		= $_POST['wp_advanced_search_autocompletion_column'];
	$wp_advanced_search_autocompletion_generate		= $_POST['wp_advanced_search_autocompletion_generate'];
	$wp_advanced_search_autocompletion_sizemin		= $_POST['wp_advanced_search_autocompletion_sizemin'];
		
	$wp_advanced_search_update = $wpdb->update(
		$wpdb->prefix.$tableName,
		array(
			"autoCompleteActive" => $wp_advanced_search_autocompletion_active,
			"autoCompleteSelector" => $wp_advanced_search_autocompletion_selector,
			"autoCompleteAutofocus" => $wp_advanced_search_autocompletion_autofocus,
			"autoCompleteType" => $wp_advanced_search_autocompletion_type,
			"autoCompleteNumber" => $wp_advanced_search_autocompletion_number,
			"autoCompleteTypeSuggest" => $wp_advanced_search_autocompletion_typesuggest,
			"autoCompleteCreate" => $wp_advanced_search_autocompletion_create,
			"autoCompleteTable" => $wp_advanced_search_autocompletion_table,
			"autoCompleteColumn" => $wp_advanced_search_autocompletion_column,
			"autoCompleteGenerate" => $wp_advanced_search_autocompletion_generate,
			"autoCompleteSizeMin" => $wp_advanced_search_autocompletion_sizemin
		), 
		array('id' => 1)
	);
}

// Function d'ajout des mots clés dans l'index (si rempli !)
function WP_Advanced_Search_Autocompletion_AddWords($datas) {
	global $wpdb, $tableName; // insérer les variables globales

	// Sélection des données dans les tables de la base de données		
	$select = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix.$tableName." WHERE id=1");
	$selectWords = $wpdb->get_results("SELECT ".$select->autoCompleteColumn." FROM ".$select->autoCompleteTable."");
	$words = $select->autoCompleteColumn;
	
	// Récupération des mots et expressions dans un tableau de données
    if(is_string($datas)) { // Si c'est une chaîne séparé par des virgules
        $expressions = array_map('trim', explode(',',htmlspecialchars($datas)));
    } elseif(is_array($datas)) { // Si c'est un tableau de mots (tags, etc.)
        $expressions = $datas;
    }
	
	// Récupération des mots dans l'index inversé
	$selected = array();
	foreach($selectWords as $w) {
		$selected[] = $w->$words;
	}

	foreach($expressions as $exp) {
		if(strlen($exp) > $select->autoCompleteSizeMin) {						
			if(!in_array($exp, $selected)) {
				$wpdb->query("INSERT INTO ".$select->autoCompleteTable."(".$select->autoCompleteColumn.") VALUES ('".$exp."')");
			}
		}
	}
}

// Suppression des extraits sélectionnés
function WP_Advanced_Search_Autocompletion_DeleteWords() {
	global $wpdb, $tableName; // insérer les variables globales

	$tableDelete = $wpdb->get_row("SELECT autoCompleteTable, autoCompleteColumn FROM ".$wpdb->prefix.$tableName, ARRAY_N);
	$tabWords = $_POST['wp_advanced_search_autocompletion_deletewords'];
	
	foreach($tabWords as $word) {
		$wpdb->delete($tableDelete[0], array($tableDelete[1] => stripslashes($word)));
	}
}
?>