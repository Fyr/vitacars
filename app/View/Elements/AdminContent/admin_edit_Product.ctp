<?
	echo $this->element('Article.edit_status');
	echo $this->PHForm->input('Product.cat_id', array(
		'label' => array('class' => 'control-label', 'text' => 'Category'), 
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
?>
					<option value="<?=$subcat['Subcategory']['id']?>"><?=$subcat['Subcategory']['title']?></option>
<?
	}
?>
				</optgroup>
			</select>
		</div>
	</div>
<?
	echo $this->PHForm->input('title');
	echo $this->PHForm->input('price', array('class' => 'input-small'));
	echo $this->PHForm->input('teaser');
	// echo $this->PHForm->input('Product.title');
	// echo $this->element('Article.edit_slug');
?>
<script type="text/javascript">
function category_onChange(e, subcat_id) {
	$('#ProductSubCatId optgroup').hide();
	var $optgroup = $('#ProductSubCatId optgroup#cat-' + $(e).val());
	$optgroup.show();
	$('#ProductSubCatId').val((subcat_id) ? subcat_id : $('option:first', $optgroup).attr('value'));
}
$(document).ready(function(){
	category_onChange($('#ProductCatId').get(0));
	$('#ProductSubCatId').val(<?=$this->request->data('Product.subcat_id')?>);
});
</script>