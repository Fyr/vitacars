<div class="span8 offset2">
<?
    $id = $this->request->data('SiteOrder.id');
    $clientGroup = $this->request->data('Client.group_id');
    $title = $this->ObjectType->getTitle(($id) ? 'edit' : 'create', $objectType);
    echo $this->element('admin_title', compact('title'));
    echo $this->PHForm->create('SiteOrder');
	$aTabs = array(
		'General' => $this->element('AdminSiteOrders/admin_edit_General'),
	);
	if ($id) {
	    if ($clientGroup == Client::GROUP_COMPANY) {
	        $aTabs[Client::getOptions($clientGroup)] = $this->element('AdminClients/admin_edit_Company', array(
	            'objectType' => 'SiteOrderCompany', 'readonly' => true
            ));
        }
        $aTabs['Delivery'] = $this->element('AdminSiteOrders/admin_edit_Delivery');
        $aTabs['Products'] = $this->element('AdminSiteOrders/admin_edit_Products');

	}

	echo $this->element('admin_tabs', compact('aTabs'));
	echo $this->element('Form.form_actions', array('backURL' => $this->Html->url(array('action' => 'index'))));
    echo $this->PHForm->end();
?>
</div>
