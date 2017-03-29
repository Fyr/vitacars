<?
	$perc = ($total) ? round($progress / $total, 2) * 100 : 0;
	echo "{$progress} / {$total} ($perc%)";