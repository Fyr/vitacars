			<ul class="hn_ul">
<?
	foreach($aNavBar as $id => $item) {
		$class = (strtolower($id) == strtolower($currMenu)) ? ' class="active"' : '';
?>
				<li<?=$class?>><div class="fix"><?=$this->Html->link($item['label'], $item['href'])?>
<?
		if (isset($item['submenu'])) {
			echo '<ul>';
			if (isset($item['submenu'])) {
				foreach($item['submenu'] as $_item) {
					echo '<li>'.$this->Html->link($_item['label'], $_item['href']).'</li>';
				}
			}
			echo '</ul>';
		}
?>
				</div></li>
<?
	}
?>
			</ul>