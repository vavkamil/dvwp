<?php
// Fonction pour l'exportation (après clic sur le bouton)
function WP_Advanced_Search_Export() {
	global $wpdb, $tableName;

	// Nom des tables à sauvegarder
	$table1 = $wpdb->prefix.$tableName;
	$table2 = $wpdb->prefix."autosuggest";
	$table3 = $wpdb->prefix."autocorrectindex";

	// Création du dump SQL
	$sqlDB = mysql_dump($wpdb, DB_NAME, array($table1, $table2, $table3));
	$backupFile = "WP_Advanced_Search-".DB_NAME.'-'.date("d_m_Y_H\hi").".sql";

	// Force le téléchargement
	header('Content-Disposition: attachment; filename='.$backupFile);
	header('Content-Type: application/force-download');
	header('Content-type: application/octet-stream');
	echo $sqlDB;
}
add_action('admin_post_nopriv_db_export', 'WP_Advanced_Search_Export');
add_action('admin_post_db_export', 'WP_Advanced_Search_Export');

// Fonction pour l'importation (après validation dans les options)
function WP_Advanced_Search_Import() {
	global $wpdb, $tableName;

	// Nom des tables à supprimer (si ce n'est pas le cas dans le Dump)
	$table1 = $wpdb->prefix.$tableName;
	$table2 = $wpdb->prefix."autosuggest";
	$table3 = $wpdb->prefix."autocorrectindex";
	$tables = array($table1, $table2, $table3); // Tableau des tables

	// Fichier pour l'upload WordPress à utiliser
	if(!function_exists('wp_handle_upload')) {
	    require_once(ABSPATH.'wp-admin/includes/file.php');
	}

	// Début de l'importation du fichier
	if(isset($_FILES['wp_advanced_search_file_import'])) {
		

		$uploadedfile = $_FILES['wp_advanced_search_file_import']; // Fichier uploadé
		$upload_overrides = array(
			'test_form' => false, // Pour WordPress
			'test_type' => false, // Ne pas bloquer à cause du type MIME (vérifié après)
		);
		$movefile = wp_handle_upload($uploadedfile, $upload_overrides); // Déplacement du fichier chargé

		// Vérification du déplacement du fichier (si OK)
		if($movefile && !isset($movefile['error'])) {
			$extension = strrchr($uploadedfile['name'], '.'); // Récupération de l'extension
			$mimesSQL = array("text/x-sql", "text/sql", "application/sql", "text/plain", "application/octet-stream"); // Types MIME autorisés
			$mime = mime_content_type($movefile['file']); // Récupération du type MIME véritable

			// Si le type MIME et l'extension correspondent, on continue...
			if(in_array($mime, $mimesSQL) && $extension == ".sql") {
				// Récupération de l'URL du fichier (pour la suppression future)
				$urlFile = $movefile['file'];

				// Importation SQL
				$sql = file($urlFile);
				$cleanSQL = array(); // Tableau des requêtes SQL nettoyées
				$dropTables = array();
				$nb = 0; 
				// Nettoyage des requêtes
				foreach($sql as $ligne) {
					// Supprime les espaces inutiles
					$ligne = trim($ligne);

					// Supprime les commentaires et lignes vides inutiles
					if(substr($ligne, 0, 2) == '--' || substr($ligne, 0, 2) == '/*' || $ligne == '') {
						continue;
	        		}

					// Vérifie que les DROP TABLE sont bien présents (sinon erreur lors de l'importation)
					if(substr($ligne, 0, 10) == 'DROP TABLE') {
						$dropTables[] = $ligne;
	        		}

	        		// Vérifie si la ligne se termine bien comme une instruction SQL ';'
					if(substr($ligne, -1, 1) != ';') {
						$cleanSQL[$nb].= $ligne;
						continue;
	        		} else {
	        			$cleanSQL[$nb].= $ligne;
	        		}
	        		$nb++;
				}

				// Vérifie qu'il y a les DROP TABLE pour les 3 tables de la base
				if(empty($dropTables) || count($dropTables) != 3) {
					foreach($tables as $table) {
						$wpdb->query('DROP TABLE IF EXISTS `'.$table.'`;');
					}
				}

				// Envoi des requêtes une par une dans la BDD
				foreach($cleanSQL as $query) {
					$wpdb->query($query);
				}

		    	// Message de validation
		    	$msg = "validImport";
		    } else {
		    	$msg = "errorMime";
		    }

		    // Supprime le fichier après l'importation (sécurité)
		    unlink($urlFile);
		} else {
			echo $movefile['error'];
			if(!empty($uploadedfile['name'])) {
				$msg = "errorImport";
			} else {
				$msg = "noImport";
			}
	    }
	}

	// Redirection avec message en notice
	$url = add_query_arg('message', $msg, urldecode(wp_get_referer()));
    wp_safe_redirect($url);
}
add_action('admin_post_nopriv_db_import', 'WP_Advanced_Search_Import');
add_action('admin_post_db_import', 'WP_Advanced_Search_Import');

