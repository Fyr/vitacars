<?=$this->element('admin_title', array('title' => __('Settings')))?>
<div class="span8 offset2">
<?
	echo $this->PHForm->create('Settings');
	echo $this->element('admin_content');
	echo $this->PHForm->input('admin_email', array('class' => 'input-large'));
	echo $this->PHForm->input('manager_emails');
	echo $this->PHForm->input('gpz_stop', array('type' => 'textarea'));
	echo $this->element('admin_content_end');
	echo $this->element('Form.btn_save');
	echo $this->PHForm->end();
?>
</div>