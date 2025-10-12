<?php
App::uses('AdminController', 'Controller');
App::uses('Brand', 'Model');
App::uses('Seo', 'Seo.Model');
class AdminBrandsController extends AdminController {
    public $name = 'AdminBrands';
    public $components = array('Article.PCArticle');
	public $uses = array('Brand', 'Seo.Seo');
    public $helpers = array('ObjectType');

    public $objectType = 'Brand';

    public function beforeFilter() {
        if (!$this->isAdmin() && !AuthComponent::user('view_brands')) {
            $this->redirect(array('controller' => 'Admin', 'action' => 'index'));
            return;
        }
        $this->currMenu = 'Brands';
        $this->set('objectType', $this->objectType);
        $this->PCArticle->setModel($this->objectType);
        parent::beforeFilter();
    }

    public function index() {
        $this->paginate = array(
        	'Brand' => array(
        		'fields' => array('id', 'title', 'published', 'is_fake')
        	),
        );
        $this->PCArticle->index();
    }

	public function edit($id = 0) {
	    $this->request->data('Seo.object_type', $this->objectType);
		$this->PCArticle->edit($id, $lSaved);
		if ($lSaved) {
			if ($objectType == 'Brand') {
				$this->_cleanCache('articles_Brand.xml');
			}
			$baseRoute = array('action' => 'index');
			return $this->redirect(($this->request->data('apply')) ? $baseRoute : array($id));
		}
	}
}
