<?
App::uses('AppHelper', 'View/Helper');
App::uses('TimeHelper', 'View/Helper');
// class PHTimeHelper extends AppHelper {
class PHTimeHelper extends TimeHelper {
	public function niceShort($dateString = null, $userOffset = null) {
		// по умолчанию - выводим полный формат, но без времени
		$date = strtotime($dateString);
		$day = date('j', $date);
		$month = ' '.__(date('M', $date), true);
		$year = ' '.date('Y', $date).'г.'; // (date('Y') == date('Y', $date)) 2014-09-29 20:54:38
		$time = '';
		if (date('m') == date('m', $date) && date('Y') == date('Y', $date)) {
			if (date('d') == date('d', $date)) {
				$day = __('Today', true);
				$month = '';
				$year = '';
				$time = ', ' . date('H:i', $date);
			} elseif ((date('d') - 1) == date('d', $date)) {
				$day = __('Yesterday', true);
				$month = '';
				$year = '';
				$time = ', ' . date('H:i', $date);
			}
		}
		return $day.$month.$year.$time;
	}

	public function niceShortTime($time) {
		$aDiv = array('days' => DAY, 'hours' => HOUR, 'mins' => MINUTE);
		$aParts = array('days' => 0, 'hours' => 0, 'mins' => 0, 'secs' => 0);
		foreach($aDiv as $label => $d) {
			$part = floor($time / $d);
			if ($part) {
				$time -= $part * $d;
				$aParts[$label] = $part;
			}
		}
		$aParts['secs'] = $time;

		$s = '';
		if ($aParts['days']) {
			$s = $aParts['days'].__('days').' ';
		}
		unset($aParts['days']);

		foreach($aParts as &$n) {
			$n = ($n < 10) ? '0'.$n : $n;
		}
		$s.= implode(':', array_values($aParts));
		return $s;
	}
}