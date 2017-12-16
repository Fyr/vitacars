<div class="span8 offset2">
<?
	$id = $this->request->data('PMFormField.id');
	$title = $this->ObjectType->getTitle(($id) ? 'edit' : 'create', 'FormField');
	echo $this->element('admin_title', compact('title'));
	echo $this->PHForm->create('PMFormField');
	echo $this->element('admin_content');
if ($id) {
	echo $this->PHForm->hidden('field_type');
	echo $this->PHForm->input('_field_type', array(
		'options' => $aFieldTypes,
		'onchange' => 'FieldType_onChange()',
		'value' => $this->request->data('PMFormField.field_type'),
		'disabled' => true
	));
} else {
	echo $this->PHForm->input('field_type', array(
		'options' => $aFieldTypes,
		'onchange' => 'FieldType_onChange()',
		'disabled' => true
	));
}
	echo $this->PHForm->input('label', array('class' => 'input-medium'));
	echo $this->PHForm->input('label_bg', array('class' => 'input-medium', 'label' => array(
		'class' => 'control-label', 'text' => 'Метка для .bg'
	)));
	echo $this->PHForm->input('key', array('class' => 'input-medium'));
?>
	<span class="options options-<?= FieldTypes::SELECT ?> options-<?= FieldTypes::MULTISELECT ?>">
		<fieldset>
			<legend>Настройки</legend>
			<?
			echo $this->PHForm->input('options', array('label' => array('text' => 'Список опций построчно', 'class' => 'control-label')));
			?>
		</fieldset>
	</span>
	<span class="options options-<?= FieldTypes::FORMULA ?>">
		<fieldset>
			<legend>Настройки</legend>

			<?
			echo $this->PHForm->input('formula');
	echo $this->PHForm->input('decimals', array('class' => 'input-mini'));
	echo $this->PHForm->input('div_float', array('class' => 'input-mini'));
	echo $this->PHForm->input('div_int', array('class' => 'input-mini'));
?>
		</fieldset>
	</span>
	<span class="options options-<?= FieldTypes::PRICE ?>">
		<fieldset>
			<legend>Настройки</legend>
			<?
			echo $this->PHForm->input('price_prefix', array(
				'class' => 'input-small',
				'label' => array('text' => 'Префикс цены', 'class' => 'control-label'),
				'after' => '<span class="small-text"><br/>($P для ₽, $E для &euro;)<br/></span></div>'
			));
			echo $this->PHForm->input('price_decimals', array(
				'class' => 'input-mini',
				'label' => array('text' => __('Decimals'), 'class' => 'control-label')
			));
			echo $this->PHForm->input('price_div_float', array(
				'class' => 'input-mini',
				'label' => array('text' => __('Div Float'), 'class' => 'control-label')
			));
			echo $this->PHForm->input('price_div_int', array(
				'class' => 'input-mini',
				'label' => array('text' => __('Div Int'), 'class' => 'control-label')
			));
			echo $this->PHForm->input('price_postfix', array(
				'class' => 'input-small',
				'label' => array('text' => 'Постфикс цены', 'class' => 'control-label'),
				'after' => '<span class="small-text"><br/>($P для ₽, $E для &euro;)<br/></span></div>'
			));
			echo $this->PHForm->input('price_currency', array(
				'options' => $aCurrency,
				'class' => 'input-medium',
				'escape' => false,
				'label' => array('text' => 'Осн.валюта', 'class' => 'control-label')
			));
			echo $this->PHForm->input('price_formula', array(
				'label' => array('text' => 'Формула', 'class' => 'control-label'),
				'after' => '<br/><span class="small-text">Напр. $Ключ1 * $Ключ2</span></div>'
			));
			?>
		</fieldset>
	</span>
<?
	echo $this->PHForm->input('sort_order', array('class' => 'input-mini'));
// echo $this->PHForm->input('required');
	echo $this->PHForm->input('exported');
	echo $this->PHForm->input('is_price');
	echo $this->element('admin_content_end');
	echo $this->element('Form.form_actions', array('backURL' => $this->Html->url(array('action' => 'index'))));
	echo $this->PHForm->end();
?>
</div>
<script type="text/javascript">
	function FieldType_onChange() {
		var $e = $('#PMFormFieldFieldType');
		$('.options').hide();
		$('.options-' + $e.val()).show();
}
$(document).ready(function(){
	FieldType_onChange();
});
</script>