<?
	if ($message) {
?>
	<!--div class="span8 offset2" style="margin-left: 19.6581%"-->
		<div class="alert <?=($class) ? 'alert-'.$class : ''?>">
			<button type="button" class="close" data-dismiss="alert">&times;</button>
			<?=$message?>
		</div>
	<!--/div-->
<?
	}
?>