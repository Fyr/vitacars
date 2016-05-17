<?
	// $this->Html->script('/Core/js/json_handler', array('inline' => false));
	$this->Html->script(array('/Core/js/json_handler', 'vendor/xdate'), array('inline' => false));
?>
<?=$this->element('admin_title', array('title' => 'Пересчет формул'))?>
<div class="span8 offset2">
	<?=$this->element('admin_content')?>
	<div class="text-center">
		<br />
		<span id="task">1. Пересчет формул...</span><br />
		<br />
		<div id="progressTask">
			<span class="info"><span>Прогресс: 5% (5/100)</span> <span>Время: 00:15:49</</span> Осталось: ~00:10:34</span>
			<div class="progress progress-primary progress-striped">
				<div class="bar"></div>
			</div>
		</div>

		<div id="progressTotal">
			<span class="info">Всего: 5% (1/3) Время: 00:15:49 Осталось: ~00:10:34</span>
			<div class="progress progress-primary progress-striped">
				<div class="bar" style="width: 50%"></div>
			</div>
		</div>

		<div style="height: 30px;">
			<button id="taskRun" class="btn btn-primary">Начать</button>
			<button id="taskAbort" class="btn btn-danger" style="display: none;">Отмена</button>
		</div>
	</div>
	<?=$this->element('admin_content_end')?>
</div>
<div id="ajaxError" style="display: none"></div>
<script type="text/javascript">

function setTitle(msg) {
	$('#task').html(msg);
}

function setProgress(n, total, context) {
	context = (context) ? context + ' ' : '';
	var percent = Math.round(n * 100 / total);
	$(context + '.info').html('Прогресс: ' + percent + '% (' + n + '/' + total + ')');
	$(context + '.bar').css('width', percent + '%');
}

function updateStatus() {
	$.get('<?=$this->Html->url(array('controller' => 'AdminAjax', 'action' => 'recalcStatus'))?>.json', null, function(response){
		if (checkJson(response)) {
			if (response.data.progress < response.data.total) {
				setProgress(response.data.progress, response.data.total);
				updateStatus();
			} else {
				setTitle('Пересчет формул выполнен успешно');
			}
		}
	});
	return false;
}

$(function(){
	$('#taskRun').click(function(){
		$(this).hide();
		$('taskAbort').show();
		setTitle('Выполняется пересчет формул...');
		$.get('<?=$this->Html->url(array('controller' => 'AdminAjax', 'action' => 'recalcStart'))?>.json', null, function(response){
			if (checkJson(response)) {
				updateStatus();
			}
		});
	});

	$('#taskAbort').click(function(){
		$.get('<?=$this->Html->url(array('controller' => 'AdminAjax', 'action' => 'recalcAbort'))?>.json', null, function(response){
			if (checkJson(response)) {
				$('taskRun').show();
				$('taskAbort').hide();
				setTitle('Пересчет формул прерван!');
			}
		});
	});
});
</script>