<?
    $id = $this->request->data('Client.id');

    echo $this->PHForm->input('Client.zone', array(
        'options' => array('by' => 'BY', 'ru' => 'RU'),
        'disabled' => !!$id
    ));
    echo $this->PHForm->input('Client.group_id', array(
        'label' => array('text' => __('Client Type'), 'class' => 'control-label'),
        'options' => Client::getOptions(),
        'disabled' => !!$id
    ));

	echo $this->PHForm->input('Client.email');
	$password = '';
	if ($id) {
	    $password = $this->request->data('Client.password');
        echo $this->PHForm->input('Client.password', array(
            'autocomplete' => 'off',
            'class' => 'input-medium',
            'required' => false,
            'value' => '',
            'onfocus' => 'onFocusPassword()',
            'onblur' => 'onBlurPassword()'
        ));
        echo $this->PHForm->input('Client.password_confirm', array(
            'autocomplete' => 'off',
            'type' => 'password',
            'required' => false,
            'value' => '',
            'class' => 'input-medium',
            'div' => 'control-group'
        ));
    } else {
        echo $this->PHForm->input('Client.password', array('class' => 'input-medium'));
        echo $this->PHForm->input('Client.password_confirm', array('type' => 'password', 'class' => 'input-medium'));
    }

    if ($id) {
	    echo $this->PHForm->input('status', array(
            'label' => false,
            'multiple' => 'checkbox',
            'options' => array(
                'active' => __('Active'),
            ),
            'class' => 'checkbox inline'
        ));
    }
?>
<script type="text/javascript">
function onFocusPassword() {
    $('#ClientPassword').attr('required', true);
    $('#ClientPasswordConfirm').attr('required', true).parent().parent().show();
}

function onBlurPassword() {
    if (!$('#ClientPassword').val()) {
        $('#ClientPassword').attr('required', false);
        $('#ClientPasswordConfirm').attr('required', false).parent().parent().hide();
    }
}

$(function () {
    onBlurPassword();
<?
    if ($this->request->is('put') && $password) {
?>
    onFocusPassword();
<?
    }
?>
});

</script>
