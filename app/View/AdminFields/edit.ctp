<div class="span8 offset2">
<?
    $id = $this->request->data('FormField.id');
    $title = $this->ObjectType->getTitle(($id) ? 'edit' : 'create', $objectType);
    echo $this->element('admin_title', compact('title'));
    echo $this->PHForm->create('FormField');
    echo $this->element('admin_content');
    echo $this->PHForm->input('field_type', array('options' => $aFieldTypes, 'onchange' => 'FieldType_onChange(this)'));
    echo $this->PHForm->input('label', array('class' => 'input-medium'));
    echo $this->PHForm->input('fieldset', array('class' => 'input-medium'));
    echo $this->PHForm->input('options');
    echo $this->PHForm->input('required');
    echo $this->element('admin_content_end');
	echo $this->element('Form.form_actions', array('backURL' => $this->Html->url(array('action' => 'index'))));
    echo $this->PHForm->end();
?>
</div>
<script type="text/javascript">
function FieldType_onChange(e) {
	var $options = $('#FormFieldOptions').closest('.control-group');
	$options.hide();
	if ($(e).val() == <?=$FormField__SELECT?>) {
		$options.show();
	}
}
$(document).ready(function(){
	FieldType_onChange($('#FormFieldFieldType').val());
});
</script>