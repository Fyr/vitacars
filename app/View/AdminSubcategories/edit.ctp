<div class="span8 offset2">
<?
    $id = $this->request->data('Subcategory.id');
    $cat_id = Hash::get($category, 'Category.id');
    $title = Hash::get($category, 'Category.title').': '.$this->ObjectType->getTitle(($id) ? 'edit' : 'create', $objectType);
    echo $this->element('admin_title', compact('title'));

    echo $this->PHForm->create($objectType);
    $aTabs = array(
        'General' => $this->element('../AdminSubcategories/_edit_general'),
		'Descr' => $this->element('edit_body'),
		'SEO' => $this->element('Seo.edit')
    );

    if ($id) {
        $aTabs['Media'] = $this->element('Media.edit', array('object_type' => $objectType, 'object_id' => $id));
    }
	echo $this->element('admin_tabs', compact('aTabs'));
	echo $this->element('Form.form_actions', array('backURL' => $this->Html->url(array('action' => 'index', $cat_id))));
    echo $this->PHForm->end();
?>
</div>
