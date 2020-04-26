<?php

if ( ! defined('ABSPATH') )
	die();

if (class_exists('ZipArchive')):
class IWP_MMB_ZipArchive extends ZipArchive {
	public $last_error = 'Unknown: ZipArchive does not return error messages';
}
endif;

# A ZipArchive compatibility layer, with behaviour sufficient for our usage of ZipArchive
class IWP_MMB_PclZip {

	protected $pclzip;
	protected $path;
	protected $addfiles;
	protected $adddirs;
	private $statindex;
	private $include_mtime = false;
	public $last_error;

	public function __construct() {
		$this->addfiles = array();
		$this->adddirs = array();
		// Put this in a non-backed-up, writeable location, to make sure that huge temporary files aren't created and then added to the backup - and that we have somewhere writable
		global $iwp_backup_core;
		if (!defined('PCLZIP_TEMPORARY_DIR')) define('PCLZIP_TEMPORARY_DIR', trailingslashit($iwp_backup_core->backups_dir_location()));
	}

	# Used to include mtime in statindex (by default, not done - to save memory; probably a bit paranoid)
	public function ud_include_mtime() {
		$this->include_mtime = true;
	}

	public function __get($name) {
		if ($name == 'numFiles' || $name == 'numAll') {

			if (empty($this->pclzip)) return false;

			$statindex = $this->pclzip->listContent();

			if (empty($statindex)) {
				$this->statindex = array();
				// We return a value that is == 0, but allowing a PclZip error to be detected (PclZip returns 0 in the case of an error).
				if (0 === $statindex) $this->last_error = $this->pclzip->errorInfo(true);
				return (0 === $statindex) ? false : 0;
			}

			if ($name == 'numFiles') {
				
				$result = array();
				foreach ($statindex as $i => $file) {
					if (!isset($statindex[$i]['folder']) || 0 == $statindex[$i]['folder']) {
						$result[] = $file;
					}
					unset($statindex[$i]);
				}

				$this->statindex=$result;

			} else {
				$this->statindex=$statindex;
			}

			return count($this->statindex);
		}

		return null;

	}

	public function statIndex($i) {
		if (empty($this->statindex[$i])) return array('name' => null, 'size' => 0);
		$v = array('name' => $this->statindex[$i]['filename'], 'size' => $this->statindex[$i]['size']);
		if ($this->include_mtime) $v['mtime'] = $this->statindex[$i]['mtime'];
		return $v;
	}

	public function open($path, $flags = 0) {
	
		if(!class_exists('PclZip')) include_once(ABSPATH.'/wp-admin/includes/class-pclzip.php');
		if(!class_exists('PclZip')) {
			$this->last_error = "No PclZip class was found";
			return false;
		}

		# Route around PHP bug (exact version with the problem not known)
		$ziparchive_create_match = (version_compare(PHP_VERSION, '5.2.12', '>') && defined('ZIPARCHIVE::CREATE')) ? ZIPARCHIVE::CREATE : 1;

		if ($flags == $ziparchive_create_match && file_exists($path)) @unlink($path);

		$this->pclzip = new PclZip($path);

		if (empty($this->pclzip)) {
			$this->last_error = 'Could not get a PclZip object';
			return false;
		}

		# Make the empty directory we need to implement addEmptyDir()
		global $iwp_backup_core;
		$iwp_backup_dir = $iwp_backup_core->backups_dir_location();
		if (!is_dir($iwp_backup_dir.'/emptydir') && !mkdir($iwp_backup_dir.'/emptydir')) {
			$this->last_error = "Could not create empty directory ($iwp_backup_dir/emptydir)";
			return false;
		}

		$this->path = $path;

		return true;

	}

