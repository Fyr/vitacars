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
		$subtask_id = (isset($options['task_id'])) ? $options['subtask_id'] : 0;

		$eol = "\n";
		$eol_len = mb_strlen($eol);

		if (!file_exists($file)) {
			throw new Exception(__('CSV Reader: File does not exists `%s`', $file));
		}

		$csv = mb_convert_encoding(trim(file_get_contents($file)), 'utf-8', 'cp1251');
		$csv = str_replace("\r\n", $eol, $csv); // прибиваем все переводы строк к одному виду
		$csv = str_replace('""', '\`', $csv); // заменяем кавычки внутри Excel-ячейка в escape-символ кавычек, чтоб не путались с $enclose

		// Получаем заголовки
		$startPos = mb_strpos($csv, $eol);
		$headers = mb_substr($csv, 0, $startPos);
		if (!$keys) {
			$keys = explode($csv_div, trim($headers));
		}

		$aCsv = array();
		$strlen = mb_strlen($csv);
		$startPos+= $eol_len;
		$col = 0; $line = 2;
		$row = array(); $lEnclose = false;

		if ($Task) {
			$Task->setProgress($subtask_id, 0, substr_count($csv, $eol));
			fdebug(substr_count($csv, $eol));
			$Task->setStatus($subtask_id, Task::RUN);
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
				$row[$key] = str_replace('\`', '"', $val);
				$startPos = $endPos + mb_strlen($csv_div);
			} elseif (!$lEnclose && ($ch === $eol || $endPos == $strlen)) { // конец строки или конец файла
				// добавить последнюю ячейку
				$key = $keys[$col];
				$val = mb_substr($csv, $startPos, $endPos - $startPos);
				$row[$key] = str_replace('\`', '"', $val);
				if (count($row) != count($keys)) {
					throw new Exception(__('CSV format error: Less values then headers (Line %s)', $line));
				}
				$aCsv[] = $row;
				$row = array();
				$col = 0;
				$startPos = $endPos + $eol_len;
			} elseif ($ch === $enclose) {
				$lEnclose = !$lEnclose;
				if ($lEnclose) {
					$startPos++;
				}
			}
			if ($ch === $eol) {
				if ($Task) {
					$Task->setProgress($subtask_id, $line - 1);

					$_progress = $Task->getProgressInfo($subtask_id);
					$progress = $Task->getProgressInfo($task_id);
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
			throw new Exception(__('CSV Reader: File does not exists `%s`', $file));
		}

		$eol = "\n";
		$csv = trim(file_get_contents($file));
		$csv = str_replace("\r\n", $eol, $csv); // прибиваем все переводы строк к одному виду

		// Получаем заголовки
		$startPos = mb_strpos($csv, $eol);
		$headers = mb_substr($csv, 0, $startPos);
		return explode($csv_div, trim(mb_substr($csv, 0, $startPos)));
	}

	/*
	static public function parse2($file, $keys = array(), $csv_div = ';') {
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
			$keys = explode($csv_div, trim($file[0]));
			unset($file[0]);
		}

		$aData = array();
		$line = 1;
		foreach($file as $row) {
			$line++;
			$_row = explode($csv_div, trim($row));
			if (count($keys) !== count($_row)) {
				throw new Exception(__('Incorrect file format (Line %s)', $line));
			}
			$aData[] = array_combine($keys, $_row);
		}

		return array('keys' => $keys, 'data' => $aData);
	}
	*/
}
?>