<style type="text/css">
	.form-horizontal .control-label {
		text-align: left;
		margin-left: 20px;
	}
</style>
<div style="padding: 20px; ">
	<b>Переменные шаблона</b><br/>
	<br/>
<?
	$aVars = array(
		'Счет-Фактура' => array(
			'{$Order.created}' => 'Дата создания',
			'{$Order.nds}' => 'НДС, %',
		),
		'Контрагенты' => array(
			'{$Agent.&lt;поле&gt;}' => 'Поставщик',
			'{$Agent2.&lt;поле&gt;}' => 'Получатель',
		),
		'Поля контрагента' => array(
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
		),
		'Итоги' => array(
			'{$Itogo.items}' => 'Кол-во позиций',
			'{$Itogo.sum}' => 'Общая сумма',
			'{$Itogo.nds}' => 'Сумма НДС (от общей)',
			'{$Itogo.k_oplate}' => 'Общая сумма с НДС',
			'{$Itogo.k_oplate_propis}' => 'Общая сумма с НДС прописью',
		),
	);
	foreach($aVars as $name => $group) {
		echo $name.':<br/>';
		foreach($group as $key => $title) {
			echo "&nbsp;&nbsp;&nbsp;&nbsp;{$key} - {$title}<br/>";
		}
	}
?>
</div>
<label class="control-label"><b>Заголовок</b></label>
<?=$this->PHForm->editor('sf_header', array('fullwidth' => true));?>
<label class="control-label"><b>Конец</b></label>
<?=$this->PHForm->editor('sf_footer', array('fullwidth' => true));?>

