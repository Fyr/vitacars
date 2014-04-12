								<ul class="main_news_list">
<?
	foreach($products as $article) {
		$this->ArticleVars->init($article, 'Product', $url, $title, $teaser, $src, '315x');
?>
									<li class="main_news_li">
										<div class="main_col_img fixed">
											<a href="<?=$url?>">
												<span class="img_item_h"><?=$title?></span>
												<span class="img_item_price"><?=$article['Product']['price']?>&nbsp;</span>
<?
		if ($src) {
?>
												<img src="<?=$src?>" alt="<?=$title?>">
<?
		}
?>
											</a>
										</div>
									</li>
<?
	}
?>
								</ul>
<?
	echo $this->element('paginate');
?>