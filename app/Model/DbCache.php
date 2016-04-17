<?
/**
 * Engine to store cache.
 * Hash used to speed up saving\retrieveing data. Key is also stored
 */
App::uses('AppModel', 'Model');
class DbCache extends AppModel {
	public $useTable = 'cache_db';
	protected $altDbConfig = 'giperzap';
	
	private function hash($key) {
		return sha1($key);
	}
	
	public function setValue($key, $value, $expires = null) {
		$_ret = false;
		try {
			$this->trxBegin();
			
			$hash = $this->hash($key);
			$conditions = compact('hash');
			$row = $this->find('first', compact('conditions'));
			$this->clear();
			
			if ($row) {
				$_ret = $this->save(array('id' => $row[$this->alias]['id'], 'value' => $value, 'expires' => $expires));
			} else {
				$_ret = $this->save(compact('hash', 'key', 'value', 'expires'));
			}
			
			$this->trxCommit();
			
		} catch (Exception $e) {
			$this->trxRollback();
			
		}
		return $_ret;
	}
	
	public function getValue($key) {
		$hash = $this->hash($key);
		$conditions = compact('hash');
		$row = $this->find('first', compact('conditions'));
		return $row;
	}
}
