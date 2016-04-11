<?php
App::uses('AppModel', 'Model');
class Search extends AppModel {
	public $useTable = 'search';

	protected $domain;

	protected function _beforeInit() {
		list($this->domain) = explode('.', $_SERVER['SERVER_NAME']);
		if ($this->domain !== 'vitacars') {
			$this->useDbConfig = 'vitacars';
		}
	}

	public function processTextRequest($_value) {
		$this->DetailNum = $this->loadModel('DetailNum');

		// вырезаем из оригинального запроса найденные слова категорий
		list($_value, $aExact) = $this->getExactWords($_value);
		$aWords = explode(' ',$_value);

		// Разделяем номера деталей и текст
		$aDigiWords = array();
		$aRest = array();
		foreach($aWords as $word) {
			if ($this->DetailNum->isDigitWord($word)) {
				$aDigiWords[] = $this->stripWord($word);
			} else {
				$aRest[] = $this->stripWord($word);
			}
		}
		unset($word);

		$aRest = $this->stripShortWords($aRest); // убиваем в тексте короткие незначащие слова
		$aRest = $this->stripStopWords($aRest); // убиваем в тексте стоп-слова
		$aWords = array_merge($aDigiWords, $aRest, $aExact);
		return $aWords;
	}

	private function getExactWords($q) {
		$_q = array();

		if ($this->domain == 'vitacars') {
			$this->VcarsArticle = $this->loadModel('Article.Article');
			$this->VcarsArticle->alias = 'VcarsArticle';
		} else {
			$this->VcarsArticle = $this->loadModel('VcarsArticle');
		}

		$fields = array('id', 'object_type', 'title', 'LENGTH(title) AS len');
		$conditions = array('object_type' => array('Subcategory', 'Category', 'Brand'));
		$order = 'len DESC, title ASC';
		$aArticles = $this->VcarsArticle->find('all', compact('fields', 'conditions', 'order'));

		$_factor = array();
		$_words = explode(' ', $q);
		foreach($aArticles as $article) {
			$id = $article['VcarsArticle']['id'];
			$objectType = $article['VcarsArticle']['object_type'];
			$title = mb_strtolower($article['VcarsArticle']['title']);
			$title = str_replace(array('.', '-', ',', '/', '\\', '&'), ' ', $title);
			$title = $this->stripSpaces($title);

			if ($info = $this->getWordsInfo($_words, $title)) {
				$total = count($info);
				$_factor[$id] = compact('total', 'info', 'id', 'objectType', 'title');
			}
		}

		if ($_factor) {
			// если точные совпадения по категориям найдены - вырезаем их
			$_factor = Hash::sort($_factor, '{n}.total', 'desc');
			foreach($_factor[0]['info'] as $_word) {
				$q = str_replace($_word, '', $q);
			}
			$_q = $_factor[0]['info'];
		}

		return array($this->stripSpaces($q), $_q);
	}

	private function getWordsInfo($_words, $title) {
		$_match = array();
		foreach(explode(' ', $title) as $i => $_title) {
			foreach($_words as $_word) {
				if ($_word == $_title) {
					$_match[$i] = $_word;
				}
			}
		}
		return $_match;
	}

	private function stripWord($q) {
		return str_replace(array('.', '', '-', ',', '/', '\\'), '', $q);
	}

	public function stripSpaces($q) {
		return trim(str_replace(array('    ', '   ', '  '), ' ', $q));
	}

	private function stripShortWords($aWords) {
		$_words = array();
		foreach($aWords as $word) {
			if ($this->isRu($word) && mb_strlen($word) <= 2) {
				// исключаем такие слова
			} else {
				$_words[] = $word;
			}
		}
		return $_words;
	}


	private function stripStopWords($aWords) {
		$aStopWords = explode(' ', Configure::read('search.stopWords'));
		$_words = array();
		foreach($aWords as $word) {
			if (in_array($word, $aStopWords)) {
				// исключаем такие слова
			} else {
				$_words[] = $word;
			}
		}
		return $_words;
	}
	/**
	 * Возвращает true, если слово состоит из русских букв
	 * (Считаем, что состоит, если хотя бы один символ русский, т.к. могут быть опечатки)
	 * @param $q
	 * @return bool
	 */
	public function isRu($q) {
		for($i = 0; $i < mb_strlen($q); $i++) {
			$ch = mb_substr($q, $i, 1);
			if (mb_strpos('абвгдеёжзийклмнопрстуфхцчшщъыьэюя', $ch) !== false) {
				return true;
			}
		}
		return false;
	}
}
