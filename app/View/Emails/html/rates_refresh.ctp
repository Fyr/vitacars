<?
	if ($errMsg) {
		echo 'Ошибка обновления курсов: '.$errMsg.'<br />Рекомендуется обновить курсы вручную';
	} else {
?>

На сегодня установлены следующие курсы НБРБ:<br/>
<?
		foreach($setKurs as $curr => $rate) {
			echo $curr.': '.$rate.'<br/>';
		}
?>
<br />
Кросс-курсы:<br />
<?
		foreach($setCrossKurs as $key => $rate) {
			echo $key.': '.$rate.'<br/>';
		}
	}
?>
