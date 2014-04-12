<ul class="side_navi_ul">
<?
	$subcat = $aSubcategories[0];
?>
	<li id="cat-<?=$subcat['Category']['id']?>">
		<div class="side_lvl_1_n"><a href=""><?=$subcat['Category']['title']?></a></div>
		<ul>

<?
	$cat = Hash::get($aSubcategories[0], 'Category.id');
	foreach($aSubcategories as $subcat) {
		if ($cat != $subcat['Category']['id']) {
			$cat = $subcat['Category']['id'];
?>
		</ul>
	</li>
	<li id="cat-<?=$subcat['Category']['id']?>">
		<div class="side_lvl_1_n"><a href=""><?=$subcat['Category']['title']?></a></div>
		<ul>
<?			
		}
		$url = array('controller' => 'SiteProducts', 'action' => 'index', '?' => array('data[Product][cat_id]' => $subcat['Category']['id'], 'data[Product][subcat_id]' => $subcat['Subcategory']['id']));
?>
			<li><a href="<?=$this->Html->url($url)?>"><?=$subcat['Subcategory']['title']?></a></li>
<?
	}
?>
		</ul>
	</li>
</ul>