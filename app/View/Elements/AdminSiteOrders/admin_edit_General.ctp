<?
    $readonly = true;
    $id = $this->request->data('SiteOrder.id');
    echo $this->PHForm->input('uuid', array(
        'class' => 'input-small',
        'label' => array('text' => __('%s order', '&numero;'), 'class' => 'control-label'),
        'value' => $this->Order->getUuid($this->request->data),
        'readonly' => $readonly
    ));
    echo $this->PHForm->input('SiteOrder.zone', array(
        'options' => array('by' => 'BY', 'ru' => 'RU'),
        'readonly' => $readonly
    ));
    echo $this->PHForm->input('Client.group_id', array(
        'label' => array('text' => __('Client Type'), 'class' => 'control-label'),
        'options' => Client::getOptions(),
        'readonly' => $readonly
    ));
    echo $this->PHForm->input('SiteOrder.username', array(
        'label' => array('text' => __('Contact Person'), 'class' => 'control-label'),
        'readonly' => $readonly
    ));
    echo $this->PHForm->input('SiteOrder.phone', array(
        'label' => array('text' => __('Contact Phone'), 'class' => 'control-label'),
        'readonly' => $readonly
    ));
	echo $this->PHForm->input('SiteOrder.email', array(
	    'readonly' => $readonly
	));
    echo $this->PHForm->input('SiteOrder.completed');
    /*
    if ($id) {
	    echo $this->PHForm->input('status', array(
            'label' => false,
            'multiple' => 'checkbox',
            'options' => array(
                'completed' => __('Completed'),
            ),
            'class' => 'checkbox inline'
        ));
    }
    */
?>
<script type="text/javascript">
</script>
