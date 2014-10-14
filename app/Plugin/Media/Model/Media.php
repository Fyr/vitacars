<?php
App::uses('AppModel', 'Model');
class Media extends AppModel {
    const MKDIR_MODE = 0755;
    
    public $types = array('image', 'audio', 'video', 'bin_file');
    protected $PHMedia;

    protected function _afterInit() {
    	App::uses('MediaPath', 'Media.Vendor');
		$this->PHMedia = new MediaPath();
    }
    
    /*public function beforeSave($options = array()) {
        
    }
    */
    
    public function getPHMedia() {
    	return $this->PHMedia;
    }
    
    /**
     * Uploades media file into auto-created folder
     *
     * @param array $data - array. Must contain elements: 'media_type', 'object_type', 'object_id', 'tmp_name', 'file', 'ext'
     *                      tmp_name - temp file to rename to media folders
     *                      file.ext - final name of file
     */
    public function uploadMedia($data) {
    	$this->clear();
		$this->save($data);
		$id = $this->id;
		
		extract($data);
		
		// Create folders if not exists
		$path = $this->PHMedia->getTypePath($object_type);
		if (!file_exists($path)) {
		    mkdir($path, self::MKDIR_MODE);
		}
		$path = $this->PHMedia->getPagePath($object_type, $id);
		if (!file_exists($path)) {
		    mkdir($path, self::MKDIR_MODE);
		}
		$path = $this->PHMedia->getPath($object_type, $id);
		if (!file_exists($path)) {
			mkdir($path, self::MKDIR_MODE);
		}
		
		if (isset($real_name)) {
			copy($real_name, $path.$file.$ext);
			$res = false;
		} else {
			// TODO: handle rename error
			$res = rename($tmp_name, $path.$file.$ext);
		}
		if ($res) {
		    // remove auto-thumb
		    $path = pathinfo($tmp_name);
		    @unlink($path['dirname'].'/thumbnail/'.$path['basename']);
		}
		
		if (!isset($media_type) || $media_type == 'image') {
			// Save original image resolution and file size
			$file = $this->PHMedia->getFileName($object_type, $id, null, $file.$ext);
			
			App::uses('Image', 'Media.Vendor');
			$image = new Image();
			$image->load($file);
			$this->save(array('id' => $id, 'orig_w' => $image->getSizeX(), 'orig_h' => $image->getSizeY(), 'orig_fsize' => filesize($file)));
			
			// Set main image if it was first image
			$this->initMain($object_type, $object_id);
		}
		
		return $id;
    }
    
    /**
     * Return list of media data with additional stats
     *
     * @param array $findData - conditions
     * @param mixed $order
     * @return array
     */
    public function getList($findData = array(), $order = array('Media.main' => 'DESC', 'Media.id' => 'DESC')) {
        $aRows = $this->find('all', array('conditions' => $findData, 'order' => $order));
        foreach($aRows as &$_row) {
            $row = $_row[$this->alias];
            if ($row['media_type'] == 'image') {
            	$_row[$this->alias]['image'] = $this->PHMedia->getImageUrl($row['object_type'], $row['id'], '100x80', $row['file'].$row['ext']);
            } elseif ($row['ext'] == '.pdf') {
            	$_row[$this->alias]['image'] = '/media/img/pdf.png';
            } else {
            	$_row[$this->alias]['image'] = '/media/img/'.$row['media_type'].'.png';
            }
            $_row[$this->alias]['url_download'] = $this->PHMedia->getRawUrl($row['object_type'], $row['id'], $row['file'].$row['ext']);
        }
        return $aRows;
    }
    
    /*
    public function typeOf($mediaRow) {
        return (isset($mediaRow[$this->alias]) && isset($mediaRow[$this->alias]['media_type'])) ? $mediaRow[$this->alias]['media_type'] : '';
    }
    */
	
    /**
     * Set main image
     *
     * @param unknown_type $id
     * @param unknown_type $object_type
     * @param unknown_type $object_id
     */
	public function setMain($id , $object_type = null, $object_id = null) {
		// Clear main flag for all media
		if ($object_id && $object_type) {
			$conditions = compact('object_type', 'object_id');
			$conditions['media_type'] = 'image';
			$this->updateAll(array('main' => 0), $conditions);
		} else {
			$media = $this->findById($id);
			$this->setMain($id, $media[$this->alias]['object_type'], $media[$this->alias]['object_id']);
			return;
		}
		$this->save(array('id' => $id, 'main' => 1));
	}
	
	/**
	 * Set main image for media
	 *
	 * @param str $object_type
	 * @param int $object_id
	 */
	public function initMain($object_type, $object_id) {
		$media = $this->find('first', array(
			'conditions' => array('object_type' => $object_type, 'object_id' => $object_id, 'media_type' => 'image'),
			'order' => array('main' => 'DESC', 'id'  => 'ASC')
		));
		if ($media) {
			// we have some media records but no main 
			if (!$media[$this->alias]['main']) {
				$media[$this->alias]['main'] = 1;
				$this->save($media);
			}
		} // no records
	}
	
    /**
     * Removes actual media-files before delete a record
     *
     * @param bool $cascade
     * @return bool
     */
	public function beforeDelete($cascade = true) {
		App::uses('Path', 'Core.Vendor');
		
		$media = $this->findById($this->id);
		$path = $this->PHMedia->getPath($media[$this->alias]['object_type'], $this->id);

		if (file_exists($path)) {
			// remove all files in folder
			$aPath = Path::dirContent($path);
			if (isset($aPath['files']) && $aPath['files']) {
				foreach($aPath['files'] as $file) {
					unlink($aPath['path'].$file);
				}
			}
			rmdir($path);
		}
		return true;
	}
}
