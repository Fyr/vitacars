<?php
App::uses('AppModel', 'Model');
class ZzapCache extends AppModel {
	public $useTable = 'zzap_cache';
	protected $altDbConfig = 'giperzap';
	
	public function setCache($method, $request, $response) {
		try {
			$this->trxBegin();
			
			$conditions = compact('method', 'request');
			$row = $this->find('first', compact('conditions'));
			$this->clear();
			if ($row) {
				$this->save(array('id' => $row['ZzapCache']['id'], 'response' => $response, 'used' => $row['ZzapCache']['used'] + 1));
			} else {
				$used = 0;
				$this->save(compact('method', 'request', 'response', 'used'));
			}
			
			$this->trxCommit();
		} catch (Exception $e) {
			$this->trxRollback();
		}
	}
	
	public function getCache($method, $request) {
		$conditions = compact('method', 'request');
		$row = $this->find('first', compact('conditions'));
		return $row;
	}
}
