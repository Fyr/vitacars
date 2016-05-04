<?
	$this->Html->script('/Core/js/json_handler', array('inline' => false));
?>
<?=$this->element('admin_title', array('title' => 'Пересчет формул'))?>
<div class="span8 offset2">
	<?=$this->element('admin_content')?>
	<div class="text-center">
		<br />
		<span id="info">Пересчет формул занимает около 40 мин.<br/>&nbsp;</span>
		<br /><br />
		<div id="progressTotal" class="progress progress-primary progress-striped">
			<div class="bar"></div>
		</div>
		<div style="height: 30px;">
			<button class="btn btn-primary">Запуск</button>
			<!-- button id="abortExport" class="btn btn-danger" style="display: none;">Отмена</button -->
		</div>
	</div>
	<?=$this->element('admin_content_end')?>
</div>
<div id="ajaxError" style="display: none"></div>
<script type="text/javascript">

function setTitle(msg) {
	$('#info').html(msg);
}

function setProgress(n, total) {
	$('#progressTotal .bar').css('width', Math.round(n * 100 / total) + '%');
}

function updateStatus() {
	$.get('<?=$this->Html->url(array('controller' => 'AdminAjax', 'action' => 'recalcStatus'))?>.json', null, function(response){
		if (checkJson(response)) {
			if (response.data.progress < response.data.total) {
				percent = Math.round(response.data.progress * 100 / response.data.total);
				setTitle('Выполняется пересчет формул...<br/>Обработано записей: ' + percent + '% (' + response.data.progress + '/' + response.data.total + ')');
				setProgress(response.data.progress, response.data.total)
				updateStatus();
			} else {
				setTitle('Пересчет формул выполнен успешно<br/>Обработано ' + response.data.progress + ' записей');
				$('#progressTotal').hide();
			}
		}
	});
	return false;
}

$(function(){
	$('.btn-primary').click(function(){
		$(this).hide();
		$.get('<?=$this->Html->url(array('controller' => 'AdminAjax', 'action' => 'recalcStart'))?>.json', null, function(response){
			if (checkJson(response)) {
				updateStatus();
			}
		});
	});
});
</script>