<?
    if (!isset($objectType)) {
        $objectType = 'ClientCompany';
    }
    if (!isset($readonly)) {
        $readonly = false;
    }
?>
<div style="text-align: center">Реквизиты компании</div>
<br/>
<?
    $id = $this->request->data($objectType.'.id');
    echo $this->PHForm->hidden($objectType.'.id', array('value' => $id));
	echo $this->PHForm->input($objectType.'.company_name', array('readonly' => $readonly));
	echo $this->PHForm->input($objectType.'.company_uuid', array('class' => 'input-medium', 'readonly' => $readonly));
	echo $this->PHForm->input($objectType.'.address', array('readonly' => $readonly));
?>
<div style="text-align: center; margin-top: 20px">Представитель компании</div>
<br/>
<?
	echo $this->PHForm->input($objectType.'.contact_person', array('readonly' => $readonly));
	echo $this->PHForm->input($objectType.'.contact_phone', array('class' => 'input-medium', 'readonly' => $readonly));
?>
