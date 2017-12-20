<div class="span8 offset2">
<?
	$this->Html->css(array('bootstrap-multiselect'), array('inline' => false));
	$this->Html->script(array('vendor/bootstrap-multiselect'), array('inline' => false));
	
    $id = $this->request->data('Product.id');
    $title = $this->ObjectType->getTitle(($id) ? 'edit' : 'create', $objectType);

echo $this->element('admin_title', compact('title'));

	if ($this->request->data('Product.motor')) {
		$this->request->data('PMFormData.fk_6', $this->request->data('Product.motor'));
	}
	
    echo $this->PHForm->create('Product');
    echo $this->Form->hidden('PMFormData.id', array('value' => Hash::get($this->request->data, 'PMFormData.id')));
    echo $this->Form->hidden('Seo.id', array('value' => Hash::get($this->request->data, 'Seo.id')));

$prices = array();
foreach ($form as $i => $field) {
    if ($field['PMFormField']['field_type'] == FieldTypes::PRICE) {
        $prices[] = $field;
        unset($form[$i]);
    }
    }

    $aTabs = array(
        'General' => $this->element('/AdminProduct/admin_edit_General'),
        'Descr' => $this->element('edit_body'),
		'SEO' => $this->element('/AdminProduct/admin_edit_Seo'),
        'Tech-params' => $this->element('/AdminProduct/admin_edit_TechParams', compact('form', 'formValues')),
    );

if ($id) {
    $aTabs['Prices'] = $this->element('/AdminProduct/admin_edit_Prices', array('form' => $prices));
        $aTabs['Media'] = $this->element('Media.edit', array('object_type' => $objectType, 'object_id' => $id));
    }
    
	echo $this->element('admin_tabs', compact('aTabs'));
	echo $this->element('Form.form_actions', array('backURL' => $this->Html->url(array('action' => 'index'))));
    echo $this->PHForm->end();
?>
</div>
