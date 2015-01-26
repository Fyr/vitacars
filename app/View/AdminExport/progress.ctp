<?
	$this->Html->script('/Core/js/json_handler', array('inline' => false));
?>
<?=$this->element('admin_title', array('title' => __('Data export')))?>
<div class="span8 offset2">
<?=$this->element('admin_content')?>
<div class="text-center">
	<br />
	<span id="info">Экспорт занимает от 10 мин. до получаса</span>
	<br /><br />
	<div id="progressTotal" class="progress progress-primary progress-striped">
        <div class="bar"></div>
    </div>
    <div id="progressStage" class="progress progress-primary progress-striped">
        <div class="bar"></div>
    </div>
    <button id="startExport" class="btn btn-primary">Начать экспорт</button>
    <button id="abortExport" class="btn btn-danger" style="display: none;">Отмена</button>
    <br />
</div>
<?=$this->element('admin_content_end')?>
</div>
<div id="ajaxError" style="display: none"></div>
<script type="text/javascript">
var stage, totalStages, abortExport, dataSource, lAjaxStarted;

function setTitle(msg) {
	$('#info').html(dataSource.replace(/_/, '.') + ': ' + msg);
}

function setProgress(e, n, total) {
	$('#' + e + ' .bar').css('width', Math.round(n * 100 / total) + '%');
}

function exportData(dataSrc) {
	$('#startExport').hide();
	$('#abortExport').show();
	
	stage = 0;
	totalStages = 8;
	dataSource = dataSrc;
	abortExport = false;
	
	setProgress('progressTotal', 0, 1);
	setProgress('progressStage', 0, 1);
	
	clearMedia();
}

function clearMedia() {
	if (abortExport) {
		return;
	}
	setTitle('Удаление предыдущих media-данных...');
	stage+= 0.5;
	setProgress('progressTotal', stage, totalStages);
	setProgress('progressStage', 0.5, 1);
	$.post('/AdminExportAjax/clearMedia.json', {data: {dataSource: dataSource}}, function(response){
		if (checkAbortExport(response)) {
			setProgress('progressStage', 1, 1);
			
			stage+= 0.5;
			setProgress('progressTotal', stage, totalStages);
			initExportArticles();
		}
	}, 'json');
}

function initExportArticles() {
	if (abortExport) {
		return;
	}
	setTitle('Подготовка для экспорта статей...');
	stage+= 0.5;
	setProgress('progressTotal', stage, totalStages);
	setProgress('progressStage', 0.5, 1);
	$.post('/AdminExportAjax/initExportArticles.json', {data: {dataSource: dataSource}}, function(response){
		if (checkAbortExport(response)) {
			setProgress('progressStage', 1, 1);
			
			stage+= 0.5;
			setProgress('progressTotal', stage, totalStages);
			
			stage+= 0.5;
			setProgress('progressTotal', stage, totalStages);
			exportArticles(response.data.page_count, 1);
		}
	}, 'json');
}

function exportArticles(total, page) {
	if (abortExport) {
		return;
	}
	setTitle('Экспорт статей (продукты, категории, брэнды, фото) ' + page + '/' + total + '...');
	setProgress('progressStage', page - 0.5, total);
	$.post('/AdminExportAjax/exportArticles.json', {data: {dataSource: dataSource, page: page, total: total}}, function(response){
		if (checkAbortExport(response)) {
			setProgress('progressStage', page, total);
			page++;
			if (page <= total) {
				exportArticles(total, page);
			} else {
				stage+= 0.5;
				setProgress('progressTotal', stage, totalStages);
				exportParams();
			}
		}
	}, 'json');
}

function exportParams() {
	if (abortExport) {
		return;
	}
	setTitle('Экспорт данных о тех.параметрах...');
	stage+= 0.5;
	setProgress('progressTotal', stage, totalStages);
	setProgress('progressStage', 0.5, 1);
	$.post('/AdminExportAjax/exportParams.json', {data: {dataSource: dataSource}}, function(response){
		if (checkAbortExport(response)) {
			setProgress('progressStage', 1, 1);
			
			stage+= 0.5;
			setProgress('progressTotal', stage, totalStages);
			initExportParamValues();
		}
	}, 'json');
}

