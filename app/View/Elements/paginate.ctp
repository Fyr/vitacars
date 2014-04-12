<?
	if ($this->Paginator->numbers()) {
?>
<div class="paging">
	Страница: <?=$this->Paginator->numbers()?>
</div>
<?
	}
?>