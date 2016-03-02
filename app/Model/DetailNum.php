<?php
App::uses('AppModel', 'Model');
class DetailNum extends AppModel {

	public function strip($q) {
		return str_replace(array('.', '-', '/', '\\'), '', $q);
	}

	public function stripList($detail_nums) {
		$detail_nums = explode(',', str_replace(' ', ',', str_replace(array('   ', '  ', ' '), ' ', trim($detail_nums))));
		$numbers = array();
		foreach($detail_nums as $dn) {
			$numbers[] = $this->strip($dn);
		}
		return array_unique($numbers);
	}

	public function findDetails($detail_nums, $lFindSame = true) {
		$aRows = $this->find('all', array('conditions' => array('detail_num' => $detail_nums)));
		$product_ids = array_unique(Hash::extract($aRows, '{n}.DetailNum.product_id'));
		if (!$lFindSame) {
			return $product_ids;
		}
		$aRows = $this->find('all', array('conditions' => array('product_id' => $product_ids)));
		$nums = array_unique(Hash::extract($aRows, '{n}.DetailNum.detail_num'));
		if (count($detail_nums) != count($nums)) {
			return $this->findDetails($nums);
		}
		return $product_ids;
	}
}
