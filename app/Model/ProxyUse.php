<?php
App::uses('AppModel', 'Model');
class ProxyUse extends AppModel {
	protected $altDbConfig = 'giperzap';
	
	public function getProxy($object_type) {
		$conditions = array('object_type' => $object_type, 'active' => 1);
		$order = array('used', 'modified');
		$row = $this->find('first', compact('conditions', 'order'));
		return $row;
	}
	
	public function useProxy($host) {
		$sql = sprintf('UPDATE %s SET used = used + 1, modified = "%s" WHERE host = "%s"', 
			$this->getTableName(), date('Y-m-d H:i:s'), $host);
		try {
			$this->trxBegin();
			$this->query($sql);
			$this->trxCommit();
		} catch (Exception $e) {
			$this->trxRollback();
		}
	}
}
