<?php
App::uses('AppModel', 'Model');
class SearchHistory extends AppModel {
	public $useTable = 'search_history';

	public function processSearch($q, $detail_num, $aRowset, $user_id) {
		$this->DetailNum = $this->loadModel('DetailNum');
		$this->SearchLog = $this->loadModel('SearchLog');

		$this->trxBegin();
		$this->save(compact('user_id', 'q', 'detail_num'));
		$search_history_id = $this->id;
		foreach($aRowset as $row) {
			$detail_nums = $this->DetailNum->stripList($row['Product']['detail_num']);
			if (in_array($detail_num, $detail_nums)) {
				$product_id = $row['Product']['id'];
				$this->SearchLog->clear();
				if (!$this->SearchLog->save(compact('search_history_id', 'product_id'))) {
					$this->trxRollback();
					return false;
				}
			}

		}
		$this->trxCommit();
		return true;
	}
}
