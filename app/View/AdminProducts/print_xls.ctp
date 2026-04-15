<?
    $isSplitCross = $this->request->data('isSplitCross');
    $fk_crossNum = 'fk_'.Configure::read('Params.crossNumber');
?>
    <table>
        <thead>
            <tr>
<?
	foreach(array(__('Brand'), __('Category'), __('Subcategory'), __('Title'), __('Title rus'), __('Code'), __('Detail num')) as $label) {
?>
                <th><?=$label?></th>
<?
	}
    foreach ($aLabels as $label) {
        echo '<th>&nbsp;'.$label.'</th>';
    }
?>
            </tr>
        </thead>
        <tbody>
<?php
	$class = 'even';
	foreach ($aRowset as $Product) {
		$class = ($class == 'even') ? 'odd' : 'even';
		$subcat_id = $Product['Product']['subcat_id'];

		$Product['Product']['brand'] = $aBrands[$Product['Product']['brand_id']];
		$Product['Product']['cat'] = $aCategories[$Product['Product']['cat_id']];
		$Product['Product']['subcat'] = (isset($aSubcategories[$subcat_id])) ? $aSubcategories[$subcat_id] : '';

		$crossNums = Hash::get($Product, 'PMFormData.'.$fk_crossNum);
		if ($crossNums && is_array($crossNums)) {
		    // output main product without cross numbers
		    $Product['PMFormData'][$fk_crossNum] = '';
		    echo $this->element('../AdminProducts/_print_row', compact('Product', 'class', 'aLabels', 'aParams'));

            // output products with cross numbers
		    foreach($crossNums as $dn) {
		        // copy product
		        $crossProduct = array(
		            'Product' => $Product['Product'],
		            'PMFormData' => $Product['PMFormData']
		        );

		        if (strpos($dn, ' ') > 0) { // cross number contains brand
		            list($brand, $dn) = explode(' ', $dn);
		            $crossProduct['Product']['brand'] = $brand;
		            $crossProduct['Product']['cat'] = $brand;
		            $crossProduct['Product']['subcat'] = '-';
		        }
                $crossProduct['Product']['code'] = $dn;
                $crossProduct['Product']['detail_num'] = '-';
		        echo $this->element('../AdminProducts/_print_row', array_merge(
		            array('Product' => $crossProduct),
		            compact('class', 'aLabels', 'aParams')
                ));
		    }
		} else {
		    echo $this->element('../AdminProducts/_print_row', compact('Product', 'class', 'aLabels', 'aParams'));
		}
	}
?>
        </tbody>
    </table>
