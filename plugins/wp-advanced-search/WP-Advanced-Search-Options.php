<?php
function WP_Advanced_Search_FullText() {
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

// Mise à jour des données par défaut
function WP_Advanced_Search_update() {
	global $wpdb, $tableName; // insérer les variables globales

	// Réglages de base
	$wp_advanced_search_table			= esc_html($_POST['wp_advanced_search_table']);
	$wp_advanced_search_name			= esc_html($_POST['wp_advanced_search_name']);
	$wp_advanced_search_resulttext		= esc_html($_POST['wp_advanced_search_resulttext']);
	$wp_advanced_search_errortext		= esc_html($_POST['wp_advanced_search_errortext']);
	$wp_advanced_search_colonneswhere	= $_POST['wp_advanced_search_colonneswhere'];
	$wp_advanced_search_typesearch		= $_POST['wp_advanced_search_typesearch'];
	$wp_advanced_search_encoding		= $_POST['wp_advanced_search_encoding'];
	$wp_advanced_search_exactsearch		= $_POST['wp_advanced_search_exactsearch'];
	$wp_advanced_search_accents			= $_POST['wp_advanced_search_accents'];
	$wp_advanced_search_exclusionwords	= $_POST['wp_advanced_search_exclusionwords'];
	$wp_advanced_search_stopwords		= $_POST['wp_advanced_search_stopwords'];
	$wp_advanced_search_posttype		= $_POST['wp_advanced_search_posttype'];
	$wp_advanced_search_idform			= esc_html($_POST['wp_advanced_search_idform']);
	$wp_advanced_search_placeholder		= esc_html($_POST['wp_advanced_search_placeholder']);
	
	// Catégories
	$wp_advanced_search_categories 		= array();
	foreach($_POST['wp_advanced_search_categories'] as $ctgSave) {
		array_push($wp_advanced_search_categories, $ctgSave);
	}
	if(is_numeric($_POST['wp_advanced_search_numberPerPage']) || !empty($_POST['wp_advanced_search_numberPerPage'])) {
		$wp_advanced_search_numberPerPage = esc_html($_POST['wp_advanced_search_numberPerPage']);
	} else {
		$wp_advanced_search_numberPerPage = 0;
	}
	
	// Mise en gras et ordre des résultats
	$wp_advanced_search_strong		= $_POST['wp_advanced_search_strong'];
	$wp_advanced_search_orderOK		= $_POST['wp_advanced_search_orderOK'];
	$wp_advanced_search_orderColumn	= $_POST['wp_advanced_search_orderColumn'];
	$wp_advanced_search_ascdesc		= $_POST['wp_advanced_search_ascdesc'];
	$wp_advanced_search_algoOK		= $_POST['wp_advanced_search_algoOK'];
		
	$wp_advanced_search_update = $wpdb->update(
		$wpdb->prefix.$tableName,
		array(
			"tables" => $wp_advanced_search_table,
			"nameField" => $wp_advanced_search_name,
			"colonnesWhere" => $wp_advanced_search_colonneswhere,
			"typeSearch" => $wp_advanced_search_typesearch,
			"encoding" => $wp_advanced_search_encoding,
			"exactSearch" => $wp_advanced_search_exactsearch,
			"accents" => $wp_advanced_search_accents,
			"exclusionWords" => $wp_advanced_search_exclusionwords,
			"stopWords" => $wp_advanced_search_stopwords,
			"NumberPerPage" => $wp_advanced_search_numberPerPage,
			"idform" => $wp_advanced_search_idform,
			"placeholder" => $wp_advanced_search_placeholder,
			"strongWords" => $wp_advanced_search_strong,
			"OrderOK" => $wp_advanced_search_orderOK,
			"OrderColumn" => $wp_advanced_search_orderColumn,
			"AscDesc" => $wp_advanced_search_ascdesc,
			"AlgoOK" => $wp_advanced_search_algoOK,
			"postType" => $wp_advanced_search_posttype,
			"categories" => serialize($wp_advanced_search_categories),
			"ResultText" => $wp_advanced_search_resulttext,
			"ErrorText" => $wp_advanced_search_errortext
		), 
		array('id' => 1)
	);
}

// Fonction d'affichage de la page d'aide et de réglages de l'extension
function WP_Advanced_Search_Callback() {
	global $wpdb, $tableName; // insérer les variables globales

	// Déclencher la fonction de mise à jour (upload)
	if(isset($_POST['wp_advanced_search_action']) && $_POST['wp_advanced_search_action'] == __('Enregistrer' , 'wp-advanced-search')) {
		WP_Advanced_Search_update();
	}
	
	// Déclencher la fonction de mise à jour (upload)
	if(isset($_POST['wp_advanced_search_fulltext'])) {
		WP_Advanced_Search_FullText();
	}

	/* --------------------------------------------------------------------- */
	/* ------------------------ Affichage de la page ----------------------- */
	/* --------------------------------------------------------------------- */
	echo '<div class="wrap advanced-search-admin">';
	echo '<div class="icon32 icon"><br /></div>';
	echo '<h2>'; _e('Aide et réglages de WP-Advanced-Search.','wp-advanced-search'); echo '</h2><br/>';
	echo '<div class="text">';
	_e('<strong>WP Advanced Search</strong> permet d\'activer un moteur de recherche puissant pour WordPress.', 'wp-advanced-search'); echo '<br/>';
	_e('Plusieurs types de recherche ("LIKE", "REGEXP" ou "FULLTEXT"), algorithme de pertinence, mise en surbrillance des mots recherchés, pagination, affichage paramétrable... ', 'wp-advanced-search');
	_e('Tout est entièrement modulable pour obtenir des résultats précis !', 'wp-advanced-search');	echo '<br/>';
	_e('<strong>Consultez la documentation pour plus d\'informations si nécessaire...</strong>', 'wp-advanced-search');	echo '<br/>';
	echo '</div>';

	// Sélection des données dans la base de données		
	$select = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix.$tableName." WHERE id=1");
?>
	<script type="text/javascript">
		function montrer(object) {
		   if (document.getElementById) document.getElementById(object).style.display = 'block';
		}
		
		function cacher(object) {
		   if (document.getElementById) document.getElementById(object).style.display = 'none';
		}
    </script>

		<!-- Formulaire pour installer les index FULLTEXT (si activé en cliquant sur le lien) -->
        <form id="WP-Advanced-Search-Form" method="post">
        	<input type="hidden" name="wp_advanced_search_fulltext" value="" />
        </form>
        
        <!-- Formulaire de mise à jour des données -->
        <form method="post" action="">
       	<div class="block">
            <div class="col">
            	<h4><?php _e('Options générales du moteur','wp-advanced-search'); ?></h4>
                <p class="tr">
					<input type="text" name="wp_advanced_search_table" id="wp_advanced_search_table" value="<?php echo $select->tables; ?>" />
					<label for="wp_advanced_search_table"><strong><?php _e('Table de recherche','wp-advanced-search'); ?></strong></label>
					<br/><em><?php _e('Il est recommandé de laisser la table "xx_posts" contenant toutes les publications','wp-advanced-search'); ?></em>
				</p>
                <p class="tr">
					<input value="<?php echo $select->nameField; ?>" name="wp_advanced_search_name" id="wp_advanced_search_name" type="text" />
					<label for="wp_advanced_search_name"><strong><?php _e('Attribut "name" du champ de recherche','wp-advanced-search'); ?></strong></label>
				</p>
                <p class="tr">
                    <input value="<?php echo $select->colonnesWhere; ?>" name="wp_advanced_search_colonneswhere" id="wp_advanced_search_colonnewhere" type="text" />
                    <label for="wp_advanced_search_colonneswhere"><strong><?php _e('Colonnes de la table dans lesquelles rechercher','wp-advanced-search'); ?></strong></label>
                    <br/><em><?php _e('Séparez les valeurs par des virgules','wp-advanced-search'); ?></em>
                </p>
                <p class="tr">
                    <select name="wp_advanced_search_typesearch" id="wp_advanced_search_typesearch">
                        <option value="FULLTEXT" <?php if($select->typeSearch == 'FULLTEXT') { echo 'selected="selected"'; } ?>><?php _e('FULLTEXT','wp-advanced-search'); ?></option>
                        <option value="REGEXP" <?php if($select->typeSearch == 'REGEXP') { echo 'selected="selected"'; } ?>><?php _e('REGEXP','wp-advanced-search'); ?></option>
                        <option value="LIKE" <?php if($select->typeSearch == 'LIKE') { echo 'selected="selected"'; } ?>><?php _e('LIKE','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_typesearch"><strong><?php _e('Type de recherche PHP-MySQL','wp-advanced-search'); ?></strong></label>
                    <br/><em><?php _e('<a href="#" onclick="','wp-advanced-search'); ?>getElementById('WP-Advanced-Search-Form').submit()<?php _e('">Installez les index FULLTEXT</a> pour que la recherche FULLTEXT puisse fonctionner...','wp-advanced-search'); ?></em>
                </p>
                <p class="tr">
                    <select name="wp_advanced_search_posttype" id="wp_advanced_search_posttype">
                        <option value="post" <?php if($select->postType == 'post') { echo 'selected="selected"'; } ?> onclick="montrer('ctgBlock')";><?php _e('Articles','wp-advanced-search'); ?></option>
                        <option value="page" <?php if($select->postType == 'page') { echo 'selected="selected"'; } ?> onclick="cacher('ctgBlock')";><?php _e('Pages','wp-advanced-search'); ?></option>
                        <option value="pagepost" <?php if($select->postType == 'pagepost') { echo 'selected="selected"'; } ?> onclick="cacher('ctgBlock')"><?php _e('Articles + Pages','wp-advanced-search'); ?></option>
						<option value="all" <?php if($select->postType == 'all') { echo 'selected="selected"'; } ?> onclick="cacher('ctgBlock')"><?php _e('Tous les contenus publiés','wp-advanced-search'); ?></option>
                        <option value="others" <?php if($select->postType == 'others') { echo 'selected="selected"'; } ?> onclick="cacher('ctgBlock')"><?php _e('Autres','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_posttype"><strong><?php _e('Type de contenus pour la recherche ?','wp-advanced-search'); ?></strong></label>
                    <br/><em><?php _e('"Autres" si vous n\'utilisez pas la table de recherche "xx_posts"','wp-advanced-search'); ?></em>
                </p>
                <p class="tr" id="ctgBlock" <?php if($select->postType == 'post') { echo 'style="display:block;"'; } else { echo 'style="display:none;"'; } ?>>
					<?php
                        $tabSlugCategories = $wpdb->get_results("SELECT TE.slug FROM $wpdb->terms as TE INNER JOIN $wpdb->term_taxonomy as TT WHERE TT.taxonomy = 'category' AND TE.term_id = TT.term_id"); // Ajouter AND TT.count !=0 pour ne garder que les catégories contenant des articles !
						$tabNameCategories = $wpdb->get_results("SELECT TE.name FROM $wpdb->terms as TE INNER JOIN $wpdb->term_taxonomy as TT WHERE TT.taxonomy = 'category' AND TE.term_id = TT.term_id"); // Ajouter AND TT.count !=0 pour ne garder que les catégories contenant des articles !
						//$tabCategories = array_combine($tabSlugCategories, $tabNameCategories);
						foreach($tabSlugCategories as $slugTab) {
							foreach($slugTab as $slug) {
								$tabSlug[] = $slug;	
							}
						}
						foreach($tabNameCategories as $nameTab) {
							foreach($nameTab as $name) {
								$tabName[] = $name;	
							}
						}
						$tabCategories = array_combine($tabSlug, $tabName);
						$select->categories = unserialize($select->categories);
                    ?>
                    <select name="wp_advanced_search_categories[]" id="wp_advanced_search_categories" multiple="multiple" size="5">
                        <option value="toutes" <?php if(in_array('toutes', $select->categories)) { echo 'selected="selected"'; } ?>><?php _e('Toutes les catégories','wp-advanced-search'); ?></option>
                        <?php
						foreach($tabCategories as $tabKey => $tabCtg) {
						?>
								<option value="<?php echo $tabKey; ?>" <?php if(in_array($tabKey, $select->categories)) { echo 'selected="selected"'; } ?> name="categories"><?php _e($tabCtg,'wp-advanced-search'); ?></option>
                        <?php
						}
                        ?>
                    </select>
                    <label for="wp_advanced_search_categories"><strong><?php _e('Catégories de recherche (articles uniquement)','wp-advanced-search'); ?></strong></label>
                </p>
                <p class="tr">
                    <input value="<?php echo $select->NumberPerPage; ?>" name="wp_advanced_search_numberPerPage" id="wp_advanced_search_numberPerPage" type="text" />
                    <label for="wp_advanced_search_numberPerPage"><strong><?php _e('Nombre de résultats par page','wp-advanced-search'); ?></strong></label>
                    <br/><em><?php _e('0 ou vide pour tout afficher dans une page (sans pagination)','wp-advanced-search'); ?></em>
                </p>
				<p class="tr">
                    <input value="<?php echo $select->idform; ?>" name="wp_advanced_search_idform" id="wp_advanced_search_idform" type="text" />
                    <label for="wp_advanced_search_idform"><strong><?php _e('ID du champ de recherche','wp-advanced-search'); ?></strong></label>
                    <br/><em><?php _e('Indiquez l\'ID du champ de recherche (HTML) pour aider les options en Javascript (souvent identique à l\'attribut name)','wp-advanced-search'); ?></em>
                </p>
				<p class="tr">
                    <input value="<?php echo $select->placeholder; ?>" name="wp_advanced_search_placeholder" id="wp_advanced_search_placeholder" type="text" />
                    <label for="wp_advanced_search_placeholder"><strong><?php _e('Placeholder du champ de recherche','wp-advanced-search'); ?></strong></label>
                    <br/><em><?php _e('Blocage de la soumission du formulaire de recherche si un "placeholder" est précisé (texte par défaut écrit dans un champ de recherche)','wp-advanced-search'); ?></em>
                </p>

                <h4><br/><?php _e('Mise en surbrillance et rendu','wp-advanced-search'); ?></h4>
                <p class="tr">
                    <select name="wp_advanced_search_strong" id="wp_advanced_search_strong">
                        <option value="exact" <?php if($select->strongWords == "exact") { echo 'selected="selected"'; } ?>><?php _e('Précise','wp-advanced-search'); ?></option>
                        <option value="total" <?php if($select->strongWords == "total") { echo 'selected="selected"'; } ?>><?php _e('Approchante','wp-advanced-search'); ?></option>
                        <option value="aucun" <?php if($select->strongWords == "aucun") { echo 'selected="selected"'; } ?>><?php _e('Aucune mise en gras','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_strong"><strong><?php _e('Mise en surbrillance des mots clés','wp-advanced-search'); ?></strong></label>
                    <br/><em><?php _e('"Précise" pour rechercher la chaîne exacte, "Approchante" pour chercher les mots contenant une chaîne (si recherche LIKE)','wp-advanced-search'); ?></em>
                </p>
                <p class="tr">
                <input value="<?php echo $select->ResultText; ?>" name="wp_advanced_search_resulttext" id="wp_advanced_search_resulttext" type="text" />
                <label for="wp_advanced_search_resulttext"><strong><?php _e('Texte pour la requête recherchée','wp-advanced-search'); ?></strong></label>
                <br/><em><?php _e('Laissez vide pour masquer le texte','wp-advanced-search'); ?></em>
                </p>
                <p class="tr">
                <input value="<?php echo $select->ErrorText; ?>" name="wp_advanced_search_errortext" id="wp_advanced_search_errortext" type="text" />
                <label for="wp_advanced_search_errortext"><strong><?php _e('Texte affiché si aucun résultat','wp-advanced-search'); ?></strong></label>
                </p>
        	</div>
            <div class="col">
				<h4><?php _e('Ordre des résultats','wp-advanced-search'); ?></h4>
                <p class="tr">
                    <select name="wp_advanced_search_orderOK" id="wp_advanced_search_orderOK">
                        <option value="1" <?php if($select->OrderOK == true) { echo 'selected="selected"'; } ?>><?php _e('Oui','wp-advanced-search'); ?></option>
                        <option value="0" <?php if($select->OrderOK == false) { echo 'selected="selected"'; } ?>><?php _e('Non','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_orderOK"><strong><?php _e('Ordonner les résultats ?','wp-advanced-search'); ?></strong></label>
                </p>
                <p class="tr">
                    <select name="wp_advanced_search_algoOK" id="wp_advanced_search_algoOK">
                        <option value="1" <?php if($select->AlgoOK == true) { echo 'selected="selected"'; } ?>><?php _e('Oui','wp-advanced-search'); ?></option>
                        <option value="0" <?php if($select->AlgoOK == false) { echo 'selected="selected"'; } ?>><?php _e('Non','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_algoOK"><strong><?php _e('Algorithme de pertinence ?','wp-advanced-search'); ?></strong></label>
                    <br/><em><?php _e('L\'algorithme de pertinence affiche en ordre décroissant les résultats qui ont le plus de correspondances avec la requête','wp-advanced-search'); ?></em>
                </p>
                <p class="tr">
                    <select name="wp_advanced_search_orderColumn" id="wp_advanced_search_orderColumn">
                    	<?php
							$columns = $wpdb->get_results("SELECT column_name FROM information_schema.COLUMNS WHERE table_name = '".$select->tables."'");							
							$numberColumn = count($columns,1);
							for($i=0; $i < $numberColumn; $i++) {
								foreach($columns[$i] as $column => $value) {
						?>
							<option value="<?php echo $value; ?>" <?php if($select->OrderColumn == $value) { echo 'selected="selected"'; } ?>><?php _e($value,'wp-advanced-search'); ?></option>
                        <?php
								}
							}
						?>
                    </select>
                    <label for="wp_advanced_search_orderColumn"><strong><?php _e('Colonne de classement des résultats','wp-advanced-search'); ?></strong></label>
                </p>
                <p class="tr">
                    <select name="wp_advanced_search_ascdesc" id="wp_advanced_search_ascdesc">
                        <option value="ASC" <?php if($select->AscDesc == "ASC") { echo 'selected="selected"'; } ?>><?php _e('Croissant (ASC)','wp-advanced-search'); ?></option>
                        <option value="DESC" <?php if($select->AscDesc == "DESC") { echo 'selected="selected"'; } ?>><?php _e('Décroissant (DESC)','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_ascdesc"><strong><?php _e('Croissant ou décroissant ?','wp-advanced-search'); ?></strong></label>
                </p>

                <h4><br/><?php _e('Options de formatage des requêtes','wp-advanced-search'); ?></h4>
                <p class="tr">
                    <select name="wp_advanced_search_stopwords" id="wp_advanced_search_stopwords">
                        <option value="1" <?php if($select->stopWords == true) { echo 'selected="selected"'; } ?>><?php _e('Oui','wp-advanced-search'); ?></option>
                        <option value="0" <?php if($select->stopWords == false) { echo 'selected="selected"'; } ?>><?php _e('Non','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_stopwords"><strong><?php _e('Activer les "stop words" ?','wp-advanced-search'); ?></strong></label>
					<br/><em><?php _e('L\'activation permet d\'ignorer les mots courts classiques (articles, conjonctions de coordination...)','wp-advanced-search'); ?></em>
                </p>
                <p class="tr">
                    <select name="wp_advanced_search_exclusionwords" id="wp_advanced_search_exclusionwords">
                        <option value="" <?php if(empty($select->accents)) { echo 'selected="selected"'; } ?>><?php _e('Désactivé','wp-advanced-search'); ?></option>
                        <option value="1" <?php if($select->exclusionWords == 1) { echo 'selected="selected"'; } ?>><?php _e('< 1 caractère','wp-advanced-search'); ?></option>
                        <option value="2" <?php if($select->exclusionWords == 2) { echo 'selected="selected"'; } ?>><?php _e('< 2 caractères','wp-advanced-search'); ?></option>
                        <option value="3" <?php if($select->exclusionWords == 3) { echo 'selected="selected"'; } ?>><?php _e('< 3 caractères','wp-advanced-search'); ?></option>
                        <option value="4" <?php if($select->exclusionWords == 4) { echo 'selected="selected"'; } ?>><?php _e('< 4 caractères','wp-advanced-search'); ?></option>
                        <option value="5" <?php if($select->exclusionWords == 5) { echo 'selected="selected"'; } ?>><?php _e('< 5 caractères','wp-advanced-search'); ?></option>
                        <option value="6" <?php if($select->exclusionWords == 6) { echo 'selected="selected"'; } ?>><?php _e('< 6 caractères','wp-advanced-search'); ?></option>
                        <option value="7" <?php if($select->exclusionWords == 7) { echo 'selected="selected"'; } ?>><?php _e('< 7 caractères','wp-advanced-search'); ?></option>
                        <option value="8" <?php if($select->exclusionWords == 8) { echo 'selected="selected"'; } ?>><?php _e('< 8 caractères','wp-advanced-search'); ?></option>
                        <option value="9" <?php if($select->exclusionWords == 9) { echo 'selected="selected"'; } ?>><?php _e('< 9 caractères','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_exclusionwords"><strong><?php _e('Exclure les mots courts ?','wp-advanced-search'); ?></strong></label>
                </p>
                <p class="tr">
                    <select name="wp_advanced_search_exactsearch" id="wp_advanced_search_exactsearch">
                        <option value="1" <?php if($select->exactSearch == true) { echo 'selected="selected"'; } ?>><?php _e('Exacte','wp-advanced-search'); ?></option>
                        <option value="0" <?php if($select->exactSearch == false) { echo 'selected="selected"'; } ?>><?php _e('Approchante','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_exactsearch"><strong><?php _e('Recherche exacte ou approchante ?','wp-advanced-search'); ?></strong></label>
					<br/><em><?php _e('"Exacte" pour chercher le mot précis ou "Approchante" pour trouver les mots contenant la chaîne de caractères correspondante','wp-advanced-search'); ?></em>
                </p>
                <p class="tr">
                    <select name="wp_advanced_search_encoding" id="wp_advanced_search_encoding">
                        <option value="utf-8" <?php if($select->encoding == "utf-8") { echo 'selected="selected"'; } ?>><?php _e('UTF-8','wp-advanced-search'); ?></option>
                        <option value="iso-8859-1" <?php if($select->encoding == "iso-8859-1") { echo 'selected="selected"'; } ?>><?php _e('ISO-8859-1 (Latin-1)','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_encoding"><strong><?php _e('Choix de l\'encodage des caractères','wp-advanced-search'); ?></strong></label>
                </p>
                <p class="tr">
                    <select name="wp_advanced_search_accents" id="wp_advanced_search_accents">
                        <option value="1" <?php if($select->accents == true) { echo 'selected="selected"'; } ?>><?php _e('Oui','wp-advanced-search'); ?></option>
                        <option value="0" <?php if($select->accents == false) { echo 'selected="selected"'; } ?>><?php _e('Non','wp-advanced-search'); ?></option>
                    </select>
                    <label for="wp_advanced_search_accents"><strong><?php _e('Suppression des accents de la requête ?','wp-advanced-search'); ?></strong></label>
                    <br/><em><?php _e('Utile si les contenus sont sans accent dans la base de données','wp-advanced-search'); ?></em>
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
?>