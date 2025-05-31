<?php
App::uses('Controller', 'Controller');
class AppController extends Controller {
    public $paginate;
	// public $components = array('DebugKit.Toolbar');
	public $aNavBar = array(), $aBottomLinks = array(), $currMenu = '', $currLink = '', $pageTitle = '';

	protected $Settings;

    public function __construct($request = null, $response = null) {
	    $this->_beforeInit();
	    parent::__construct($request, $response);
	    $this->_afterInit();
	}

	protected function _beforeInit() {
	    // Add here components, models, helpers etc that will be also loaded while extending child class
	}

	protected function _afterInit() {
	    // after construct actions here
		if (!Configure::read('Settings')) {
			App::uses('Settings', 'Model');
			/*
			$this->Settings = new Settings();
			$this->Settings->setDataSource('giperzap'); // load settings from GiperZap
			$this->Settings->initData();
			*/

			$this->Settings = new Settings();
			$this->Settings->initData();
			/*
                        $stopWords = Configure::read('Settings.gpz_stop');
                        $stopWords = str_replace(array("\r\n", "\r", "\n"), "|", $stopWords);
                        Configure::write('Settings.gpz_stop', explode("|", $stopWords));
            */
		}
	}

    public function isAuthorized($user) {
    	$this->set('currUser', $user);
		return Hash::get($user, 'active');
	}

	public function beforeRender() {
		$this->set('aNavBar', $this->aNavBar);
		$this->set('currMenu', $this->currMenu);
		$this->set('aBottomLinks', $this->aBottomLinks);
		$this->set('currLink', $this->currLink);
		$this->set('pageTitle', $this->pageTitle);
	}

	public function setFlash($msg, $type = 'info') {
		$this->Session->setFlash($msg, 'default', array(), $type);
	}

	/**
	 * Runs shell in background (do not wait until shell is completed)
	 * @param $shell - shell name
	 */
	public function runBkg($method) {
		if (TEST_ENV) {
			fdebug('../Console/cake.bat bkg_service '.$method."\r\n", 'run.bat', false);
		} else {
			system("../Console/cake bkg_service {$method} < /dev/null > task.log &");
		}
	}

	protected function _getCacheFilename($key, $zone = '') {
		return Configure::read("sitemap.$zone.dir").Configure::read("sitemap.$zone.prefix").$key;
	}

	protected function _cleanCache($key) {
		foreach(array('by', 'ru') as $zone) {
			$fname = $this->_getCacheFilename($key, $zone);
			if (Configure::read("sitemap.$zone.cache") && file_exists($fname)) {
				return unlink($fname);
			}
		}
		return '';
	}

	protected function _cleanProductsCache($category) {
		// TODO: clean up caache when new products are created
		$this->loadModel('Product');
		$this->Product->unbindModel(array(
			'belongsTo' => array('Category', 'Subcategory', 'Brand'),
			'hasOne' => array('Media', 'PMFormData', 'Seo', 'Search')
		));

		// get products amount in category
		$conditions = array('Product.published' => 1, 'Product.cat_id' => $category['Category']['id'], 'Product.is_fake' => 0);
		$result = $this->Product->find('count', compact('conditions'));

		foreach(array('by', 'ru') as $zone) {
			if (Configure::read("sitemap.$zone.cache")) {
				$pagesCount = ceil($result / Configure::read("sitemap.$zone.per_page"));
				for ($i = 1; $i <= $pagesCount; $i++) {
					$slug = $category['Category']['slug'];
					$fname = $this->_getCacheFilename("products_{$slug}_{$i}.xml.gz", $zone);
					if (file_exists($fname)) {
						unlink($fname);
					}
				}
			}
		}
	}
}
