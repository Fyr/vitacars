<?php
/**
 * Class to implement DB-based cache
 * Options: 
 * 
 Cache::config('configname', array(
	'engine' => 'DbTable', // [required]
	'duration' => '+1 days', // [optional] 0 - never expires
	'prefix' => 'namespace', // [optional] - key will be written with this prefix
	'storage' => 'cache_techdoc', // [optional] DB table to store data in
	'lock' => false, // [optional] use file locking
	'serialize' => true, // [optional]
 ));
  
 */

App::uses('DbCache', 'Model');
class DbTableEngine extends CacheEngine {

	public $settings = array(), $data = array();

	private function tableName() {
		return 'cache_'.strtolower($this->settings['prefix']);
	}
	
	private function getModel() {
		return $this->model;
	}
	
	public function init($settings = array()) {
		$settings += array(
			'engine' => 'DbTable',
			'storage' => 'cache_db',
			'prefix' => '',
			'duration' => false,
			'serialize' => true
		);
		parent::init($settings);
		
		$this->model = new DbCache(false, $this->settings['storage']);
		return true;
	}

/**
 * Garbage collection. Permanently remove all expired and deleted data
 *
 * @param integer $expires [optional] An expires timestamp, invalidating all data before.
 * @return boolean True if garbage collection was successful, false on failure
 */
	public function gc($expires = null) {
		return $this->clear(true);
	}

/**
 * Write data for key into cache
 *
 * @param string $key Identifier for the data
 * @param mixed $data Data to be cached
 * @param integer $duration How long to cache the data, in seconds
 * @return boolean True if the data was successfully cached, false on failure
 */
	public function write($key, $data, $duration) {
		// $expires = ($this->settings['duration']) ? date('Y-m-d H:i:s', time() + $duration) : null;
		$expires = null;
		return $this->getModel()->setValue($key, ($this->settings['serialize']) ? serialize($data) : $data, $expires);
	}

/**
 * Read a key from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 */
	public function read($key) {
		$this->data = $this->getModel()->getValue($key);
		if (!$this->data) {
			return null;
		}
		/*
		if ($this->settings['duration'] && !$this->data || strtotime($this->data[$this->getModel()->alias]['expires']) < time()) {
			return null;
		}
		*/
		return ($this->settings['serialize']) ? unserialize($this->data('value')) : $this->data('value');
	}
	
/**
 * Cache Engine settings.
 * Method is used to return a full data of cache, because there's no way to call any method of DbTableEngine directly
 *
 * @return array settings
 */
	public function settings() {
		$alias = $this->getModel()->alias;
		$this->settings['data'] = (isset($this->data[$alias])) ? $this->data[$alias] : array();
		return $this->settings;
	}

/**
 * Returns data from used cache record
 *
 * @param string $field
 * @return mixed
 */
	public function data($field = '') {
		return ($field) ? $this->data[$this->getModel()->alias][$field] : $this->data;
	}

/**
 * Delete a key from the cache
 *
 * @param string $key Identifier for the data
 * @return boolean True if the value was successfully deleted, false if it didn't exist or couldn't be removed
 */
	public function delete($key) {
		$this->data = $this->getModel()->getValue($key);
		
		// fdebug(array('delete Cache', $this->getModel()->alias, $this->data, $key));
		
		$this->getModel()->delete($this->data('id'));
		return true;
	}

/**
 * Delete all values from the cache
 *
 * @param boolean $check Optional - only delete expired cache items
 * @return boolean True if the cache was successfully cleared, false otherwise
 */
	public function clear($check) {
		$conditions = true;
		/*
		if ($check) {
			$conditions = array('expires IS NOT NULL', 'expires <' => date('Y-m-d H:i:s'));
		}
		*/
		// $this->getModel()->deleteAll($conditions, false, false);
		return true;
	}

/**
 * Not implemented
 *
 * @param string $key
 * @param integer $offset
 * @return void
 * @throws CacheException
 */
	public function decrement($key, $offset = 1) {
		throw new CacheException(__d('cake_dev', 'Files cannot be atomically decremented.'));
	}

/**
 * Not implemented
 *
 * @param string $key
 * @param integer $offset
 * @return void
 * @throws CacheException
 */
	public function increment($key, $offset = 1) {
		throw new CacheException(__d('cake_dev', 'Files cannot be atomically incremented.'));
	}

/**
 * Not implemented
 * Recursively deletes all files under any directory named as $group
 *
 * @return boolean success
 */
	public function clearGroup($group) {
		return true;
	}
	
