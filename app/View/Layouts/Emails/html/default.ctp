<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style type="text/css">
body, form, table, td, th, p, div, span, b, br {
	font-size: 13px;
	font-family: Tahoma, Arial, Helvetica, sans-serif;
	color: #515862; 
}
img {
	border: none;
}
td, th { padding: 3px 5px }
.align-right {
	text-align: right;
}
.even {
	background-color: #eee;
}
.odd {
}
</style>
</head>
<body>
<?php echo $this->fetch('content'); ?>
<p>
	С уважением, <br>
	Админицстрация <?=DOMAIN_TITLE?>
</p>
</body>
</html>