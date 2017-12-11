<div class="span8 offset2">
<?
	$id = $this->request->data('PMFormConst.id');
	$title = $this->ObjectType->getTitle(($id) ? 'edit' : 'create', 'FormConst');
	echo $this->element('admin_title', compact('title'));
	echo $this->PHForm->create('PMFormConst');

$select_kurs = array(
	$this->PHForm->input('price_kurs_from', array('class' => 'input-small', 'options' => $aCurrency, 'div' => false, 'between' => false, 'after' => false, 'label' => false)),
	$this->PHForm->input('price_kurs_to', array('class' => 'input-small', 'options' => $aCurrency, 'div' => false, 'between' => false, 'after' => false, 'label' => false))
);

	echo $this->element('admin_content');
	echo $this->PHForm->input('field_type', array('options' => $aFieldTypes, 'onchange' => 'FieldType_onChange(this)'));
	echo $this->PHForm->input('label', array('class' => 'input-medium'));
	echo $this->PHForm->input('key', array('class' => 'input-medium'));
	echo $this->PHForm->input('value', array('class' => 'input-medium'));
	echo $this->PHForm->input('sort_order', array('class' => 'input-small'));
echo $this->PHForm->input('is_price_kurs', array(
	'label' => array('text' => 'Курс для пересчета цен', 'class' => 'control-label'),
	'after' => '&nbsp;&nbsp;' . $this->Html->tag('span', implode('&nbsp;<i class="icon-chevron-right"></i>&nbsp;', $select_kurs), array('class' => 'select-kurs')),
	'onchange' => 'changeKurs()'
));
	echo $this->element('admin_content_end');
	echo $this->element('Form.form_actions', array('backURL' => $this->Html->url(array('action' => 'index'))));
	echo $this->PHForm->end();
?>
</div>
<script>
	function changeKurs() {
		var lChecked = $('#PMFormConstIsPriceKurs').is(':checked');
		if (lChecked) {
			$('.select-kurs').show();
		} else {
			$('.select-kurs').hide();
		}
	}
	$(function () {
		changeKurs();
	});
</script>