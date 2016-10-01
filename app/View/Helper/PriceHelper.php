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

	public function jsFunction($currency = '', $lPrefix = true) {
		$currency = ($currency) ? $currency : Configure::read('Settings.price_currency');
		$script = '
function number_format( number, decimals, dec_point, thousands_sep ) {	// Format a number with grouped thousands
	var i, j, kw, kd, km;

	if( isNaN(decimals = Math.abs(decimals)) ){
		decimals = 2;
	}
	if( dec_point == undefined ){
		dec_point = ",";
	}
	if( thousands_sep == undefined ){
		thousands_sep = ".";
	}

	i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

	if( (j = i.length) > 3 ){
		j = j % 3;
	} else{
		j = 0;
	}

	km = (j ? i.substr(0, j) + thousands_sep : "");
	kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
	kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");

	return km + kw + kd;
}

	var Price = { format: function(sum){
		var sign = (sum < 0) ? "-" : "";
		sum = Math.abs(sum);
		sum = number_format(
			sum,
			"'.Configure::read('Settings.decimals_'.$currency).'",
			"'.Configure::read('Settings.float_div_'.$currency).'",
			"'.Configure::read('Settings.int_div_'.$currency).'"
		);
';
		if ($lPrefix) {
			$script .= '

		return sign + "' . Configure::read('Settings.price_prefix_' . $currency) . '" + sum + "' . Configure::read('Settings.price_postfix_' . $currency) . '";
	}}
';
		} else {
			$script .= '

		return sign + sum;
	}}
';

		}
		return $script;
	}
	
	public function num2str($num, $currency = '') {
		$currency = ($currency) ? $currency : Configure::read('Settings.price_currency');

		$nul = 'ноль';
		$ten = array(
			array('','один','два','три','четыре','пять','шесть','семь', 'восемь','девять'),
			array('','одна','две','три','четыре','пять','шесть','семь', 'восемь','девять'),
		);
		$a20 = array('десять','одиннадцать','двенадцать','тринадцать','четырнадцать' ,'пятнадцать','шестнадцать','семнадцать','восемнадцать','девятнадцать');
		$tens = array(2=>'двадцать','тридцать','сорок','пятьдесят','шестьдесят','семьдесят' ,'восемьдесят','девяносто');
		$hundred = array('','сто','двести','триста','четыреста','пятьсот','шестьсот', 'семьсот','восемьсот','девятьсот');
		$aUnits = array(
			'byn' => array( // Units
				array('копейка' ,'копейки' ,'копеек',	 1),
				array('белорусский рубль'  ,'белорусских рубля', 'белорусских рублей', 0),
				array('тысяча'  ,'тысячи'  ,'тысяч'     ,1),
				array('миллион' ,'миллиона','миллионов' ,0),
				array('миллиард','милиарда','миллиардов',0),
			),
			'rur' => array( // Units
				array('копейка' ,'копейки' ,'копеек',	 1),
				array('российский рубль'   ,'российских рубля', 'российских рублей', 0),
				array('тысяча'  ,'тысячи'  ,'тысяч'     ,1),
				array('миллион' ,'миллиона','миллионов' ,0),
				array('миллиард','милиарда','миллиардов',0),
			),
			'usd' => array( // Units
				array('цент', 'цента', 'центов', 1),
				array('доллар США', 'доллара США', 'долларов США', 0),
				array('тысяча'  ,'тысячи'  ,'тысяч'     ,1),
				array('миллион' ,'миллиона','миллионов' ,0),
				array('миллиард','милиарда','миллиардов',0),
			),
			'eur' => array( // Units
				array('евроцент', 'евроцента', 'евроцентов', 1),
				array('евро', 'евро', 'евро', 0),
				array('тысяча'  ,'тысячи'  ,'тысяч'     ,1),
				array('миллион' ,'миллиона','миллионов' ,0),
				array('миллиард','милиарда','миллиардов',0),
			)
		);
		if (isset($aUnits[$currency])) {
			$unit = $aUnits[$currency];
		} else {
			$unit = array( // Units
				array('копейка', 'копейки', 'копеек', 1),
				array('рубль', 'рубля', 'рублей', 0),
				array('тысяча', 'тысячи', 'тысяч', 1),
				array('миллион', 'миллиона', 'миллионов', 0),
				array('миллиард', 'милиарда', 'миллиардов', 0),
			);
		}
		//
		list($rub,$kop) = explode('.',sprintf("%015.2f", floatval($num)));
		$out = array();
		if (intval($rub)>0) {
			foreach(str_split($rub,3) as $uk=>$v) { // by 3 symbols
				if (!intval($v)) continue;
				$uk = sizeof($unit)-$uk-1; // unit key
				$gender = $unit[$uk][3];
				list($i1,$i2,$i3) = array_map('intval',str_split($v,1));
				// mega-logic
				$out[] = $hundred[$i1]; # 1xx-9xx
				if ($i2>1) $out[]= $tens[$i2].' '.$ten[$gender][$i3]; # 20-99
				else $out[]= $i2>0 ? $a20[$i3] : $ten[$gender][$i3]; # 10-19 | 1-9
				// units without rub & kop
				if ($uk>1) $out[]= $this->_morph($v,$unit[$uk][0],$unit[$uk][1],$unit[$uk][2]);
			} //foreach
		}
		else $out[] = $nul;
		$out[] = $this->_morph(intval($rub), $unit[1][0],$unit[1][1],$unit[1][2]); // rub
		$out[] = $kop.' '.$this->_morph($kop,$unit[0][0],$unit[0][1],$unit[0][2]); // kop
		return trim(preg_replace('/ {2,}/', ' ', join(' ',$out)));
	}

	/**
	 * Склоняем словоформу
	 */
	private function _morph($n, $f1, $f2, $f5) {
		$n = abs(intval($n)) % 100;
		if ($n>10 && $n<20) return $f5;
		$n = $n % 10;
		if ($n>1 && $n<5) return $f2;
		if ($n==1) return $f1;
		return $f5;
	}
}
