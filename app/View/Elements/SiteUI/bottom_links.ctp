			<ul class="footer_navi_ul">
<?
	foreach($aBottomLinks as $id => $item) {
		$class = (strtolower($id) == strtolower($currMenu)) ? ' class="active" style="font-weight: bold;"' : '';
?>
				<li<?=$class?>><?=$this->Html->link($item['label'], $item['href'])?></li>
<?
	}
?>
			</ul>
