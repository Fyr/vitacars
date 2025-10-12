<?
    if ($this->request->data('Seo.id')) {
        echo $this->PHForm->hidden('Seo.id');
    }
	echo $this->PHForm->input('Seo.title', array('type' => 'text'));
	echo $this->PHForm->input('Seo.keywords', array('type' => 'text'));
	echo $this->PHForm->input('Seo.descr', array('type' => 'text'));
