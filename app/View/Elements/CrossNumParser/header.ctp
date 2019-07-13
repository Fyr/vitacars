<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=windows-1251">
    <style type="text/css">
        th {
            text-align: center;
            vertical-align: middle;
        }

        td {
            text-align: left;
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
<table cellpadding="0" cellspacing="0" border="0">
    <thead>
    <?
    foreach ($headers as $title) {
        ?>
        <th><?= $title ?></th>
        <?
    }
    ?>
    </thead>
    <tbody>
