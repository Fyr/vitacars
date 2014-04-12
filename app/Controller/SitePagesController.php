<?php
App::uses('AppController', 'Controller');
App::uses('SiteController', 'Controller');
class SitePagesController extends SiteController {
	public $name = 'SitePages';
	public $uses = array('Page', 'News', 'Product');
	// public $helpers = array('ArticleVars');

	public function home() {
		$this->currMenu = 'Home';
		
		// Welcome block
		$article = $this->Page->findBySlug('home');
		$this->set('home_article', $article);
		$this->pageTitle = $article['Page']['title'];
		
		// Новости
		$news = $this->News->find('all', array('conditions' => array('News.published' => 1), 'order' => 'News.created DESC', 'limit' => 2));
		$this->set('news', $news);
		
		// Новинки
		$products = $this->Product->find('all', array('conditions' => array('Product.published' => 1), 'order' => 'Product.created DESC', 'limit' => 2));
		$this->set('products', $products);
	}
	
	public function view($slug) {
		$article = $this->Page->findBySlug($slug);
		$this->pageTitle = $article['Page']['title'];
		$this->set('article', $article);
	}
}
