<?php
App::uses('AppHelper', 'View/Helper');
class ObjectTypeHelper extends AppHelper {
    public $helpers = array('Html');
    
    private function _getTitles() {
        $Titles = array(
            'index' => array(
                'Article' => __('Articles'),
                'Page' => __('Static pages'),
                'News' => __('News'),
                'Category' => __('Categories'),
                'Subcategory' => __('Subcategories'),
                'Product' => __('Products'),
                'FormField' => __('Tech.params'),
                'FormConst' => __('Constants'),
                'User' => __('Users'),
                'Brand' => __('Brands'),
                'Order' => __('Orders'),
            ), 
            'create' => array(
                'Article' => __('Create Article'),
                'Page' => __('Create Static page'),
                'News' => __('Create News article'),
                'Category' => __('Create Category'),
                'Subcategory' => __('Create Subcategory'),
                'Product' => __('Create Product'),
                'FormField' => __('Create tech.param'),
                'FormConst' => __('Create constant'),
                'User' => __('Create User'),
                'Brand' => __('Create Brand'),
                'Order' => __('Upload Order'),
            ),
            'edit' => array(
                'Article' => __('Edit Article'),
                'Page' => __('Edit Static page'),
                'News' => __('Edit News article'),
                'Category' => __('Edit Category'),
                'Subcategory' => __('Edit Subcategory'),
                'Product' => __('Edit Product'),
                'FormField' => __('Edit tech.param'),
                'FormConst' => __('Edit constant'),
                'User' => __('Edit User'),
                'Brand' => __('Edit Brand'),
                'Order' => __('Edit Order'),
            )
        );
        return $Titles;
    }
    
    public function getTitle($action, $objectType) {
        $aTitles = $this->_getTitles();
        return (isset($aTitles[$action][$objectType])) ? $aTitles[$action][$objectType] : $aTitles[$action]['Article'];
    }
    
    public function getBaseURL($objectType, $objectID = '') {
        return $this->Html->url(array('action' => 'index', $objectType, $objectID));
    }
}