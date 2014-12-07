<div class="span8 offset2">
<?
	$id = $this->request->data('PMFormField.id');
	$title = $this->ObjectType->getTitle(($id) ? 'edit' : 'create', 'PMFormField');
	echo $this->element('admin_title', compact('title'));
	echo $this->PHForm->create('PMFormField');
	echo $this->element('admin_content');
	echo $this->PHForm->input('field_type', array('options' => $aFieldTypes, 'onchange' => 'FieldType_onChange(this)'));
	echo $this->PHForm->input('label', array('class' => 'input-medium'));
	echo $this->PHForm->input('key', array('class' => 'input-medium'));
	// echo $this->PHForm->input('fieldset', array('class' => 'input-medium'));
	echo $this->PHForm->input('options');
?>
	<span class="formula">
<?
	echo $this->PHForm->input('formula', array('id' => 'PMFormFieldFormula'));
	echo $this->PHForm->input('decimals', array('class' => 'input-mini'));
	echo $this->PHForm->input('div_float', array('class' => 'input-mini'));
	echo $this->PHForm->input('div_int', array('class' => 'input-mini'));
?>
	</span>
<?
	echo $this->PHForm->input('sort_order');
	echo $this->PHForm->input('required');
	echo $this->PHForm->input('exported');
	echo $this->element('admin_content_end');
	echo $this->element('Form.form_actions', array('backURL' => $this->Html->url(array('action' => 'index'))));
	echo $this->PHForm->end();
?>
</div>
<script type="text/javascript">
function FieldType_onChange(e) {
	var $options = $('#PMFormFieldOptions').closest('.control-group');
	var $formula = $('.formula');
	$options.hide();
	$formula.hide();
	if ($(e).val() == <?=$PMFormField__SELECT?> || $(e).val() == <?=$PMFormField__MULTISELECT?>) {
		$options.show();
	} else if ($(e).val() == <?=$PMFormField__FORMULA?>) {
		$formula.show();
		if (!$('#PMFormFieldFormula').val()) {
			$('#PMFormFieldDecimals').val('0');
			$('#PMFormFieldDivFloat').val(',');
			$('#PMFormFieldDivInt').val(' ');
		}
	}
}
$(document).ready(function(){
	FieldType_onChange($('#PMFormFieldFieldType'));
});
</script>