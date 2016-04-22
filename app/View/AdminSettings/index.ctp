<?
	$this->Html->css(array('bootstrap-multiselect'), array('inline' => false));
	$this->Html->script(array('vendor/bootstrap-multiselect' ), array('inline' => false));
	echo $this->element('admin_title', array('title' => __('Settings')));
?>
<div class="span8 offset2">
<?
	echo $this->PHForm->create('Settings');
	echo $this->element('admin_content');
	echo $this->PHForm->input('admin_email', array('class' => 'input-large'));
	echo $this->PHForm->input('manager_emails');

	echo $this->PHForm->input('gpz_brands', array(
		'class' => 'multiselect', 'type' => 'select', 'multiple' => true,
		'options' => $aBrandOptions, 'value' => explode(',', $this->request->data('Settings.gpz_brands')),
		'label' => array('text' => 'Брэнды для поиска Gpz', 'class' => 'control-label')
	));
	echo $this->element('admin_content_end');
	echo $this->element('Form.btn_save');
	echo $this->PHForm->end();
?>
</div>

<script type="text/javascript">
	$(function(){
		$('#SettingsGpzBrands').multiselect({
			nonSelectedText: 'Выберите бренд',
			nSelectedText: 'выбрано'
		});
	});
</script>