<?
header('Content-type: application/ms-excel');
//header('Content-Type: text/html; charset=utf8');
$filename = (isset($filename) && $filename) ? $filename : 'list.xls';
header('Content-Disposition: attachment; filename='.$filename);
?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=windows-1251">
<style type="text/css">
td {
    vertical-align: middle;
}
.align-right {
	text-align: right;
}
.even {
	background-color: #eee;
}
.odd {
}
img {
    display: block;
}
.even-changed {
	background-color: #eee;
	border: 1px solid #00f;
}
</style>
</head>
<body>
	<?=mb_convert_encoding($this->fetch('content'), "CP1251", "UTF-8")?>
</body>
</html>