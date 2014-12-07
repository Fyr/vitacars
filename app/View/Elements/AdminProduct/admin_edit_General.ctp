<?
	$this->Html->css(array('bootstrap-multiselect'), array('inline' => false));
	$this->Html->script(array('vendor/bootstrap-multiselect', '/Article/js/translit_utf', '/Article/js/edit_slug'), array('inline' => false));

	echo $this->PHForm->input('Product.cat_id', array(
		'label' => array('class' => 'control-label', 'text' => __('Category')), 
		'options' => $aCategories,
		'value' => $this->request->data('Product.cat_id'),
		'onchange' => 'category_onChange(this)'
	));
?>
	<div class="control-group">
		<label class="control-label" for="ProductSubCatId"><?=__('Subcategory')?></label>
		<div class="controls">
			<select id="ProductSubCatId" name="data[Product][subcat_id]" autocomplete="off">
				<optgroup id="cat-<?=Hash::get($aSubcategories[0], 'Category.id')?>" label="<?=Hash::get($aSubcategories[0], 'Category.title')?>">
<?
	$cat = Hash::get($aSubcategories[0], 'Category.id');
	foreach($aSubcategories as $subcat) {
		if ($cat != $subcat['Category']['id']) {
			$cat = $subcat['Category']['id'];
?>
				</optgroup>
				<optgroup id="cat-<?=$subcat['Category']['id']?>" label="<?=$subcat['Category']['title']?>">
<?			
		}
		$selected = ($this->request->data('Product.subcat_id') == $subcat['Subcategory']['id']) ? ' selected="selected"' : '';
?>
					<option value="<?=$subcat['Subcategory']['id']?>"<?=$selected?>><?=$subcat['Subcategory']['title']?></option>
<?
	}
?>
				</optgroup>
			</select>
		</div>
	</div>
<?
	echo $this->PHForm->input('brand_id', array('options' => $aBrandOptions));
	echo $this->PHForm->input('title');
	echo $this->PHForm->input('title_rus', array('label' => array('text' => __('Title rus'), 'class' => 'control-label')));
	echo $this->PHForm->input('detail_num', array('type' => 'text', 'label' => array('text' => __('Detail num'), 'class' => 'control-label')));
	/*
	$aMotorOptions = 'BF4M1012
BF6M1012
BF4M1013FC
BF6M1013FC
BF6M1015
BF8M1015
BF4M2012
BF6M2012
BF4M2013
BF6M2013
TCD2012
TCD2013
TCD2015
SERPIC
F2L1011
F3L1011
F2L2011
F3L2011
F2M1011
F3M1011
F2M2011
F3M2011
3L913
4L913
6L913
МТЗ';

	$aMotorOptions = $this->PHFormFields->getSelectOptions($aMotorOptions);
	$value = explode(',', $this->request->data('Product.motor'));
	$options = array(
		'multiple' => true, // 'class' => 'multiselect', 'type' => 'select', 
		'value' => $value,// array_combine($value, $value),
		'options' => array_combine($aMotorOptions, $aMotorOptions), 
		'label' => array('text' => __('Motor'), 'class' => 'control-label')
	);
	fdebug($options);
	echo $this->PHForm->input('selectmotor', $options);
	*/
	echo $this->PHForm->input('code');
	echo $this->PHForm->input('page_id', array(
		'type' => 'text',
		'label' => array('text' => __('Slug'), 'class' => 'control-label')
	));
	echo $this->PHForm->input('count');
	echo $this->PHForm->input('teaser');
	echo $this->PHForm->input('status', array('label' => false, 'multiple' => 'checkbox', 'options' => array('published' => __('Published'), 'featured' => __('Featured'), 'active' => __('On stock')), 'class' => 'checkbox inline'));
	
	$subcat_id = $this->request->data('Product.subcat_id');
?>
<script type="text/javascript">
function category_onChange(e, subcat_id) {
	$('#ProductSubCatId optgroup').hide();
	var $optgroup = $('#ProductSubCatId optgroup#cat-' + $(e).val());
	$optgroup.show();
	$('#ProductSubCatId').val((subcat_id) ? subcat_id : $('option:first', $optgroup).attr('value'));
}

function change_SeoTitle() {
	$('#SeoTitle').val($('#ProductTitleRus').val() + ' ' + $('#ProductCode').val());
}

function change_Slug() {
	$('#ProductPageId').val(translit($('#ProductTitleRus').val() + '-' + $('#ProductDetailNum').val()));
}

function change_SeoDescr() {
	$('#SeoKeywords').val($('#ProductTitle').val() + ' ' + $('#ProductDetailNum').val());
	$('#SeoDescr').val($('#ProductTitle').val() + ' ' + $('#ProductDetailNum').val());
}

$(document).ready(function(){
	category_onChange($('#ProductCatId').get(0), <?=($subcat_id) ? $subcat_id : '0'?>);
	$('#ProductSelectmotor').multiselect({
		nonSelectedText: 'Выберите мотор',
		nSelectedText: 'выбрано'
	});
	$('#PMFormDataFk6').multiselect({
		nonSelectedText: 'Выберите мотор',
		nSelectedText: 'выбрано'
	});
	$('#ProductTitleRus').change(function(){
		change_SeoTitle();
	});
	$('#ProductCode').change(function(){
		change_SeoTitle();
	});
	$('#ProductTitleRus, #ProductDetailNum').change(function(){
		change_Slug();
	});
	$('#ProductTitle, #ProductDetailNum').change(function(){
		change_SeoDescr();
	});
});
</script>