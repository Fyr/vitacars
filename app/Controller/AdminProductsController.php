<?php
App::uses('AdminController', 'Controller');
class AdminProductsController extends AdminController {
    public $name = 'AdminProducts';
    public $components = array('Table.PCTableGrid', 'Article.PCArticle');
    public $uses = array('Product', 'Form.PMForm', 'Form.PMFormValue', 'Form.FormField', 'Category', 'Subcategory');
    public $helpers = array('ObjectType', 'Form.PHFormFields');
    
    public function beforeRender() {
    	parent::beforeRender();
    	$this->set('objectType', $this->Product->objectType);
    }
    
    private function _getParamRelation($fieldID) {
    	
    }
    
    public function index() {
    	
    	$aParams = $this->FormField->find('all');
    	$aLabels = array();
    	$aFields = array();
    	$hasOne = array();
    	foreach($aParams as $i => $_field) {
    		$i++;
    		$alias = 'Param'.$i;
    		$hasOne[$alias] = array(
				'className' => 'Form.PMFormValue',
				'foreignKey' => 'object_id',
				'conditions' => array($alias.'.field_id' => $_field['FormField']['id'])
			);
			$aFields[] = $alias.'.value';
			$aLabels[$alias.'.value'] = $_field['FormField']['label'];
    	}
    	$this->set('aLabels', $aLabels);
    	$this->Product->bindModel(array('hasOne' => $hasOne), false);
        $this->paginate = array(
           	'fields' => array_merge(array('title', 'code', 'Media.id', 'Media.object_type', 'Media.file', 'Media.ext'), $aFields)
        );
        $aRowset = $this->PCTableGrid->paginate('Product');
        $this->set('aRowset', $aRowset);
    }
    
	public function edit($id = 0) {
		$this->loadModel('Media.Media');
		if (!$id) {
			$this->request->data('Product.object_type', $this->Product->objectType);
		}
		$this->PCArticle->setModel('Product')->edit(&$id, &$lSaved);
		if ($lSaved) {
			if ($this->request->is('put')) {
				// save product params only for updated product
				$this->PMFormValue->saveForm('ProductParam', $id, 1, $this->request->data('PMFormValue'));
			}
			$baseRoute = array('action' => 'index');
			return $this->redirect(($this->request->data('apply')) ? $baseRoute : array($id));
		}
		
		$this->set('form', $this->PMForm->getFields('ProductParams', 1));
		$this->set('formValues', $this->PMFormValue->getValues('ProductParam', $id));
	}
}
