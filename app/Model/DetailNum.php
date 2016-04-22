<?php
App::uses('AppModel', 'Model');
class DetailNum extends AppModel {
	protected $altDbConfig = 'vitacars';

	const ORIG = 1;
	const CROSS = 2;

	public function strip($q) {
		$q = str_replace(array('.', '-', '/', '\\'), '', $q);
		while (strpos($q, '0') === 0) { // вырезаем лидирующие нули
			$q = substr($q, 1);
		}
		return $q;
	}

	public function stripList($detail_nums) {
		$detail_nums = explode(',', str_replace(' ', ',', str_replace(array('   ', '  ', ' '), ' ', trim($detail_nums))));
		$numbers = array();
		foreach($detail_nums as $dn) {
			$dn = $this->strip($dn);
			if ($dn) {
				$numbers[] = $dn;
			}
		}
		return array_unique($numbers);
	}

	public function findDetails($detail_nums, $lFindSame = true, $numType = false) {
		$conditions = array('detail_num' => $detail_nums);
		if (strpos(implode('', $detail_nums), '*') !== false) {
			if (count($detail_nums) == 1) {
				$conditions = array('detail_num LIKE ' => str_replace('*', '%', $detail_nums[0]));
			} else {
				$conditions = array('OR' => array());
				foreach($detail_nums as $dn) {
					$conditions['OR'][] = array('detail_num LIKE "' => str_replace('*', '%', $dn).'"');
				}
			}
		}
		if ($numType) {
			$conditions['num_type'] = $numType;
		}
		$aRows = $this->find('all', compact('conditions'));
		$product_ids = array_unique(Hash::extract($aRows, '{n}.DetailNum.product_id'));
		if (!$lFindSame) {
			return $product_ids;
		}

		$conditions = array('product_id' => $product_ids);
		if ($numType) {
			$conditions['num_type'] = $numType;
		}
		$aRows = $this->find('all', compact('conditions'));
		$nums = array_unique(Hash::extract($aRows, '{n}.DetailNum.detail_num'));
		if (count($detail_nums) != count($nums) && count($nums) < 1000) {
			return $this->findDetails($nums, $lFindSame, $numType);
		}
		return $product_ids;
	}

	public function isDigitWord($q) {
		$q = mb_strtolower($q);
		for($i = 0; $i < mb_strlen($q); $i++) {
			$ch = mb_substr($q, $i, 1);
			if (!preg_match('/[a-z0-9\-\.\\/]/', $ch)) {
				return false;
			}
		}
		return preg_match('/.*[0-9]+.*/', $q) && true;
	}
}
