<?
	$this->Html->css(array('bootstrap-multiselect'), array('inline' => false));
	$this->Html->script(array('vendor/bootstrap-multiselect' ), array('inline' => false));
	echo $this->element('admin_title', array('title' => __('Settings')));
?>
<div class="span8 offset2">
<?
	echo $this->PHForm->create('Settings');
	// echo $this->element('admin_content');
	$aTabs = array(
		'General' => $this->PHForm->input('admin_email', array('class' => 'input-large'))
			.$this->PHForm->input('manager_emails')
			.$this->PHForm->input('gpz_brands', array(
				'class' => 'multiselect', 'type' => 'select', 'multiple' => true,
				'options' => $aBrandOptions, 'value' => explode(',', $this->request->data('Settings.gpz_brands')),
				'label' => array('text' => 'Брэнды для поиска Gpz', 'class' => 'control-label')
			)),
		'Шаблон СФ' => $this->element('AdminSettings/orders')
	);

	echo $this->element('admin_tabs', compact('aTabs'));
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