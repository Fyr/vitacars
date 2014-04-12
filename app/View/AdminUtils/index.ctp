<?=$this->element('admin_title', array('title' => __('Utils')))?>
<div class="span8 offset2">
<?=$this->element('admin_content')?>
<?
	$backURL = $this->Html->url(array('plugin' => '', 'controller' => 'AdminUtils', 'action' => 'index'));
	$url = $this->Html->url(array('plugin' => 'translate', 'controller' => 'index', 'action' => 'generate', 
		'?' => array('backURL' => $backURL)
	));
?>
<a class="btn btn-primary" href="<?=$url?>"><?=__('Generate static labels')?></a> 
<a class="btn btn-primary" href="<?=$url?>"><?=__('Clean image cache')?></a><br/>
<?=$this->element('admin_content_end')?>
</div>