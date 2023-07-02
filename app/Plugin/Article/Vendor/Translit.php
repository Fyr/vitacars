<?
class Translit {
	
	static function convert($st, $lUrlMode = false) {
		// Ñíà÷àëà çàìåíÿåì "îäíîñèìâîëüíûå" ôîíåìû.
		$st = mb_convert_encoding($st, 'cp1251', 'utf8');
		$st = strtr($st, "àáâãäå¸çèéêëìíîïðñòóôõûý", "abvgdeeziyklmnoprstufhye");
		$st = strtr($st, "ÀÁÂÃÄÅ¨ÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÛÝ", "ABVGDEEZIYKLMNOPRSTUFHYE");
		
		// Çàòåì - "ìíîãîñèìâîëüíûå".
		$st = strtr($st, array(
			"æ"=>"zh", "ö"=>"c", "÷"=>"ch", "ø"=>"sh", "ù"=>"shch", "ü"=>"", "ú"=>"", "þ"=>"ju", "ÿ"=>"ja",
			"Æ"=>"ZH", "Ö"=>"C", "×"=>"CH", "Ø"=>"SH", "Ù"=>"SHCH", "Ü"=>"", "ú"=>"", "Þ"=>"JU", "ß"=>"JA",
			"¿"=>"i", "¯"=>"Yi", "º"=>"ie", "ª"=>"Ye"
		));
		
		if ($lUrlMode) {
			$st = strtolower($st);
			$st = strtr($st, array(
				"'" => "", '"' => '', ' ' => '-'
			));
			$latin = 'abcdefghijklmnopqrstuvqxwz-';
			for($i = 0; $i < strlen($st); $i++) {
				if (strpos($latin, $st[$i]) === false) {
					$st[$i] = '-';
				}
			}
			$st = str_replace(array('----', '---', '--'), '-', $st);
			while ($st[strlen($st) - 1] === '-') {
				$st = substr($st, 0, -1);
			}
		}
		
		return $st;
	}
}
