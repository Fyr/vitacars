<div class="span8 offset2">
<?
    echo $this->element('admin_title', array('title' => $message['Message']['title']));
	echo $this->element('admin_content');
	echo $message['Message']['body'];

	$backURL = $this->Html->url(array('action' => 'messageList'));
	$deleteLink = $this->Html->link('Удалить <i class="icon-white icon-trash"></i>',
		$this->Html->url(array('action' => 'delete')).'/'.$message['Message']['id'].'?model=Message&backURL='.urlencode($backURL),
		array('class' => 'btn btn-danger pull-right', 'escape' => false),
		__('Are you sure to delete this record?')
	);
	echo $this->element('admin_content_end');
?>
	<table class="form-3actions" width="100%">
		<tr>
			<td width="30%">
				<a href="<?=$this->Html->url(array('action' => 'messageList'))?>" class="btn"><i class="icon-chevron-left"></i> К списку сообщений</a>
			</td>
			<td width="40%" align="center">
				<?//$this->PHForm->submit('<i class="icon-white icon-ok"></i> '.__('Save'), array('class' => 'btn btn-primary', 'name' => 'save', 'value' => 'save'))?>
			</td>
			<td width="30%">
				<?=$deleteLink?>
			</td>
		</tr>
	</table>
	<br/>
</div>
