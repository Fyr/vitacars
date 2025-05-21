<div style="text-align: center">Реквизиты компании</div>
<br/>
<?
    $id = $this->request->data('ClientCompany.id');
    echo $this->PHForm->hidden('ClientCompany.id', array('value' => $id));
	echo $this->PHForm->input('ClientCompany.company_name');
	echo $this->PHForm->input('ClientCompany.company_uuid', array('class' => 'input-medium'));
	echo $this->PHForm->input('ClientCompany.address');
?>
<div style="text-align: center; margin-top: 20px">Представитель компании</div>
<br/>
<?
	echo $this->PHForm->input('ClientCompany.contact_person');
	echo $this->PHForm->input('ClientCompany.contact_phone', array('class' => 'input-medium'));
?>
