<?
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
					<option value="<?=$subcat['Subcategory']['id']?>".<?=$selected?>><?=$subcat['Subcategory']['title']?></option>
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
	echo $this->PHForm->input('teaser');
	echo $this->PHForm->input('code');
	echo $this->PHForm->input('count');
	echo $this->PHForm->input('status', array('label' => false, 'multiple' => 'checkbox', 'options' => array('published' => __('Published'), 'featured' => __('Featured'), 'active' => __('Active')), 'class' => 'checkbox inline'));
	
	$subcat_id = $this->request->data('Product.subcat_id');
?>
<script type="text/javascript">
function category_onChange(e, subcat_id) {
	$('#ProductSubCatId optgroup').hide();
	var $optgroup = $('#ProductSubCatId optgroup#cat-' + $(e).val());
	$optgroup.show();
	$('#ProductSubCatId').val((subcat_id) ? subcat_id : $('option:first', $optgroup).attr('value'));
}
$(document).ready(function(){
	category_onChange($('#ProductCatId').get(0), <?=($subcat_id) ? $subcat_id : '0'?>);
});
</script>