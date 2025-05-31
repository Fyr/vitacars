<div class="span8 offset2">
<?
    $id = $this->request->data('Client.id');
    $clientGroup = $this->request->data('Client.group_id');
    $title = $this->ObjectType->getTitle(($id) ? 'edit' : 'create', $objectType);
    echo $this->element('admin_title', compact('title'));
    echo $this->PHForm->create('Client');
	$aTabs = array(
		'General' => $this->element('AdminClients/admin_edit_General'),
	);
	if ($id) {
	    $aTabs[Client::getOptions($clientGroup)] = ($clientGroup == Client::GROUP_COMPANY)
	        ? $this->element('AdminClients/admin_edit_Company', array('objectType' => 'ClientCompany'))
	        : $this->element('AdminClients/admin_edit_User');
        $aTabs[__('Delivery Address')] = $this->PHForm->input('delivery_address');
	}

	echo $this->element('admin_tabs', compact('aTabs'));
	echo $this->element('Form.form_actions', array('backURL' => $this->Html->url(array('action' => 'index'))));
    echo $this->PHForm->end();
?>
</div>
