<?
	$msgs = __('Messages');
	if (isset($messages) && $messages) {
		if ($messages['unread']) {
			$msgs = sprintf('<span class="text-error">Новых сообщений: <b>%d</b></span><span> (%d)</span>', $messages['unread'], $messages['total']);
		} else {
			$msgs = sprintf('<span>%s (%d)</span>', __('Messages'), $messages['total']);
		}
	}

?>
<p class="navbar-text text-right small-text right-bottom">
	<a href="<?=$this->Html->url(array('plugin' => '', 'controller' => 'admin', 'action' => 'index'))?>" rel="tooltip-bottom" title="Перейти на главную" class="navbar-link">
		<i class="icon-home"></i><span>Главная</span>
	</a> |
	<a href="<?=$this->Html->url(array('plugin' => '', 'controller' => 'adminMessages', 'action' => 'messageList'))?>" rel="tooltip-bottom" title="Перейти к просмотру сообщений" class="navbar-link">
		<i class="icon-envelope"></i><?=$msgs?>
	</a> |
	<a href="<?=$this->Html->url(array('plugin' => '', 'controller' => 'adminAuth', 'action' => 'logout'))?>" rel="tooltip-bottom" title="<?=__('Log out')?>" class="navbar-link">
		<i class="icon-off"></i><span><?=__('Log out')?></span>
	</a>
</p>