// Gère l'affichage des notices adéquates
function WP_Advanced_Search_admin_notice() {
	if(isset($_GET['message'])) {
		if($_GET['message'] == "validImport") {
			$noticeClass = 'updated notice-success';
			$message = __("Importation des tables réussie !", 'wp-advanced-search');
		}
		if($_GET['message'] == "errorImport") {
			$noticeClass = 'notice-error';
			$message = __("Echec de l'importation des tables !", 'wp-advanced-search');
		}
		if($_GET['message'] == "errorMime") {
			$noticeClass = 'notice-error';
			$message = __("Le fichier envoyé n'est pas au bon format (.sql) !", 'wp-advanced-search');
		}
		if($_GET['message'] == "noImport") {
			$noticeClass = 'notice-error';
			$message = __("Aucun fichier (.sql) à importer !", 'wp-advanced-search');
		}
    	
    	echo '<div class="notice '.$noticeClass.' is-dissmissible">';
        echo '<p>'.$message.'</p>';
        echo '</div>';
    } else {
    	return;
    }
}
add_action('admin_notices', 'WP_Advanced_Search_admin_notice');

// Autorise le format .sql en upload
function WP_Advanced_Search_Add_SQL_MIME($mimes) {
	$mimes = array_merge($mimes, array('sql' => 'application/octet-stream'));
	return $mimes;
}
add_filter('upload_mimes', 'WP_Advanced_Search_Add_SQL_MIME');

function WP_Advanced_Search_Callback_ExportImport() {
	/* --------------------------------------------------------------------- */
	/* ------------------------ Affichage de la page ----------------------- */
	/* --------------------------------------------------------------------- */
	echo '<div class="wrap advanced-search-admin">';
	echo '<div class="icon32 icon"><br /></div>';
	echo '<h2>'; _e('Importer/Exporter','wp-advanced-search'); echo '</h2><br/>';
	echo '<div class="text">';
	_e("<strong>WP Advanced Search</strong> vous permet d'exporter et d'importer les données et les réglages de votre extension.", 'wp-advanced-search');
	echo '<br/>';
	_e('<em>N.B. : n\'hésitez pas à contacter <a href="http://blog.internet-formation.fr/2013/10/wp-advanced-search-moteur-de-recherche-avance-pour-wordpress/" target="_blank">Mathieu Chartier</a>, le créateur du plugin, pour de plus amples informations.</em>', 'wp-advanced-search'); echo '<br/>';
	echo '</div>';
?>
    <!-- Formulaire pour importer et exporter -->
   	<div class="block">
        <div class="col">
    		<form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        	<h4><?php _e('Exportation des données (SQL)','wp-advanced-search'); ?></h4>
            <p class="tr">
				<input type="submit" name="wp_advanced_search_export" class="button-primary submit" value="<?php _e('Exporter' , 'wp-advanced-search'); ?>"/>
				<label for="wp_advanced_search_table"><strong><?php _e("Créer un fichier d'exportation (.sql)",'wp-advanced-search'); ?></strong></label>
				<br/><em><?php _e("L'exportation récupère automatiquement toutes les données du moteur de recherche (tables) dans un fichier .sql.",'wp-advanced-search'); ?></em>
				<input type="hidden" name="action" value="db_export">
			</p>
			</form>
    	</div>
    	<div class="col">
        	<h4><?php _e('Importation des données (SQL)','wp-advanced-search'); ?></h4>
        	<form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
            <p class="tr">
				<input type="file" name="wp_advanced_search_file_import" class="file" value=""/>
				<input type="submit" name="wp_advanced_search_import" class="button-primary submit" value="<?php _e('Importer' , 'wp-advanced-search'); ?>"/>
				<br/><em><?php _e("Attention ! L'importation supprime les anciennes données du moteur de recherche (écrasement des tables de la base de données).",'wp-advanced-search'); ?></em>
				<input type="hidden" name="action" value="db_import">
			</p>
			</form>
    	</div>
    	<p class="clear"></p>
    </div>
<?php
	echo '</div>'; // Fin de la page d'admin
} // Fin de la fonction Callback

