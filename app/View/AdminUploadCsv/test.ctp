<div class="span8 offset2">
<?
	$title = __('Upload counters');
	echo $this->element('admin_title', compact('title'));
	echo $this->element('admin_content');
	if ($task) {
		echo $this->element('progress', compact('task'));
	} else {
?>
	<form class="form-horizontal" action="" method="post">
	<div>
		<div class="text-center">
			Test Task занимает около 3х минут<br/>
		</div>
		<br/>
		<div class="control-group">
			<label class="control-label">Кол-во итераций, <br/> не более 60</label>
			<div class="controls">
				<input type="text" value="20" maxlength="255" class="input-small" name="data[total]" autocomplete="off">
			</div>
		</div>
		<br/>
		<div class="text-center">
			<button type="submit" class="btn btn-primary">Начать</button>
		</div>
	</div>
	</form>
<?
	}
	echo $this->element('admin_content_end');
?>
</div>