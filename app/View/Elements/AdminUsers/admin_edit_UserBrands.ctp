<?
	echo $this->PHTableGrid->render('Brand', array(
		'actions' => array(
			'table' => array(),
			'row' => array(),
			'checked' => array()
		)
	));
	echo $this->PHForm->hidden('User.brand_rights', array('value' => $this->request->data('User.brand_rights')));
?>
<script type="text/javascript">
$(document).ready(function(){
	var $grid = $('#grid_Brand');
	
	var vals = $('#UserBrandRights').val().split(',');
	for(var i = 0; i < vals.length; i++) {
		$('.grid-chbx-row[value=' + vals[i] + ']', $grid).click();
	}
	$('.form-3actions button[type=submit]').click(function(){
		var vals = [];
		$('.grid-chbx-row:checked', $grid).each(function(){
			vals.push($(this).val());
		});
		$('#UserBrandRights').val(vals.join(','));
	});
});
</script>