	# Do the actual write-out - it is assumed that close() is where this is done. Needs to return true/false
	public function close() {
		if (empty($this->pclzip)) {
			$this->last_error = 'Zip file was not opened';
			return false;
		}

		global $iwp_backup_core;
		$iwp_backup_dir = $iwp_backup_core->backups_dir_location();

		$activity = false;

		# Add the empty directories
		foreach ($this->adddirs as $dir) {
			if (false == $this->pclzip->add($iwp_backup_dir.'/emptydir', PCLZIP_OPT_REMOVE_PATH, $iwp_backup_dir.'/emptydir', PCLZIP_OPT_ADD_PATH, $dir)) {
				$this->last_error = $this->pclzip->errorInfo(true);
				return false;
			}
			$activity = true;
		}

		foreach ($this->addfiles as $rdirname => $adirnames) {
			foreach ($adirnames as $adirname => $files) {
				if (false == $this->pclzip->add($files, PCLZIP_OPT_REMOVE_PATH, $rdirname, PCLZIP_OPT_ADD_PATH, $adirname)) {
					$this->last_error = $this->pclzip->errorInfo(true);
					return false;
				}
				$activity = true;
			}
			unset($this->addfiles[$rdirname]);
		}

		$this->pclzip = false;
		$this->addfiles = array();
		$this->adddirs = array();

		clearstatcache();
		if ($activity && filesize($this->path) < 50) {
			$this->last_error = "Write failed - unknown cause (check your file permissions)";
			return false;
		}

		return true;
	}

	# Note: basename($add_as) is irrelevant; that is, it is actually basename($file) that will be used. But these are always identical in our usage.
	public function addFile($file, $add_as) {
		# Add the files. PclZip appears to do the whole (copy zip to temporary file, add file, move file) cycle for each file - so batch them as much as possible. We have to batch by dirname(). On a test with 1000 files of 25KB each in the same directory, this reduced the time needed on that directory from 120s to 15s (or 5s with primed caches).
		$rdirname = dirname($file);
		$adirname = dirname($add_as);
		$this->addfiles[$rdirname][$adirname][] = $file;
	}

	# PclZip doesn't have a direct way to do this
	public function addEmptyDir($dir) {
		$this->adddirs[] = $dir;
	}

	public function extract($path_to_extract, $path) {
		return $this->pclzip->extract(PCLZIP_OPT_PATH, $path_to_extract, PCLZIP_OPT_BY_NAME, $path);
	}

}

class IWP_MMB_BinZip extends IWP_MMB_PclZip {

	private $binzip;

	public function __construct() {
		global $IWP_backup;
		$this->binzip = $IWP_backup->binzip;
		if (!is_string($this->binzip)) {
			$this->last_error = "No binary zip was found";
			return false;
		}
		return parent::__construct();
	}

	public function addFile($file, $add_as) {

		global $iwp_backup_core;
		# Get the directory that $add_as is relative to
		$base = $iwp_backup_core->str_lreplace($add_as, '', $file);

		if ($file == $base) {
			// Shouldn't happen; but see: https://bugs.php.net/bug.php?id=62119
			$iwp_backup_core->log("File skipped due to unexpected name mismatch (locale: ".setlocale(LC_CTYPE, "0")."): file=$file add_as=$add_as", 'notice', false, true);
		} else {
			$rdirname = untrailingslashit($base);
			# Note: $file equals $rdirname/$add_as
			$this->addfiles[$rdirname][] = $add_as;
		}

	}

	# The standard zip binary cannot list; so we use PclZip for that
	# Do the actual write-out - it is assumed that close() is where this is done. Needs to return true/false
	public function close() {

		if (empty($this->pclzip)) {
			$this->last_error = 'Zip file was not opened';
			return false;
		}

		global $iwp_backup_core, $IWP_backup;
		$iwp_backup_dir = $iwp_backup_core->backups_dir_location();

		$activity = false;

		# BinZip does not like zero-sized zip files
		if (file_exists($this->path) && 0 == filesize($this->path)) @unlink($this->path);

		$descriptorspec = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w')
		);
		$exec = $this->binzip;
		if (defined('IWP_BINZIP_OPTS') && IWP_BINZIP_OPTS) $exec .= ' '.IWP_BINZIP_OPTS;
		$exec .= " -v -@ ".escapeshellarg($this->path);

