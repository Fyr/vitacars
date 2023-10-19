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
			.$this->PHForm->input('show_fake', array('label' => array('class' => 'control-label', 'text' => __('Show fake products')))),
		'Tpl_Orders' => $this->element('AdminSettings/orders'),
		'Tpl_ProductDescr' => $this->element('AdminSettings/products'),
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