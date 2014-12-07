<div class="span8 offset2">
<?
	$this->Html->css(array('bootstrap-multiselect'), array('inline' => false));
	$this->Html->script(array('vendor/bootstrap-multiselect'), array('inline' => false));
	
    $id = $this->request->data('Product.id');
    $title = $this->ObjectType->getTitle(($id) ? 'edit' : 'create', $objectType);
?>
	<?=$this->element('admin_title', compact('title'))?>
<?
    echo $this->PHForm->create('Product');
    echo $this->Form->hidden('PMFormData.id', array('value' => Hash::get($this->request->data, 'PMFormData.id')));
    echo $this->Form->hidden('Seo.id', array('value' => Hash::get($this->request->data, 'Seo.id')));
    $aTabs = array(
        'General' => $this->element('/AdminProduct/admin_edit_General'),
		'Text' => $this->element('Article.edit_body'),
		'SEO' => $this->element('Seo.edit'),
		'Tech-params' => $this->element('/AdminProduct/admin_edit_TechParams', compact('form', 'formValues'))
    );
    if ($id) {
    	// $aTabs['Tech-params'] = $this->element('/AdminProduct/admin_edit_TechParams', compact('form', 'formValues'));
        $aTabs['Media'] = $this->element('Media.edit', array('object_type' => $objectType, 'object_id' => $id));
    }
    
	echo $this->element('admin_tabs', compact('aTabs'));
	echo $this->element('Form.form_actions', array('backURL' => $this->Html->url(array('action' => 'index'))));
    echo $this->PHForm->end();
?>
</div>
