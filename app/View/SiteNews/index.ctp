
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
<?
	echo $this->element('paginate');
?>