if(!function_exists('mysql_dump')) {
function mysql_dump($connect, $database = DB_NAME, $listeTables = array()) {
	$lnbr = "\n\r";
	$query = "-- ---------------------------------------------------------".$lnbr;
	$query .= "-- Nom de la base : ".$database.$lnbr;
	if(!empty($listeTables)) {
		$dbTables = $listeTables[0];
		for($i = 1; $i < count($listeTables); $i++) {
			$dbTables.= ", ".$listeTables[$i];
		}
	$query .= "-- Tables sauvegardees : ".$dbTables.$lnbr;
	}
	$query .= "-- Date : ".date("d-m-Y H\hi\m\i\\ns").$lnbr;
	$query .= "-- ---------------------------------------------------------".$lnbr;

	$tables = $connect->get_results("SHOW TABLES FROM ".$database);
	foreach($tables as $table) {
		foreach($table as $t) {
			if(!empty($listeTables) && in_array($t, $listeTables)) {
				$table_list[] = $t;
			} elseif(empty($listeTables)) {
				$table_list[] = $t;
			}
		}
	}
	
	for($i = 0; $i < count($table_list); $i++) {
		$results = $connect->get_results('DESCRIBE '.$table_list[$i]);
		$query .= "-- ---------------------------------------------------------".$lnbr;
		$query .= "-- Creation de la table ".$table_list[$i].$lnbr;
		$query .= "-- ---------------------------------------------------------".$lnbr;
		$query .= 'DROP TABLE IF EXISTS `'.$table_list[$i].'`;'.str_repeat($lnbr, 2);
		$query .= 'CREATE TABLE `'.$table_list[$i]. '` (';
		$tmp = '';

		foreach($results as $row) {
			$query .= '`' . $row->Field.'` '.$row->Type;

            if ($row->Null != 'YES') { $query .= ' NOT NULL'; }
            if ($row->Default != '') { $query .= ' DEFAULT \''.$row->Default.'\''; }
            if ($row->Extra) { $query .= ' ' . strtoupper($row->Extra); }
            if ($row->Key == 'PRI') { $tmp = 'primary key('.$row->Field.')'; }

            $query .= ',';
		}
		$query .= $tmp.');'.str_repeat($lnbr, 2);
 		

		$results = $connect->get_results('SELECT * FROM '.$table_list[$i]);
		if(!empty($results)) {
	 		$query .= "-- ---------------------------------------------------------".$lnbr;
			$query .= "-- Insertion dans la table ".$table_list[$i].$lnbr;
			$query .= "-- ---------------------------------------------------------".$lnbr;
			foreach($results as $row) {
				$query .= 'INSERT INTO `'.$table_list[$i].'` (';
				$data = Array();
				while (list($key, $value) = @each($row)) {
					$data['keys'][] = $key; $data['values'][] = addslashes($value);
				}
	 
	            $query .= join($data['keys'], ', ').') VALUES (\''.join($data['values'], '\', \'').'\');'.$lnbr;
	        }
	        $query .= str_repeat($lnbr, 2);
    	}
    }
	return $query;
} 
}
?>