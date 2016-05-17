<?
	$this->Html->script(array('/Core/js/json_handler', 'vendor/xdate'), array('inline' => false));
?>
<div class="text-center">
	<br />
	<span id="task"><?=$task_name?></span><br />
	<br />
	<div id="progressSubtask">
		<span class="info">&nbsp;<!-- Прогресс: 5% (5/100) Время: 00:15:49 Осталось: ~00:10:34--></span>
		<div class="progress progress-primary progress-striped">
			<div class="bar"></div>
		</div>
	</div>

	<div id="progressTotal">
		<span class="info">&nbsp;<!-- Всего: 5% (1/3) Время: 00:15:49 Осталось: ~00:10:34 --></span>
		<div class="progress progress-primary progress-striped">
			<div class="bar"></div>
		</div>
	</div>

	<div style="height: 30px;">
		<button id="taskAbort" class="btn btn-danger">Прервать</button>
		<button id="taskDone" class="btn btn-success" style="display: none;" onclick="window.location.reload()">OK</button>
		<button id="taskAborted" class="btn" style="display: none;" onclick="window.location.reload()">Отмена</button>
	</div>
</div>
<script>
var ABORT;

function setTitle(msg) {
	$('#task').html(msg);
}

function setProgress(task, context) {
	context = (context) ? context + ' ' : '';
	var aStats = [
		'<span>' + (task.subtask ? 'Всего: ' : 'Прогресс: ') + '</span>' +  task.progress.percent + '% (' + task.progress.progress + '/' + task.progress.total + ')',
		'<span>' + 'Время: ' + '</span>' + Date.getPeriod(task.progress.exec_time, 'rus'),
		'<span>' + 'Осталось: '  + '</span>' + '~' + Date.getPeriod(task.progress.time_finish, 'rus')
	];
	if (!task.subtask) {
		aStats.push('<span>' + 'Скорость: ' + '</span>' + task.progress.avg_speed + '/сек');
	}
	$(context + '.info').html(aStats.join('&nbsp;&nbsp;'));
	$(context + '.bar').css('width', task.progress.percent + '%');
}

function renderStatus(task) {
	setTitle(task.status == '<?=Task::ABORT?>' ? ABORT : task.task_name);
	if (task.subtask) {
		setTitle(task.subtask.task_name);
		setProgress(task.subtask, '#progressSubtask');

		if (task.subtask.progress.time_finish > task.progress.time_finish) {
			task.progress.time_finish = task.subtask.progress.time_finish;
		}
	}
	setProgress(task, '#progressTotal');
}

function updateStatus(url) {
	$.get(url, null, function(response){
		if (checkJson(response)) {
			console.log(response);
			renderStatus(response.data);
			if (response.data.status == '<?=Task::CREATED?>' || response.data.status == '<?=Task::RUN?>') {
				setTimeout(function() { updateStatus(url) }, 1000);
			} else {
				$('#taskAbort').hide();
				if (response.data.status == '<?=Task::DONE?>') {
					setTitle('Процесс выполнен успешно');
					$('#taskDone').show();
				} else if (response.data.status == '<?=Task::ABORT?>') {
					setTitle(ABORT);
					setTimeout(function() { updateStatus(url) }, 1000);
				} else if (response.data.status == '<?=Task::ABORTED?>') {
					setTitle('Процесс остановлен пользователем!');
					$('#taskAborted').show();
				} else if (response.data.status == '<?=Task::ERROR?>') {
					setTitle('<span class="err-msg">Ошибка выполнения процесса! ' + response.data.xdata + '</span>');
					$('#taskAborted').show();
				}
			}
		}
	});
	return false;
}
$(function(){
	ABORT = 'Остановка процесса...';
<?
	if (isset($id)) {
?>
	updateStatus('<?=$this->Html->url(array('controller' => 'AdminAjax', 'action' => 'getTaskStatus', $id))?>.json');
	$('#taskAbort').click(function(){
		setTitle(ABORT);
		$.get('<?=$this->Html->url(array('controller' => 'AdminAjax', 'action' => 'taskAbort', $id))?>.json', null, function(response){
			if (checkJson(response)) {
			}
		});
	});
<?
	}
?>
});
</script>