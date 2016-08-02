<?
class Assert {
	static public function equal($msg, $result, $sample) {
		if (is_array($result)) {
			$result = self::preprocessArray($result);
		}
		if (is_array($sample)) {
			$sample = self::preprocessArray($sample);
		}
		if ($sample === $result) {
			$msg = $msg.' - OK%s';
		} else {
			if (is_array($sample) || is_string($sample)) {
				$_result = var_export($result, true);
				$_sample = var_export($sample, true);
				list($result, $sample) = self::cmpAsStr($_result, $_sample);
			} else {
				$result = var_export($result, true);
				$sample = var_export($sample, true);
			}
			$msg = "<pre>{$msg} - ERROR!%sResult: `{$result}`%sMust be: `{$sample}`%s</pre>";
		}

		echo sprintf(str_replace('%s', '<br />', $msg));
	}

	static function preprocessArray($aSample) {
		foreach($aSample as $key => $item) {
			if (is_numeric($key)) {
				unset($aSample[$key]);
				$key += 0;
				$aSample[$key] = $item;
			}

			if (is_numeric($item)) {
				$item += 0;
				$aSample[$key] = $item;
			} elseif (is_array($item)) {
				$item = self::preprocessArray($item);
				$aSample[$key] = $item;
			}
		}
		return $aSample;
	}

	static public function cmpAsStr($_result, $_sample) {
		$status = -1;
		$aSample = array();
		$aResult = array();
		$pos = -1;

		for($i = 0; $i < max(strlen($_result), strlen($_sample)); $i++) {
			$new_status = self::getStrStatus($_sample, $_result, $i);
			if ($status <> $new_status) {
				$pos++;
				$status = $new_status;
				$aSample[$pos] = array('status' => $status, 'chunk' => '');
				$aResult[$pos] = array('status' => $status, 'chunk' => '');
			}
			if (isset($_sample[$i])) {
				$aSample[$pos]['chunk'].= $_sample[$i];
			}
			if (isset($_result[$i])) {
				$aResult[$pos]['chunk'].= $_result[$i];
			}
		}
		return array(self::getCmpStr($aResult), self::getCmpStr($aSample));
	}

	static private function getStrStatus($_sample, $_result, $i) {
		if (isset($_sample[$i]) && isset($_result[$i])) {
			if ($_sample[$i] !== $_result[$i]) {
				return 2;
			}
		} else {
			return 1;
		}
		return 0;
	}

	static private function getCmpStr($aStr) {
		$html = '';
		foreach($aStr as $item) {
			$style = '';
			if ($item['status'] == 2) {
				$style = 'font-weight: bold; color: #f00;';
			} elseif ($item['status'] == 1) {
				$style = 'font-weight: bold; color: #aaa';
			}
			$html.= '<span style="'.$style.'">'.$item['chunk'].'</span>';
		}
		return $html;
	}
}
