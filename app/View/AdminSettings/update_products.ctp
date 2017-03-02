<?
	if (isset($task)) {
?>
<div class="span8 offset2">
<?
		$title = 'Обновление продуктов';
		echo $this->element('admin_title', compact('title'));
		echo $this->element('admin_content');
		echo $this->element('progress', compact('task'));
		echo $this->element('admin_content_end');
?>
</div>
<script type="text/javascript">
$(function () {
	$('#taskDone').click(function () {
		window.location.href = '<?=$this->Html->url(array('controller' => 'AdminSettings', 'action' => 'updateProducts'))?>';
	});
});
</script>
<?
	} else {
		echo $this->element('AdminSettings/update_products_form');
	}
?>