	static public function runTests() {
		/**
		 * TODO:
		 * 1. Auto-create table
		 * 2. Handle expiration date
		 */
		$sql = "DROP TABLE IF EXISTS `%1\$s`;
CREATE TABLE `%1\$s` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created` datetime NOT NULL,
  `modified` datetime DEFAULT NULL,
  `expires` datetime DEFAULT NULL,
  `hash` varchar(40) NOT NULL,
  `key` text,
  `value` longtext,
  `used` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		$model = new AppModel(0, false);
		$model->query(sprintf($sql, 'cache_test'));
		$model->query(sprintf($sql, 'cache_test2'));
		
		$cache = new AppModel(0, 'cache_test');
		$cache2 = new AppModel(0, 'cache_test2');
		
		Cache::config('default', array(
			'engine' => 'DbTable', //[required]
			'duration' => '+1 days', //[optional]
			'storage' => 'cache_test', //[optional]  prefix every cache file with this string
			'lock' => false, //[optional]  use file locking
			'serialize' => true, //[optional]
		));
		
		Cache::config('testcache2', array(
			'engine' => 'DbTable', //[required]
			'duration' => false, //[optional]
			'storage' => 'cache_test2', //[optional]  prefix every cache file with this string
			'lock' => false, //[optional]  use file locking
			'serialize' => true, //[optional]
		));
		
		App::uses('Test', 'Vendor');
		
		$key1 = 'testkey1';
		$key2 = 'testkey_2';
		$key3 = 'sometestkey3';
		
		$value1 = 'some value1';
		$value2 = 2;
		$value3 = array('key1' => 'value1', 'key2' => 'value_2');
		
		// Test 1 - read empty cache
		Test::assertEqual('Test1', array(null, null), array(Cache::read($key1), Cache::read($key3, 'testcache2')));
		
		// Test 2 - write cache on diff.configs
		$time = time(); 
		$dt = date('Y-m-d H:i:s', $time);
		$dt2 = date('Y-m-d H:i:s', $time + DAY);
		
		// Cache::write($key1, $value1);
		Cache::write($key2, $value2, 'testcache2');
		// Cache::write($key3, $value3);
		
		$trueRes = array(
			array(
				array('AppModel' => array('id' => '1', 'created' => $dt, 'modified' => $dt, 'expires' => $dt2, 'hash' => sha1($key1), 'key' => $key1, 'value' => serialize($value1), 'used' => '0')),
				array('AppModel' => array('id' => '2', 'created' => $dt, 'modified' => $dt, 'expires' => $dt2, 'hash' => sha1($key3), 'key' => $key3, 'value' => serialize($value3), 'used' => '0')),
			),
			array(
				array('AppModel' => array('id' => '1', 'created' => $dt, 'modified' => $dt, 'expires' => null, 'hash' => sha1($key2), 'key' => $key2, 'value' => serialize($value2), 'used' => '0')),
			)
		);
		Test::assertEqual('Test2', $trueRes, 
			array(
				$cache->find('all'), 
				$cache2->find('all')
			)
		);
		
		
		
		/*
		$sql = 'DROP TABLE IF EXISTS `%s`';
		$model->query(sprintf($sql, 'cache_test'));
		$model->query(sprintf($sql, 'cache_test2'));
		*/
	}
}
