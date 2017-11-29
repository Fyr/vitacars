<?php
class CsvReader {

	/**
	 * Получить данные из CSV файла в виде ассоц.массива
	 *
	 * @param str $file - путь+имя файла
	 * @encoding str - исх.кодировка если нужно перекодирование в utf8
	 * @return array
	 */
	static public function parse($file, $options = array()) {
		$keys = (isset($options['keys'])) ? $options['keys'] : array();
		$csv_div = (isset($options['csv_div'])) ? $options['csv_div'] : ';';
		$enclose = (isset($options['enclose'])) ? $options['enclose'] : '"';
		$Task = (isset($options['Task'])) ? $options['Task'] : null;
		$task_id = (isset($options['task_id'])) ? $options['task_id'] : 0;
		$subtask_id = (isset($options['subtask_id'])) ? $options['subtask_id'] : 0;

		$eol = "\n";
		$eol_len = mb_strlen($eol);

		$csv = mb_convert_encoding(trim(file_get_contents($file)), 'utf-8', 'cp1251');
		$csv = str_replace("\r\n", $eol, $csv); // прибиваем все переводы строк к одному виду
		$csv = str_replace('""', '\`', $csv); // заменяем кавычки внутри Excel-ячейка в escape-символ кавычек, чтоб не путались с $enclose

		// Получаем заголовки
		if ($keys) {
			$startPos = 0;
		} else {
			$keys = self::getHeaders($file);
			$startPos = mb_strpos($csv, $eol) + $eol_len;
		}

		$aCsv = array();
		$strlen = mb_strlen($csv);
		$col = 0; $line = 2;
		$j = 0; $t = microtime();
		$row = array(); $lEnclose = false;

		if ($Task) {
			$totalLines = substr_count($csv, $eol);
			$Task->setProgress($subtask_id, 0, $totalLines);
			$Task->setStatus($subtask_id, Task::RUN);
			$progress = $Task->getProgressInfo($task_id);
		}

		for($endPos = $startPos; $endPos <= $strlen; $endPos++) {
			if ($Task) {
				$status = $Task->getStatus($task_id);
				if ($status == Task::ABORT) {
					$Task->setStatus($subtask_id, Task::ABORTED);
					throw new Exception(__('Processing was aborted by user'));
				}
			}

			$ch = mb_substr($csv, $endPos, 1);
			if (!$lEnclose && $ch === $csv_div) {
				$key = $keys[$col];
				$col++;
				if ($col >= count($keys)) {
					throw new Exception(__('CSV format error: Too much values (Line %s)', $line));
				}
				$lastCh = mb_substr($csv, $endPos - 1, 1);
				$val = mb_substr($csv, $startPos, $endPos - $startPos + (($lastCh === $enclose) ? -1 : 0));
				$row[$key] = self::postProcess($val);
				$startPos = $endPos + mb_strlen($csv_div);
			} elseif (!$lEnclose && ($ch === $eol || $endPos == $strlen)) { // конец строки или конец файла
				// добавить последнюю ячейку
				$key = $keys[$col];
				$val = mb_substr($csv, $startPos, $endPos - $startPos);
				$row[$key] = self::postProcess($val);
				if (count($row) != count($keys)) {
					throw new Exception(__('CSV format error: Less values then headers (Line %s)', $line));
				}
				$aCsv[] = $row;
				$row = array();
				$col = 0;
				// $startPos = $endPos + $eol_len;
				$csv = mb_substr($csv, $endPos + $eol_len);
				$startPos = $endPos = 0;
				$strlen = mb_strlen($csv);
			} elseif ($ch === $enclose) {
				$lEnclose = !$lEnclose;
				if ($lEnclose) {
					$startPos++;
				}
			}
			if ($ch === $eol) {
				if ($Task) {
					$Task->setProgress($subtask_id, $line);

					$_progress = $Task->getProgressInfo($subtask_id);
					$Task->setProgress($task_id, $progress['progress'] + $_progress['percent'] * 0.01);
				}
				$line++;
			}
		}

		if ($Task) {
			$Task->setStatus($subtask_id, Task::DONE);
		}
		return array('keys' => $keys, 'data' => $aCsv);
	}

	static function getHeaders($file, $csv_div = ';') {
		if (!file_exists($file)) {
			throw new Exception(__('File does not exists `%s`', $file));
		}

		$eol = "\n";
		$csv = trim(file_get_contents($file));
		$csv = str_replace("\r\n", $eol, $csv); // прибиваем все переводы строк к одному виду

		// Получаем заголовки
		$startPos = mb_strpos($csv, $eol);
		return explode($csv_div, trim(mb_substr($csv, 0, $startPos)));
	}

	static function postProcess($val)
	{
		$val = str_replace('\`', '"', $val);

		// check float with ',' instead of '.'
		if (preg_match('/^\d+\,\d+$/', $val) === 1) {
			$val = floatval(str_replace(',', '.', $val));
		}
		return $val;
	}
}
?>