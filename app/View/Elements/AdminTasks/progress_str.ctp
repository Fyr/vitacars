<?
	$perc = ($total) ? round($progress / $total, 2) * 100 : 0;
	$_progress = floor($progress);
	echo "{$_progress} / {$total} ($perc%)";