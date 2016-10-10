<div class="span8 offset2">
<?
    $id = $this->request->data('Order.id');
    $title = $this->ObjectType->getTitle(($id) ? 'edit' : 'create', 'Order');
    echo $this->element('admin_title', compact('title'));
    if ($id) {
?>
    <div style="height: 30px; margin: 10px 0;">
        <a class="btn pull-right" href="<?=$this->Html->url(array('action' => 'details', $id))?>">Детали <i class="icon-chevron-right"></i></a>
    </div>

<?
    }
    echo $this->PHForm->create('Order', array('type' => 'file'));
	echo $this->element('admin_content');
    echo $this->PHForm->input('agent_id', array('options' => $aAgentOptions, 'label' => array('class' => 'control-label', 'text' => 'Поставщик')));
    echo $this->PHForm->input('agent2_id', array('options' => $aAgent2Options, 'label' => array('class' => 'control-label', 'text' => 'Получатель')));
    echo $this->PHForm->input('currency', array('options' => $aCurrencyOptions, 'label' => array('class' => 'control-label', 'text' => 'Валюта')));
    echo $this->PHForm->input('nds', array('class' => 'input-small', 'label' => array('class' => 'control-label', 'text' => 'НДС, %')));
    echo $this->PHForm->input('paid', array('label' => array('class' => 'control-label', 'text' => 'Оплачено')));
/*
    echo $this->PHForm->input('status', array(
        'label' => false,
        'multiple' => 'checkbox',
        'options' => array(
            'paid' => __('Paid'),
        ),
        'class' => 'checkbox inline'
    ));
*/
    if (!$id) {
?>
    <div class="control-group">
        <div class="controls">
            <h4>Загрузить детальные строки</h4>
        </div>
    </div>

<?
        echo $this->PHForm->input(__('From file'), array(
            'class' => 'input-medium',
            'type' => 'file',
            'name' => 'csv_file',
            'id' => 'csv_file',
            'after' => '<div class="small-text">Формат файла: {номер детали};{кол-во} (CSV, без заголовков)</div></div>'
        ));
        echo $this->PHForm->input('details_text', array(
            'type' => 'textarea',
            'label' => array('class' => 'control-label', 'text' => 'Из текста')
        ));
    }
	echo $this->element('admin_content_end');
    if ($id) {
        echo $this->element('Form.form_actions', array('backURL' => $this->Html->url(array('action' => 'index'))));
    } else {
        echo $this->PHForm->submit(
            __('Upload').' <i class="icon-white icon-chevron-right"></i>',
            array('class' => 'btn btn-success pull-right', 'name' => 'apply', 'value' => 'apply')
        ).'<br/>';
    }
    echo $this->PHForm->end();
?>
</div>
