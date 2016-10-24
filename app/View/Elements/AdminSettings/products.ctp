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
		'Продукт' => array(
			'{$Product.title}' => 'Наименование',
			'{$Product.title_rus}' => 'Наименование (рус.)',
			'{$Product.detail_num}' => 'Номера деталей',
			'{$Product.code}' => 'Код',
			'{$Product.brand}' => 'Название брэнда',
			'{$Product.category}' => 'Название категории',
			'{$Product.subcategory}' => 'Название подкатегории'
		),
		'Тех.параметры' => array(
			'{$PMFormData.&lt;fk_id&gt;}' => 'Значение тех.параметра (напр. $PMFormData.fk_6)',
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
<label class="control-label"><b>Описание продукта</b></label>
<?=$this->PHForm->editor('tpl_product_descr', array('fullwidth' => true));?>


