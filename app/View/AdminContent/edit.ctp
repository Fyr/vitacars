<div class="span8 offset2">
<?
    $id = $this->request->data('Article.id');
    $objectType = $this->request->data('Article.object_type');
    $objectID = $this->request->data('Article.object_id');
    
    $title = $this->ObjectType->getTitle(($id) ? 'edit' : 'create', $objectType);
    if ($objectType == 'Subcategory' && $objectID) {
		$title = $category['Category']['title'].': '.$title;
	}
?>
	<?=$this->element('admin_title', compact('title'))?>
<?
    echo $this->PHForm->create('Article');
    $aTabs = array(
        'General' => $this->element('/AdminContent/admin_edit_'.$objectType),
		'Text' => $this->element('Article.edit_body')
    );
    if ($objectType == 'Subcategory' && $objectID) {
    	// $aTabs['Tech-params'] = $this->element('/AdminContent/admin_edit_Params');
    }
    if (in_array($objectType, array('Category', 'Subcategory', 'Brand'))) {
    	$aTabs['SEO'] = $this->element('Seo.edit');
    }
    if ($id) {
        $aTabs['Media'] = $this->element('Media.edit', array('object_type' => $objectType, 'object_id' => $id));
    }
	echo $this->element('admin_tabs', compact('aTabs'));
	echo $this->element('Form.form_actions', array('backURL' => $this->ObjectType->getBaseURL($objectType, $objectID)));
	if ($objectType == 'Subcategory' && $objectID) {
		echo $this->PHForm->hidden('FormKey.field_id', array('value' => implode(',', $formKeys)));
	}
    echo $this->PHForm->end();
?>
</div>
<script type="text/javascript">
$(document).ready(function(){
	var $grid = $('#grid_FormField');
});
</script>