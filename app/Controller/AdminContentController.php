<?php
App::uses('AdminController', 'Controller');
class AdminContentController extends AdminController {
    public $name = 'AdminContent';
    public $components = array('Article.PCArticle');
    public $uses = array('Category', 'Subcategory', 'Brand', 'Form.FormField', 'Form.PMForm');
    public $helpers = array('ObjectType');
    
    public function beforeFilter() {
		if (!$this->isAdmin()) {
			$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
			return;
		}
		parent::beforeFilter();
	}
    
    public function index($objectType, $objectID = '') {
    	// $this->loadModel($objectType);
        $this->paginate = array(
            'Page' => array(
            	'fields' => array('title', 'slug')
            ),
        	'News' => array(
        		'fields' => array('id', 'created', 'title', 'teaser', 'featured', 'published')
        	),
        	'Category' => array(
        		'fields' => array('id', 'title', 'sorting'),
        		'order' => array('Category.sorting' => 'ASC')
        	),
        	'Subcategory' => array(
        		'conditions' => array('Subcategory.object_id' => $objectID),
        		'fields' => array('id', 'title', 'sorting'),
        		'order' => array('Subcategory.sorting' => 'ASC')
        	),
        	'Brand' => array(
        		'fields' => array('id', 'title')
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
		$this->PCArticle->edit(&$id, &$lSaved);
		$objectType = $this->request->data('Article.object_type');
		$objectID = $this->request->data('Article.object_id');
		
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
				
				// по моему это не нужно
				$fields = $this->request->data('FormKey.field_id');
				$this->PMForm->bindFields($formID, ($fields) ? explode(',', $fields) : array());
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
			$baseRoute = array('action' => 'index', $objectType, $objectID);
			return $this->redirect(($this->request->data('apply')) ? $baseRoute : array($id));
		}
		
		$this->currMenu = $objectType;
		if ($objectType == 'Subcategory' && $objectID) {
        	$this->set('category', $this->Category->findById($objectID));
        	$this->currMenu = 'Category';
        	
			$this->paginate = array(
	    		'fields' => array('field_type', 'label', 'fieldset', 'required'),
	    		'limit' => 100
	    	);
	    	$this->PCTableGrid->paginate('FormField');
	    	
	    	$formKeys = array();
	    	if ($id) {
	    		$form = $this->PMForm->getObject('Subcategory', $id);
	    		$formKeys = $this->PMForm->getFormKeys(Hash::get($form, 'PMForm.id'));
	    	}
	    	$this->set('formKeys', $formKeys);
		}
		
		if ($id && in_array($objectType, array('Category', 'Subcategory', 'Brand'))) {
			$this->loadModel('Seo.Seo');
			$seo = $this->Seo->getObject($objectType, $id);
			if ($seo) {
				$this->request->data('Seo', $seo['Seo']);
			}
			// $this->request->data('Seo.title', Hash::get($seo, 'Seo.title');
				
		}
	}
}
