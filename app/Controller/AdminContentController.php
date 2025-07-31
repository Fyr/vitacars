<?php
App::uses('AdminController', 'Controller');
class AdminContentController extends AdminController {
    public $name = 'AdminContent';
    public $components = array('Article.PCArticle');
	public $uses = array('Category', 'Subcategory', 'Brand', 'Form.PMForm');
    public $helpers = array('ObjectType');

    public function index($objectType, $objectID = '') {
    	if ($objectType == 'Brand') {
    		if ( !($this->isAdmin() || AuthComponent::user('view_brands')) ) {
    			$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
				return;
    		}
    	} elseif (!$this->isAdmin()) {
			$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
			return;
		}

    	// $this->loadModel($objectType);
        $this->paginate = array(
			/*
            'Page' => array(
            	'fields' => array('title', 'slug')
            ),
        	'News' => array(
        		'fields' => array('id', 'created', 'title', 'teaser', 'featured', 'published')
        	),
			*/
        	'Category' => array(
				'conditions' => array('is_fake' => 0),
        		'fields' => array('id', 'title', 'sorting', 'export_ru', 'export_by'),
        		'order' => array('Category.sorting' => 'ASC')
        	),
        	'Subcategory' => array(
        		'conditions' => array('Subcategory.object_id' => $objectID),
        		'fields' => array('id', 'title', 'sorting'),
        		'order' => array('Subcategory.sorting' => 'ASC')
        	),
        	'Brand' => array(
        		'fields' => array('id', 'title', 'published', 'is_fake')
        	),
        );

        $data = $this->PCArticle->setModel($objectType)->index();
        $this->set('objectType', $objectType);
        $this->set('objectID', $objectID);

        $this->currMenu = $objectType;
        if ($objectType == 'Subcategory' && $objectID) {
        	$this->set('category', $this->Category->findById($objectID));
        	$this->currMenu = 'Cetegory';
        }

    }

	public function edit($id = 0, $objectType = '', $objectID = '') {
		$this->loadModel('Media.Media');

		// Здесь работаем с моделью Article, т.к. если задавать только $id,
		// непонятно какую модель загружать, чтобы определить $objectType
		$this->loadModel('Article.Article');
		if (!$id) {
			$this->request->data('Article.object_type', $objectType);
			$this->request->data('Article.object_id', $objectID);
		}
		$this->PCArticle->edit($id, $lSaved);
		$objectType = $this->request->data('Article.object_type');
		$objectID = $this->request->data('Article.object_id');

		if ($objectType == 'Brand') {
    		if ( !($this->isAdmin() || AuthComponent::user('view_brands')) ) {
    			$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
				return;
    		}
    	} elseif (!$this->isAdmin()) {
			$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
			return;
		}

		if ($lSaved) {
			if ($objectType == 'Subcategory') {
				// Save form for this subcategory
				$form = $this->PMForm->getObject('Subcategory', $id);
				if (!$form) {
					$this->PMForm->save(array('object_type' => 'Subcategory', 'object_id' => $id));
					$formID = $this->PMForm->id;
				} else {
					$formID = $form['PMForm']['id'];
				}
			}
			if (in_array($objectType, array('Category', 'Subcategory', 'Brand'))) {
				$this->loadModel('Seo.Seo');
				$this->request->data('Seo.object_type', $objectType);
				$this->request->data('Seo.object_id', $id);
				$seo = $this->Seo->getObject($objectType, $id);
				if ($seo) {
					$this->request->data('Seo.id', $seo['Seo']['id']);
				}
				$this->Seo->save($this->request->data);
			}
			if ($objectType == 'Brand') {
				$this->_cleanCache('articles_Brand.xml');
			} else if ($objectType == 'Category') {
				$category = $this->Category->findById($id);
				$this->_cleanCache('product_Categories.xml');
				$this->_cleanProductsCache($category);
			}
			$baseRoute = array('action' => 'index', $objectType, $objectID);
			return $this->redirect(($this->request->data('apply')) ? $baseRoute : array($id));
		}

		$this->currMenu = $objectType;
		if ($objectType == 'Subcategory' && $objectID) {
        	$this->set('category', $this->Category->findById($objectID));
        	$this->currMenu = 'Category';
		}

		if (in_array($objectType, array('Category', 'Subcategory', 'Brand'))) {
			if ($id) {
				$this->loadModel('Seo.Seo');
				$seo = $this->Seo->getObject($objectType, $id);
				if ($seo) {
					$this->request->data('Seo', $seo['Seo']);
				}
			} else {
				$this->request->data('Article.sorting', '0');
			}
		}
	}
}
