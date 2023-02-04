<?
if (count($recentMsgs)) {
	if (count($recentMsgs) > 1) {
		$msgURL = array('plugin' => '', 'controller' => 'AdminMessages', 'action' => 'messageList');
		$title = sprintf('У вас %s непрочитанных сообщений', count($recentMsgs));
		$body = sprintf(
			'Кликните %s или по кнопке под этим текстом, чтобы просмотреть ваши сообщения',
			$this->Html->link('сюда', $msgURL)
		);
		$btnTitle = 'Прочитать все';
	} else {
		$msgURL = array('plugin' => '', 'controller' => 'AdminMessages', 'action' => 'view', $recentMsgs[0]['Message']['id']);
		$title = $recentMsgs[0]['Message']['title'];
		$body = $recentMsgs[0]['Message']['body'];
		$btnTitle = 'Прочитать полностью';
	}
?>
<script>
var timer;

function popupFadeout(delay) {
	timer = setTimeout(function () {
		$("#notify").dialog('option', 'hide', {effect: "fadeOut", duration: 2000});
		$("#notify").dialog('close');
	}, delay);
}

$(function () {
	setTimeout(function () {
		$("#notify").dialog({
			show: {
				effect: "fadeIn",
				duration: 2000
			},
			resizable: false,
			draggable: false,
			width: 400,
			position: {my: "left top", at: "right bottom", of: window}
		});
		popupFadeout(7000);

		$('.ui-dialog').mouseenter(function(){
			console.log('mouseenter');
			clearTimeout(timer);
			$(this).addClass('ui-force-show');
			$("#notify").dialog('option', 'show', false);
			$("#notify").dialog('option', 'hide', {effect: "fadeOut", duration: 1});
			$("#notify").dialog('open');
		});

		$('.ui-dialog').mouseleave(function(){
			$(this).removeClass('ui-force-show');
			popupFadeout(4000);
		});
	}, 2000);
});
</script>
<div id="notify" title="<?=$title?>" style="display: none">
<? /*
	<div id="content">
		<p><?=nl2br($body)?></p>
	</div>
*/?>
	<a class="btn-small btn-primary pull-right" href="<?=$this->Html->url($msgURL) ?>">
		<?=$btnTitle?> <i class="icon-white icon-chevron-right"></i>
	</a>
</div>
<?
}
?>