<div class="span8 offset2">
<?
    $id = $this->request->data('Agent.id');
    $title = $this->ObjectType->getTitle(($id) ? 'edit' : 'create', $objectType);
    echo $this->element('admin_title', compact('title'));
    echo $this->PHForm->create('Agent');
	$aTabs = array(
		'General' => $this->element('AdminAgents/admin_edit_General'),
		'Bank' => $this->element('AdminAgents/admin_edit_Bank'),
		'Contacts' => $this->element('AdminAgents/admin_edit_Contacts'),
	);
	echo $this->element('admin_tabs', compact('aTabs'));
	echo $this->element('Form.form_actions', array('backURL' => $this->Html->url(array('action' => 'index'))));
    echo $this->PHForm->end();
?>
</div>
