<?php
App::uses('AppHelper', 'View/Helper');
class PriceHelper extends AppHelper {

	public function format($sum, $currency = '') {
		$currency = ($currency) ? $currency : Configure::read('Settings.price_currency');
		$sum = number_format(
			$sum,
			Configure::read('Settings.decimals_'.$currency),
			Configure::read('Settings.float_div_'.$currency),
			Configure::read('Settings.int_div_'.$currency)
		);
		$sum = Configure::read('Settings.price_prefix_'.$currency).$sum.Configure::read('Settings.price_postfix_'.$currency);
		return str_replace('$P', $this->symbolP(), $sum);
	}

	public function symbolP() {
		return '<span class="rubl">₽</span>';
	}
}
