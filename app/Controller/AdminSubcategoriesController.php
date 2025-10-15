<?php
App::uses('AdminController', 'Controller');
App::uses('Category', 'Model');
App::uses('Subcategory', 'Model');
App::uses('Seo', 'Seo.Model');
class AdminSubcategoriesController extends AdminController {
    public $name = 'AdminSubcategories';
    public $components = array('Article.PCArticle');
	public $uses = array('Subcategory', 'Category', 'Seo.Seo');
    public $helpers = array('ObjectType');

    private $objectType = 'Subcategory';

    public function beforeFilter() {
        $this->currMenu = 'Category';
        $this->set('objectType', $this->objectType);
        $this->PCArticle->setModel($this->objectType);
        parent::beforeFilter();
    }

    public function index($cat_id) {
        $this->paginate = array(
        	'Subcategory' => array(
        		'conditions' => array('cat_id' => $cat_id, 'Subcategory.is_fake' => 0),
                'fields' => array('id', 'title', 'sorting'),
                'order' => array('Subcategory.sorting' => 'ASC')
        	),
        );
        $this->PCArticle->index();
        $this->set('category', $this->Category->findById($cat_id));
    }

	public function edit($cat_id, $id = 0) {
	    $category = $this->Category->findById($cat_id);

	    $this->request->data('Subcategory.cat_id', $cat_id);
	    $this->request->data('Seo.object_type', $this->objectType);
		$this->PCArticle->edit($id, $lSaved);
		if ($lSaved) {
            $this->_cleanProductsCache($category);

			$baseRoute = array('action' => 'index', $cat_id);
			return $this->redirect(($this->request->data('apply')) ? $baseRoute : array($cat_id, $id));
		}

		if (!$id) {
		    $this->request->data('Subcategory.sorting', '0');
		}
		$this->set('category', $category);
	}
}
