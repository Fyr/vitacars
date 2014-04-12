
								<div class="main_2_cold">
									<?=$home_article['Page']['body']?>
								</div>
							</div>
						</div>
					</div>
					<div class="main_col_block">
						<?=$this->element('/SiteUI/page_title', array('pageTitle' => 'Новинки'))?>
						<div class="main_col_c">
							<div class="main_col_c_in">
								<ul class="main_news_list">
<?
	foreach($products as $article) {
		$this->ArticleVars->init($article, 'Product', $url, $title, $teaser, $src, '315x');
?>
									<li class="main_news_li">
										<div class="main_col_img fixed">
											<a href="<?=$url?>">
												<span class="img_item_h"><?=$title?></span>
												<span class="img_item_price"><!--price-->&nbsp;</span>
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
							</div>
						</div>
					</div>
					<div class="main_col_block">
						<?=$this->element('/SiteUI/page_title', array('pageTitle' => 'Новости компаний'))?>
						<div class="main_col_c">
							<div class="main_col_c_in">
								<ul class="main_news_list">
<?
	foreach($news as $article) {
		$this->ArticleVars->init($article, 'News', $url, $title, $teaser, $src, '315x');
?>
									<li class="main_news_li">
<?
		if ($src) {
?>
										<div class="main_col_img"><a href="<?=$url?>"><img src="<?=$src?>" alt="<?=$title?>"></a></div>
<?
		}
?>

										<div class="short_article_h"><a href="<?=$url?>"><?=$title?></a></div>
										<div class="short_article_t">
											<p><?=$teaser?></p>
										</div>
										<div class="short_article_f"><a href="<?=$url?>" class="more_link">Подробнее</a></div>
									</li>
<?
	}
?>

								</ul>
