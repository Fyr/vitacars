<?
    $id = $this->request->data('Client.id');

    echo $this->PHForm->input('Client.zone', array('options' => array('by' => 'BY', 'ru' => 'RU')));
    echo $this->PHForm->input('Client.group_id', array(
        'label' => array('text' => __('Client type'), 'class' => 'control-label'),
        'options' => Client::getOptions(),
        'readonly' => !!$id
    ));

	echo $this->PHForm->input('Client.email');
	$password = '';
	if ($id) {
	    $password = $this->request->data('Client.password');
        echo $this->PHForm->input('Client.password', array(
            'class' => 'input-medium', 'required' => false, 'value' => '', 'autocomplete' => 'off', 'readonly' => true,
            'onfocus' => "onFocusPassword()"
        ));
        echo $this->PHForm->input('Client.password_confirm', array(
            'type' => 'password',
            'required' => false,
            'value' => '',
            'class' => 'input-medium',
            'div' => 'control-group hidden'
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
    $('#ClientPassword').attr('readonly', false).attr('required', true);
    $('.control-group.hidden').removeClass('hidden');
    $('#ClientPasswordConfirm').attr('required', true);
}

<?
    if ($this->request->is('put') && $password) {
?>
$(function () {
    onFocusPassword();
});
<?
    }
?>
</script>
