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
<label class="control-label"><b>Продукт</b></label>
<span class="descr-tabs">
<?
    echo $this->element('lang_tabs');
	foreach(Configure::read('domains') as $lang) {
?>
	<div id="descr-tab-content-<?=$lang?>" class="descr-tab-content">
		<fieldset>
			<legend>Шаблон SEO-данных (meta-тэги)</legend>
<?
		echo $this->PHForm->input('tpl_product_seo_title_'.$lang, array('label' => array('text' => 'Title', 'class' => 'control-label')));
		echo $this->PHForm->input('tpl_product_seo_keywords_'.$lang, array('label' => array('text' => 'Keywords', 'class' => 'control-label')));
		echo $this->PHForm->input('tpl_product_seo_descr_'.$lang, array('label' => array('text' => 'Description', 'class' => 'control-label')));
?>
		</fieldset>
		<b>Шаблон описания продукта</b><br/>
<?
		echo $this->element('Article.edit_body', array('field' => 'tpl_product_descr_'.$lang));
?>
	</div>
<?
	}
?>
</span>
