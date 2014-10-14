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
    $aTabs = array(
        'General' => $this->element('/AdminContent/admin_edit_'.$objectType),
		'Text' => $this->element('Article.edit_body')
    );
    
    if ($id) {
    	$aTabs['Tech-params'] = $this->PHFormFields->render($form, $formValues);
    	$aTabs['SEO'] = $this->element('Seo.edit');
        $aTabs['Media'] = $this->element('Media.edit', array('object_type' => $objectType, 'object_id' => $id));
    }
    
	echo $this->element('admin_tabs', compact('aTabs'));
	echo $this->element('Form.form_actions', array('backURL' => $this->Html->url(array('action' => 'index'))));
    echo $this->PHForm->end();
?>
</div>
<script type="text/javascript">
$(document).ready(function(){
	$('#PMFormValueValue_2').multiselect({
		nonSelectedText: 'Выберите мотор',
		nSelectedText: 'выбрано'
	});
});
</script>