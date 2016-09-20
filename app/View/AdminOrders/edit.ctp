<div class="span8 offset2">
<?
    $id = $this->request->data('Order.id');
    $title = $this->ObjectType->getTitle(($id) ? 'edit' : 'create', 'Order');
    echo $this->element('admin_title', compact('title'));
    echo $this->PHForm->create('Order');
	echo $this->element('admin_content');
    echo $this->PHForm->input('agent_id', array('options' => $aAgentOptions, 'label' => array('class' => 'control-label', 'text' => 'Поставщик')));
    echo $this->PHForm->input('agent2_id', array('options' => $aAgentOptions, 'label' => array('class' => 'control-label', 'text' => 'Получатель')));
    echo $this->PHForm->input('nds', array('label' => array('class' => 'control-label', 'text' => 'НДС, %')));
    echo $this->PHForm->input('status', array(
        'label' => false,
        'multiple' => 'checkbox',
        'options' => array(
            'paid' => __('Paid'),
        ),
        'class' => 'checkbox inline'
    ));
	echo $this->element('admin_content_end');
	echo $this->element('Form.form_actions', array('backURL' => $this->Html->url(array('action' => 'index'))));
    echo $this->PHForm->end();
?>
</div>
