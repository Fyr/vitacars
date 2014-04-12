<div class="banner_place">
	<ul class="slider_banner bx_slider">
<?
	foreach($aSlider as $media) {
		$src = $this->Media->imageUrl($media, 'noresize');
?>
		<li class="banner_slide"><div class="img_container"><div class="img_container_in"><img src="<?=$src?>" alt=""></div></div></li>
<?
	}
?>
	</ul>
</div>