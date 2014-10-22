<?
class Translit {
	
	static function convert($st, $lUrlMode = false) {
		// Ñíà÷àëà çàìåíÿåì "îäíîñèìâîëüíûå" ôîíåìû.
		$st = mb_convert_encoding($st, 'cp1251', 'utf8');
		$st = strtr($st, "àáâãäå¸çèéêëìíîïğñòóôõûı", "abvgdeeziyklmnoprstufhye");
		$st = strtr($st, "ÀÁÂÃÄÅ¨ÇÈÉÊËÌÍÎÏĞÑÒÓÔÕÛİ", "ABVGDEEZIYKLMNOPRSTUFHye");
		
		// Çàòåì - "ìíîãîñèìâîëüíûå".
		$st = strtr($st, array(
			"æ"=>"zh", "ö"=>"ts", "÷"=>"ch", "ø"=>"sh", "ù"=>"shch", "ü"=>"j", "ú"=>"j", "ş"=>"yu", "ÿ"=>"ya",
			"Æ"=>"ZH", "Ö"=>"TS", "×"=>"CH", "Ø"=>"SH", "Ù"=>"SHCH", "Ü"=>"J", "ú"=>"J", "Ş"=>"YU", "ß"=>"YA",
			"¿"=>"i", "¯"=>"Yi", "º"=>"ie", "ª"=>"Ye"
		));
		
		if ($lUrlMode) {
			$st = strtolower(strtr($st, array(
				"'" => "", '"' => '', ' ' => '-', '.' => '-', ',' => '-', '/' => '-'
			)));
		}
		
		return $st;
	}
}
