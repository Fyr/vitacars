<?
	$this->Html->css(array('bootstrap-multiselect'), array('inline' => false));
	$this->Html->script(array('vendor/bootstrap-multiselect' ), array('inline' => false));
	echo $this->PHForm->create('Filter');
	echo $this->element('admin_content');
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
	$('#FilterCategoryId').multiselect({
		nonSelectedText: '- все категории -',
		nSelectedText: 'выбрано'
	});
});
</script>