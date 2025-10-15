<?php
App::uses('AdminController', 'Controller');
App::uses('Category', 'Model');
App::uses('Seo', 'Seo.Model');
class AdminCategoriesController extends AdminController {
    public $name = 'AdminCategories';
    public $components = array('Article.PCArticle');
	public $uses = array('Category', 'Seo.Seo');
    public $helpers = array('ObjectType');

    private $objectType = 'Category';

    public function beforeFilter() {
        $this->currMenu = 'Category';
        $this->set('objectType', $this->objectType);
        $this->PCArticle->setModel($this->objectType);
        parent::beforeFilter();
    }

    public function index() {
        $this->paginate = array(
        	'Category' => array(
        		'conditions' => array('is_fake' => 0),
                'fields' => array('id', 'title', 'sorting', 'export_by', 'export_ru'),
                'order' => array('Category.sorting' => 'ASC')
        	),
        );
        $this->PCArticle->index();
    }

	public function edit($id = 0) {
	    $this->request->data('Seo.object_type', $this->objectType);
		$this->PCArticle->edit($id, $lSaved);
		if ($lSaved) {
            $category = $this->Category->findById($id);
            $this->_cleanCache('product_Categories.xml');
            $this->_cleanProductsCache($category);

			$baseRoute = array('action' => 'index');
			return $this->redirect(($this->request->data('apply')) ? $baseRoute : array($id));
		}

		if (!$id) {
		    $this->request->data('Category.sorting', '0');
		}
	}
}
