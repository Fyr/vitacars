<style type="text/css">
.form-horizontal .control-label {
	text-align: left;
	margin-left: 20px;
}
</style>
<b>Переменные шаблона</b> <br />
<?
$aVars = array(
	'Счет-Фактура' => array(
		'{$Order.created}' => 'Дата создания',
		'{$Order.nds}' => 'НДС, %',
	),
	'Контрагенты' => array(
		'{$Agent.title}' => 'Наименование',
		'{$Agent.full_title}' => 'Полное наименование',
		'{$Agent.address}' => 'Адрес',
		'{$Agent.phone}' => 'Тел.',
		'{$Agent.email}' => 'E-mail',
		'{$Agent.inn}' => 'ИНН',
		'{$Agent.kpp}' => 'КПП',
		'{$Agent.bik}' => 'БИК',
		'{$Agent.bank_name}' => 'Наименование банка',
		'{$Agent.bank_rs}' => 'Расчетный счет',
		'{$Agent.bank_address}' => 'Адрес банка'
	)
);
foreach($aVars as $name => $group) {
	echo $name.':<br/>';
	foreach($group as $key => $title) {
		echo "&nbsp;&nbsp;{$key} - {$title}<br/>";
	}
}
?>
<br />
<label class="control-label"><b>Заголовок</b></label>
<?=$this->PHForm->editor('sf_header', array('fullwidth' => true));?>
<label class="control-label"><b>Конец</b></label>
<?=$this->PHForm->editor('sf_footer', array('fullwidth' => true));?>
