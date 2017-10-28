<?
	$this->Html->css(array('bootstrap-multiselect'), array('inline' => false));
	$this->Html->script(array('vendor/bootstrap-multiselect' ), array('inline' => false));
	echo $this->PHForm->create('Filter');
	echo $this->element('admin_content');
	echo $this->PHForm->input('zone', array(
		'options' => array('by' => 'Agromotors.BY', 'ru' => 'Agromotors.RU', 'ua' => 'DeutzUa.com.UA'),
		'label' => array('text' => 'Сайт', 'class' => 'control-label')
	));
	echo $this->PHForm->input('update', array(
		'options' => array('0' => 'все продукты', '1' => 'без описания', '2' => 'только с описанием'),
		'label' => array('text' => 'Обновление', 'class' => 'control-label')
	));
	echo $this->PHForm->input('data_type', array(
		'options' => array('descr' => 'Описание', 'seo' => 'SEO'),
		'multiple' => true,
		'label' => array('text' => 'Данные', 'class' => 'control-label')
	));
	echo $this->PHForm->input('brand_id', array(
		'options' => $aBrandOptions,
		'multiple' => true,
		'label' => array('text' => 'Брэнд', 'class' => 'control-label')
	));
	echo $this->PHForm->input('category_id', array(
		'options' => $aCategoryOptions,
		'multiple' => true,
		'label' => array('text' => 'Категория', 'class' => 'control-label')
	));
	echo $this->element('admin_content_end');
	echo $this->PHForm->submit(__('Apply').' <i class="icon-white icon-chevron-right"></i>', array('class' => 'btn btn-success pull-right', 'name' => 'apply', 'value' => 'apply'));
	echo $this->PHForm->end();
?>
<br/><br/>
<script type="text/javascript">
$(function(){
	$('#FilterBrandId').multiselect({
		nonSelectedText: '- все брэнды -',
		nSelectedText: 'выбрано'
	});
	$('#FilterCategoryId').multiselect({
		nonSelectedText: '- все категории -',
		nSelectedText: 'выбрано'
	});
	$('#FilterDataType').multiselect({
		nonSelectedText: '- все данные -',
		nSelectedText: 'выбрано'
	});
});
</script>