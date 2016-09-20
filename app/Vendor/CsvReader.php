<?php
class CsvReader {

	const CSV_DIV = ';';

	/**
	 * Получить данные из CSV файла в виде ассоц.массива
	 *
	 * @param str $file - путь+имя файла
	 * @encoding str - исх.кодировка если нужно перекодирование в utf8
	 * @return array
	 */
	static public function parse($file, $keys = array()) {
		if (!file_exists($file)) {
			throw new Exception(__('Invalid file path'));
		}
		$file = mb_convert_encoding(trim(file_get_contents($file)), 'utf-8', 'cp1251');
		$file = str_replace("\r\n", "\n", $file);
		$file = str_replace(array('   ', '  '), ' ', $file);
		$file = explode("\n", $file);
		if (!($file && is_array($file) && count($file) > 1)) {
			throw new Exception('Incorrect file content');
		}

		if (!$keys) {
			$keys = explode(self::CSV_DIV, trim($file[0]));
			unset($file[0]);
		}

		$aData = array();
		$line = 1;
		foreach($file as $row) {
			$line++;
			$_row = explode(self::CSV_DIV, trim($row));
			if (count($keys) !== count($_row)) {
				throw new Exception(__('Incorrect file format (Line %s)', $line));
			}
			$aData[] = array_combine($keys, $_row);
		}

		return array('keys' => $keys, 'data' => $aData);
	}
}
?>