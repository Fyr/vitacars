<?
class Translit {
	
	static function convert($st, $lUrlMode = false) {
		// ������� �������� "��������������" ������.
		$st = mb_convert_encoding($st, 'cp1251', 'utf8');
		$st = strtr($st, "�����������������������", "abvgdeeziyklmnoprstufhye");
		$st = strtr($st, "�����Ũ�����������������", "ABVGDEEZIYKLMNOPRSTUFHYE");
		
		// ����� - "���������������".
		$st = strtr($st, array(
			"�"=>"zh", "�"=>"c", "�"=>"ch", "�"=>"sh", "�"=>"shch", "�"=>"", "�"=>"", "�"=>"ju", "�"=>"ja",
			"�"=>"ZH", "�"=>"C", "�"=>"CH", "�"=>"SH", "�"=>"SHCH", "�"=>"", "�"=>"", "�"=>"JU", "�"=>"JA",
			"�"=>"i", "�"=>"Yi", "�"=>"ie", "�"=>"Ye"
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