		$last_recorded_alive = time();
		$something_useful_happened = $iwp_backup_core->something_useful_happened;
		$orig_size = file_exists($this->path) ? filesize($this->path) : 0;
		$last_size = $orig_size;
		clearstatcache();

		$added_dirs_yet = false;

		# If there are no files to add, but there are empty directories, then we need to make sure the directories actually get added
		if (0 == count($this->addfiles) && 0 < count($this->adddirs)) {
			$dir = realpath($IWP_backup->make_zipfile_source);
			$this->addfiles[$dir] = '././.';
		}
		// Loop over each destination directory name
		foreach ($this->addfiles as $rdirname => $files) {

			$process = proc_open($exec, $descriptorspec, $pipes, $rdirname);

			if (!is_resource($process)) {
				$iwp_backup_core->log('BinZip error: proc_open failed');
				$this->last_error = 'BinZip error: proc_open failed';
				return false;
			}

			if (!$added_dirs_yet) {
				# Add the directories - (in fact, with binzip, non-empty directories automatically have their entries added; but it doesn't hurt to add them explicitly)
				foreach ($this->adddirs as $dir) {
					fwrite($pipes[0], $dir."/\n");
				}
				$added_dirs_yet=true;
			}

			$read = array($pipes[1], $pipes[2]);
			$except = null;

			if (!is_array($files) || 0 == count($files)) {
				fclose($pipes[0]);
				$write = array();
			} else {
				$write = array($pipes[0]);
			}

			while ((!feof($pipes[1]) || !feof($pipes[2]) || (is_array($files) && count($files)>0)) && false !== ($changes = @stream_select($read, $write, $except, 0, 200000))) {

				if (is_array($write) && in_array($pipes[0], $write) && is_array($files) && count($files)>0) {
					$file = array_pop($files);
					// Send the list of files on stdin
					fwrite($pipes[0], $file."\n");
					if (0 == count($files)) fclose($pipes[0]);
				}

				if (is_array($read) && in_array($pipes[1], $read)) {
					$w = fgets($pipes[1]);
					// Logging all this really slows things down; use debug to mitigate
					if ($w && $IWP_backup->debug) $iwp_backup_core->log("Output from zip: ".trim($w), 'debug');
					if (time() > $last_recorded_alive + 5) {
						$iwp_backup_core->record_still_alive();
						$last_recorded_alive = time();
					}
					if (file_exists($this->path)) {
						$new_size = @filesize($this->path);
						if (!$something_useful_happened && $new_size > $orig_size + 20) {
							$iwp_backup_core->something_useful_happened();
							$something_useful_happened = true;
						}
						clearstatcache();
						# Log when 20% bigger or at least every 50MB
						if ($new_size > $last_size*1.2 || $new_size > $last_size + 52428800) {
							$iwp_backup_core->log(basename($this->path).sprintf(": size is now: %.2f MB", round($new_size/1048576,1)));
							$last_size = $new_size;
						}
					}
				}

				if (is_array($read) && in_array($pipes[2], $read)) {
					$last_error = fgets($pipes[2]);
					if (!empty($last_error)) $this->last_error = rtrim($last_error);
				}

				// Re-set
				$read = array($pipes[1], $pipes[2]);
				$write = (is_array($files) && count($files) >0) ? array($pipes[0]) : array();
				$except = null;

			}

			fclose($pipes[1]);
			fclose($pipes[2]);

			$ret = proc_close($process);

			if ($ret != 0 && $ret != 12) {
				if ($ret < 128) {
					$iwp_backup_core->log("Binary zip: error (code: $ret - look it up in the Diagnostics section of the zip manual at http://www.info-zip.org/mans/zip.html for interpretation... and also check that your hosting account quota is not full)");
				} else {
					$iwp_backup_core->log("Binary zip: error (code: $ret - a code above 127 normally means that the zip process was deliberately killed ... and also check that your hosting account quota is not full)");
				}
				if (!empty($w) && !$IWP_backup->debug) $iwp_backup_core->log("Last output from zip: ".trim($w), 'debug');
				return false;
			}

			unset($this->addfiles[$rdirname]);
		}

		return true;
	}

}
