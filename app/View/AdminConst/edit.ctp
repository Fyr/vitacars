<div class="span8 offset2">
<?
	$id = $this->request->data('PMFormConst.id');
	$title = $this->ObjectType->getTitle(($id) ? 'edit' : 'create', 'FormConst');
	echo $this->element('admin_title', compact('title'));
	echo $this->PHForm->create('PMFormConst');
	echo $this->element('admin_content');
	echo $this->PHForm->input('field_type', array('options' => $aFieldTypes, 'onchange' => 'FieldType_onChange(this)'));
	echo $this->PHForm->input('label', array('class' => 'input-medium'));
	echo $this->PHForm->input('key', array('class' => 'input-medium'));
	echo $this->PHForm->input('value', array('class' => 'input-medium'));
	echo $this->PHForm->input('sort_order', array('class' => 'input-small'));
	echo $this->element('admin_content_end');
	echo $this->element('Form.form_actions', array('backURL' => $this->Html->url(array('action' => 'index'))));
	echo $this->PHForm->end();
?>
</div>
