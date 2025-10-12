<?
	$title = $this->ObjectType->getTitle('index', $objectType);
    $createURL = $this->Html->url(array('action' => 'edit'));
    $createTitle = $this->ObjectType->getTitle('create', $objectType);
?>
<?=$this->element('admin_title', compact('title'))?>
<div class="text-center">
    <a class="btn btn-primary" href="<?=$createURL?>">
        <i class="icon-white icon-plus"></i> <?=$createTitle?>
    </a>
</div>
<br/>
<?
    echo $this->PHTableGrid->render($objectType);
?>
