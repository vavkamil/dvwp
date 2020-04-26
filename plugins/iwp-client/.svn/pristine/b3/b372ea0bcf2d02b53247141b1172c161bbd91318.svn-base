<?php

if ( ! defined('ABSPATH') )
	die();

$iwp_addon_moredatabase = new IWP_MMB_Addon_MoreDatabase;

class IWP_MMB_Addon_MoreDatabase {

	private $database_tables;

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_filter('IWP_encrypt_file', array($this, 'encrypt_file'), 10, 5);
	}

	/**
	 * This function encrypts the database when specified. Used in backup.php.
	 *
	 * @param  array  $result
	 * @param  string $file           this is the file name of the db zip to be encrypted
	 * @param  string $encryption     This is the encryption word (salting) to be used when encrypting the data
	 * @param  string $whichdb        This specifies the correct DB
	 * @param  string $whichdb_suffix This spcifies the DB suffix
	 * @return string                 returns the encrypted file name
	 */
	public function encrypt_file($result, $file, $encryption, $whichdb, $whichdb_suffix) {

		global $iwp_backup_core;
		$iwp_backup_dir = $iwp_backup_core->backups_dir_location();
		$iwp_backup_core->jobdata_set('jobstatus', 'dbencrypting'.$whichdb_suffix);
		$encryption_result = 0;
		$time_started = microtime(true);
		$file_size = @filesize($iwp_backup_dir.'/'.$file)/1024;

		$memory_limit = ini_get('memory_limit');
		$memory_usage = round(@memory_get_usage(false)/1048576, 1);
		$memory_usage2 = round(@memory_get_usage(true)/1048576, 1);
		$iwp_backup_core->log("Encryption being requested: file_size: ".round($file_size, 1)." KB memory_limit: $memory_limit (used: ${memory_usage}M | ${memory_usage2}M)");
		
		$encrypted_file = IWP_MMB_Encryption::encrypt($iwp_backup_dir.'/'.$file, $encryption);

		if (false !== $encrypted_file) {
			// return basename($file);
			$time_taken = max(0.000001, microtime(true)-$time_started);

			$checksums = $iwp_backup_core->which_checksums();
			
			foreach ($checksums as $checksum) {
				$cksum = hash_file($checksum, $iwp_backup_dir.'/'.$file.'.crypt');
				$iwp_backup_core->jobdata_set($checksum.'-db'.(('wp' == $whichdb) ? '0' : $whichdb.'0').'.crypt', $cksum);
				$iwp_backup_core->log("$file: encryption successful: ".round($file_size, 1)."KB in ".round($time_taken, 2)."s (".round($file_size/$time_taken, 1)."KB/s) ($checksum checksum: $cksum)");
				
			}

			// Delete unencrypted file
			@unlink($iwp_backup_dir.'/'.$file);

			$iwp_backup_core->jobdata_set('jobstatus', 'dbencrypted'.$whichdb_suffix);

			return basename($file.'.crypt');
		} else {
			$iwp_backup_core->log("Encryption error occurred when encrypting database. Encryption aborted.");
			$iwp_backup_core->log(__("Encryption error occurred when encrypting database. Encryption aborted.", 'InfiniteWP'), 'error');
			return basename($file);
		}
	}

}
