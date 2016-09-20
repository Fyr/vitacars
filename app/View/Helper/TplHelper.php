<?php
App::uses('AppHelper', 'View/Helper');
class TplHelper extends AppHelper {

	public function format($tpl, $data = array()) {
		foreach($data as $key => $val) {
			if (is_array($val)) {
				foreach($val as $_key => $_val) {
					$tpl = str_replace('{$'.$key.'.'.$_key.'}', $_val, $tpl);
				}
			} else {
				$tpl = str_replace('{$'.$key.'}', $val, $tpl);
			}
		}
		return $tpl;
	}
}
