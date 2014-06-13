<?php
header('Content-type: application/ms-excel');
header('Content-Type: text/html; charset=utf8');
header('Content-Disposition: attachment; filename=list.xls');
echo $this->fetch('content');
