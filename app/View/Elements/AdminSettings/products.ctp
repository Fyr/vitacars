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
    <ul class="nav nav-tabs">
		<li id="tab-by" class="active"><a href="javascript:;">BY</a></li>
		<li id="tab-ru"><a href="javascript:;">RU</a></li>
		<li id="tab-ua"><a href="javascript:;">UA</a></li>
	</ul>
    <br/>
<?
	foreach(array('by', 'ru', 'ua') as $lang) {
		$tab = ($lang == 'by') ? '' : '_' . $lang;
?>
	<div id="descr-tab-content-<?=$lang?>" class="descr-tab-content">
		<fieldset>
			<legend>SEO-данные (meta-тэги)</legend>
<?
		echo $this->PHForm->input('tpl_product_seo_title_'.$lang, array('label' => array('text' => 'Title', 'class' => 'control-label')));
		echo $this->PHForm->input('tpl_product_seo_keywords_'.$lang, array('label' => array('text' => 'Keywords', 'class' => 'control-label')));
		echo $this->PHForm->input('tpl_product_seo_descr_'.$lang, array('label' => array('text' => 'Description', 'class' => 'control-label')));
?>
		</fieldset>
		<b>Описание продукта</b><br/>
<?
		echo $this->element('Article.edit_body', array('field' => 'tpl_product_descr'.$tab));
?>
	</div>
<?
	}
?>
</span>
<script>
function descr_activateTab(tab) {
	var context = $('.descr-tabs');
	$('ul.nav.nav-tabs > li', context).removeClass('active');
	$('ul.nav.nav-tabs > #tab-' + tab, context).addClass('active');
	$('.descr-tab-content', context).hide();
	$('#descr-tab-content-' + tab, context).show();
}
$(function(){
	descr_activateTab('by');
	$('.descr-tabs ul.nav.nav-tabs > li').click(function(){
		var tab = $(this).prop('id').replace(/tab-/, '');
		descr_activateTab(tab);
	});
});
</script>