function initExportParamValues() {
	if (abortExport) {
		return;
	}
	setTitle('Подготовка для экспорта значений тех.параметров...');
	stage+= 0.5;
	setProgress('progressTotal', stage, totalStages);
	setProgress('progressStage', 0.5, 1);
	$.post('/AdminExportAjax/initExportParamValues.json', {data: {dataSource: dataSource}}, function(response){
		if (checkAbortExport(response)) {
			setProgress('progressStage', 1, 1);
			
			stage+= 0.5;
			setProgress('progressTotal', stage, totalStages);
			
			stage+= 0.5;
			setProgress('progressTotal', stage, totalStages);
			exportParamValues(response.data.page_count, 1);
		}
	}, 'json');
}

function exportParamValues(total, page) {
	if (abortExport) {
		return;
	}
	setTitle('Экспорт значений тех.параметров ' + page + '/' + total + '...');
	setProgress('progressStage', page - 0.5, total);
	$.post('/AdminExportAjax/exportParamValues.json', {data: {dataSource: dataSource, page: page, total: total}}, function(response){
		if (checkAbortExport(response)) {
			setProgress('progressStage', page, total);
			page++;
			if (page <= total) {
				exportParamValues(total, page);
			} else {
				stage+= 0.5;
				setProgress('progressTotal', stage, totalStages);
				
				initExportSeo();
			}
		}
	}, 'json');
}

function initExportSeo() {
	if (abortExport) {
		return;
	}
	setTitle('Подготовка для экспорта SEO-данных...');
	stage+= 0.5;
	setProgress('progressTotal', stage, totalStages);
	setProgress('progressStage', 0.5, 1);
	$.post('/AdminExportAjax/initExportSeo.json', {data: {dataSource: dataSource}}, function(response){
		if (checkAbortExport(response)) {
			setProgress('progressStage', 1, 1);
			
			stage+= 0.5;
			setProgress('progressTotal', stage, totalStages);
			
			stage+= 0.5;
			setProgress('progressTotal', stage, totalStages);
			exportSeo(response.data.page_count, 1);
		}
	}, 'json');
}

function exportSeo(total, page) {
	if (abortExport) {
		return;
	}
	setTitle('Экспорт SEO-данных ' + page + '/' + total + '...');
	setProgress('progressStage', page - 0.5, total);
	$.post('/AdminExportAjax/exportSeo.json', {data: {dataSource: dataSource, page: page, total: total}}, function(response){
		if (checkAbortExport(response)) {
			setProgress('progressStage', page, total);
			page++;
			if (page <= total) {
				exportSeo(total, page);
			} else {
				stage+= 0.5;
				setProgress('progressTotal', stage, totalStages);
				console.log([stage, totalStages]);
				exportCompleted();
			}
		}
	}, 'json');
}

$(document).ready(function(){
	$('#startExport').click(function(){
		exportData('agromotors_by');
	});
	
	$('#abortExport').click(function(){
		startAbortExport();
	});
});
$(document).ajaxStart(function() {
	lAjaxStarted = true;
});
$(document).ajaxComplete(function() {
	lAjaxStarted = false;
});
$(document).ajaxError(function(event, jqxhr, settings, thrownError) {
	alert('Error ' + jqxhr.status + ': ' + jqxhr.statusText + '\n\n' + jqxhr.responseText);
});

function exportCompleted() {
	setProgress('progressTotal', 1, 1);
	setProgress('progressStage', 1, 1);
	setTitle('Экспорт успешно завершен');
	$('#startExport').show();
	$('#abortExport').hide();
}

function startAbortExport() {
	abortExport = true;
	setTitle('Остановка процесса экспорта...');
	if (!lAjaxStarted) {
		checkAbortExport({status: 'OK'});
	}
}

function checkAbortExport(response) {
	if (!checkJson(response)) {
		setTitle('Экспорт был прерван из-за ошибки системы!');
		alert('Внимание! Экспорт был прерван из-за ошибки системы!\nЭто может привести к ошибкам на сайтах agromotors');
		return false;
	}
	if (abortExport) {
		setTitle('Экспорт был прерван пользователем!');
		alert('Внимание! Экспорт был прерван!\nЭто может привести к ошибкам на сайтах agromotors');
		$('#startExport').show();
		$('#abortExport').hide();
		
		setProgress('progressTotal', 0, 1);
		setProgress('progressStage', 0, 1);
		return false;
	}
	return true;
}
</script>