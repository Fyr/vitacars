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
					<option value="0">- <?=__('No subcategory')?> -</option>
<?
	$cat = Hash::get($aSubcategories[0], 'Category.id');
	foreach($aSubcategories as $subcat) {
		if ($cat != $subcat['Category']['id']) {
			$cat = $subcat['Category']['id'];
?>
				</optgroup>
				<optgroup id="cat-<?=$subcat['Category']['id']?>" label="<?=$subcat['Category']['title']?>">
					<option value="0">- <?=__('No subcategory')?> -</option>
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
	echo $this->PHForm->input('code');
	echo $this->PHForm->input('slug', array(
		'type' => 'text',
		'label' => array('text' => __('Slug'), 'class' => 'control-label')
	));
	echo $this->PHForm->input('count');
	echo $this->PHForm->input('status', array(
		'label' => false,
		'multiple' => 'checkbox',
		'options' => array(
			'published' => __('Published'),
			'featured' => __('Featured'),
			'active' => __('On stock'),
			'show_detailnum' => __('Show detail number')
		),
		'class' => 'checkbox inline'
	));

	$subcat_id = $this->request->data('Product.subcat_id');
	// echo $this->Form->hidden('Product.motor');
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
	$('#ProductSlug').val(translit($('#ProductTitleRus').val() + '-' + $('#ProductCode').val()));
}

function change_SeoDescr() {
	$('#SeoKeywords').val($('#ProductTitle').val() + ' ' + $('#ProductDetailNum').val());
	$('#SeoDescr').val($('#ProductTitle').val() + ' ' + $('#ProductDetailNum').val());
}

$(document).ready(function(){
	category_onChange($('#ProductCatId').get(0), <?=($subcat_id) ? $subcat_id : '0'?>);

	$('#ProductTitleRus').change(function(){
		change_SeoTitle();
	});
	$('#ProductCode').change(function(){
		change_SeoTitle();
	});
	$('#ProductTitleRus, #ProductCode').change(function(){
		change_Slug();
	});
	$('#ProductTitleRus, #ProductCode').keyup(function(){
		change_Slug();
	});
	$('#ProductTitle, #ProductDetailNum').change(function(){
		change_SeoDescr();
	});

	$('#PMFormDataFk6').closest('.controls').addClass('multiMotors fourColomn');
	$('.multiselect').multiselect({
		nonSelectedText: 'Выберите опции',
		numberDisplayed: 2,
		nSelectedText: 'выбрано',
		onChange: function(option, checked) {
			var select = $(option).parent().get(0);
			$('#' + select.id + '_').val($('#' + select.id).val().join(','));
		}
	});
	$('select.multiselect').each(function(){
		$('#' + this.id + '_').val($(this).val());
	});
	$('#ProductEditForm').submit(function(){
		$('select.multiselect').remove();
		return true;
	});
});
</script>
