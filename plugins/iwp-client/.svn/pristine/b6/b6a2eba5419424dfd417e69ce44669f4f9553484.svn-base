<?php
// Adapted from WP Social under the GPL - thanks to Alex King (https://github.com/crowdfavorite/wp-social)
/**
 * Semaphore Lock Management
 */

if ( ! defined('ABSPATH') )
	die();

class IWP_MMB_Semaphore {

	/**
	 * Initializes the semaphore object.
	 *
	 * @static
	 */
	public static function factory() {
		return new self;
	}

	/**
	 * @var bool
	 */
	protected $lock_broke = false;

	public $lock_name = 'lock';

	/**
	 * Attempts to start the lock. If the rename works, the lock is started.
	 *
	 * @return bool
	 */
	public function lock() {
		global $wpdb, $iwp_backup_core;

		// Attempt to set the lock
		$affected = $wpdb->query("
			UPDATE $wpdb->options
			   SET option_name = 'IWP_locked_".$this->lock_name."'
			 WHERE option_name = 'IWP_unlocked_".$this->lock_name."'
		");

		if ($affected == '0' and !$this->stuck_check()) {
			$iwp_backup_core->log('Semaphore lock ('.$this->lock_name.', '.$wpdb->options.') failed (line '.__LINE__.')');
			return false;
		}

		// Check to see if all processes are complete
		$affected = $wpdb->query("
			UPDATE $wpdb->options
			   SET option_value = CAST(option_value AS UNSIGNED) + 1
			 WHERE option_name = 'IWP_semaphore_".$this->lock_name."'
			   AND option_value = '0'
		");
		if ($affected != '1') {
			if (!$this->stuck_check()) {
				$iwp_backup_core->log('Semaphore lock ('.$this->lock_name.', '.$wpdb->options.') failed (line '.__LINE__.')');
				return false;
			}

			// Reset the semaphore to 1
			$wpdb->query("
				UPDATE $wpdb->options
				   SET option_value = '1'
				 WHERE option_name = 'IWP_semaphore_".$this->lock_name."'
			");

			$iwp_backup_core->log('Semaphore ('.$this->lock_name.', '.$wpdb->options.') reset to 1');
		}

		// Set the lock time
		$wpdb->query($wpdb->prepare("
			UPDATE $wpdb->options
			   SET option_value = %s
			 WHERE option_name = 'IWP_last_lock_time_".$this->lock_name."'
		", current_time('mysql', 1)));
		$iwp_backup_core->log('Set semaphore last lock ('.$this->lock_name.') time to '.current_time('mysql', 1));

		$iwp_backup_core->log('Semaphore lock ('.$this->lock_name.') complete');
		update_option('IWP_backup_status', '1');
		return true;
	}

	/**
	 * Increment the semaphore.
	 *
	 * @param  array  $filters
	 * @return Social_Semaphore
	 */
	public function increment(array $filters = array()) {
		global $wpdb;

		if (count($filters)) {
			// Loop through all of the filters and increment the semaphore
			foreach ($filters as $priority) {
				for ($i = 0, $j = count($priority); $i < $j; ++$i) {
					$this->increment();
				}
			}
		}
		else {
			$wpdb->query("
				UPDATE $wpdb->options
				   SET option_value = CAST(option_value AS UNSIGNED) + 1
				 WHERE option_name = 'IWP_semaphore_".$this->lock_name."'
			");
			$iwp_backup_core->log('Incremented the semaphore ('.$this->lock_name.') by 1');
		}

		return $this;
	}

	/**
	 * Decrements the semaphore.
	 *
	 * @return void
	 */
	public function decrement() {
		global $wpdb, $iwp_backup_core;

		$wpdb->query("
			UPDATE $wpdb->options
			   SET option_value = CAST(option_value AS UNSIGNED) - 1
			 WHERE option_name = 'IWP_semaphore_".$this->lock_name."'
			   AND CAST(option_value AS UNSIGNED) > 0
		");
		$iwp_backup_core->log('Decremented the semaphore ('.$this->lock_name.') by 1');
	}

	/**
	 * Unlocks the process.
	 *
	 * @return bool
	 */
	public function unlock() {
		global $wpdb, $iwp_backup_core;

		// Decrement for the master process.
		$this->decrement();

		$result = $wpdb->query("
			UPDATE $wpdb->options
			   SET option_name = 'IWP_unlocked_".$this->lock_name."'
			 WHERE option_name = 'IWP_locked_".$this->lock_name."'
		");
		update_option('IWP_backup_status', '0');
		if ($result == '1') {
			$iwp_backup_core->log('Semaphore ('.$this->lock_name.') unlocked');
			return true;
		}

		$iwp_backup_core->log('Semaphore ('.$this->lock_name.', '.$wpdb->options.') still locked ('.$result.')');
		return false;
	}

	/**
	 * Attempts to jiggle the stuck lock loose.
	 *
	 * @return bool
	 */
	private function stuck_check() {
		global $wpdb, $iwp_backup_core;

		// Check to see if we already broke the lock.
		if ($this->lock_broke) {
			return true;
		}

		$current_time = current_time('mysql', 1);
		$three_minutes_before = gmdate('Y-m-d H:i:s', time()-(defined('IWP_SEMAPHORE_LOCK_WAIT') ? IWP_SEMAPHORE_LOCK_WAIT : 180));

		$affected = $wpdb->query($wpdb->prepare("
			UPDATE $wpdb->options
			   SET option_value = %s
			 WHERE option_name = 'IWP_last_lock_time_".$this->lock_name."'
			   AND option_value <= %s
		", $current_time, $three_minutes_before));

		if ('1' == $affected) {
			$iwp_backup_core->log('Semaphore ('.$this->lock_name.', '.$wpdb->options.') was stuck, set lock time to '.$current_time);
			$this->lock_broke = true;
			return true;
		}

		return false;
	}

}
