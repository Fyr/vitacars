<?php
App::uses('AppModel', 'Model');
class SearchHistory extends AppModel {
	public $useTable = 'search_history';

	public function processSearch($q, $detail_num, $aRowset, $user_id) {
		$this->DetailNum = $this->loadModel('DetailNum');
		$this->SearchDetail = $this->loadModel('SearchDetail');

		$this->trxBegin();
		$this->save(compact('user_id', 'q', 'detail_num'));
		$search_history_id = $this->id;
		foreach($aRowset as $row) {
			$detail_nums = $this->DetailNum->stripList($row['Product']['detail_num']);
			if (in_array($detail_num, $detail_nums)) {
				$product_id = $row['Product']['id'];
				$this->SearchDetail->clear();
				if (!$this->SearchDetail->save(compact('search_history_id', 'product_id'))) {
					$this->trxRollback();
					return false;
				}
			}

		}
		$this->trxCommit();
		return true;
	}

	public function getProducts($date, $date2, $minQty = 0, $maxQty = 0) {
		$conditions = $this->dateRange('created', $date, $date2);
		$having = array();
		if (!TEST_ENV) {
			$conditions['user_id <> '] = 1;
		}
		$queries = $this->find('all', compact('conditions'));
		if ($queries) {
			$this->SearchDetail = $this->loadModel('SearchDetail');
			$ids = Hash::extract($queries, '{n}.SearchHistory.id');

			$conditions = array('search_history_id' => $ids);
			$fields = array('COUNT(*) AS qty', 'product_id');
			$group = array('product_id');
			$order = array('qty' => 'DESC');
			$aRows = $this->SearchDetail->find('all', compact('conditions', 'fields', 'group', 'order'));

			$rows = array();
			foreach($aRows as $row) {
				$rows[] = array('qty' => $row[0]['qty'], 'product_id' => $row['SearchDetail']['product_id']);
			}
			return compact('queries', 'rows');
		}
	}
}
