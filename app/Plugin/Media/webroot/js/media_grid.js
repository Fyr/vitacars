var MediaGrid = function(config) {
	var self = this;

	self.container = config.container;
	$self = $(config.container);

	self.data = [];
	self.settings = {
		model: 'Media',
		primaryKey: 'Media.id',
	};
	self.tab = '';
	self.selected = 0;
	
	this.init = function(config) {
		// load template
		tmpl('media-info', null);
		
		self.setData(config.data);
		self.actions = config.actions;
		self.tab = 'by';
		self.initSelected();
		self.update();
	}

	this.initSelected = function() {
		if (self.data && self.data.length) {
			self.selected = self.getID(self.data[0]);
		} else {
			self.selected = 0;
		}
	}
	
	this.update = function() {
		self.render();
		self.bindEvents();
	}
	
	this.setData = function(data) {
	    self.data = data;
	}
	
	this.getModelField = function(col_key) {
		var field = col_key.split('.');
		return {model: field[0], field: field[1]};
	}

	this.getValue = function(column_key, rowData) {
		if (self.settings.model) {
			var col = self.getModelField(column_key);
			return rowData[col.model][col.field];
		}
		return rowData[column_key];
	}
	
	this.getID = function(rowData) {
		return self.getValue(self.settings.primaryKey, rowData);
	}
	
	this.getDataByID = function(id) {
		for(var i = 0; i < self.data.length; i++) {
	        if (self.getID(self.data[i]) == id) {
	        	return self.data[i];
	        }
	    }
	    return null;
	}
	
	this.render = function() {
		$('.media-thumbs', $self).html(self.renderThumbs());
		if (self.data && self.data.length) {
			self.showInfo(self.selected);
		} else {
			$('.media-info', $self).html(mediaLocale.noData);
		}
	}
	
	this.renderThumbs = function() {
		var html = '';
		if (self.data && self.data.length) {
			for(var i = 0; i < self.data.length; i++) {
			    html+= self.renderThumb(self.data[i]);
			}
		} else {
			html = Format.tag('div', {class: 'alert well-large'}, mediaLocale.noFiles)
		}
		return html;
	}
		
	this.renderThumb = function(rowData) {
		var _class = 'img-rounded pull-left thumb';
		var lang = self.tab;
		if (self.getValue(self.settings.model + '.media_type', rowData) != 'image') {
			_class+= ' non-image';
		}
		if (!self.getValue(self.settings.model + '.show_' + lang, rowData)) {
			_class+= ' non-shown';
		}
		if (self.getValue(self.settings.model + '.main_' + lang, rowData)) {
			_class+= ' main-thumb';
		}
		if (self.getID(rowData) == self.selected) {
			_class+= ' selected';
		}
		return Format.tag('div', 
			{class: _class, 'data-thumb': self.getID(rowData)}, 
			Format.tag('img', {src: self.getValue(self.settings.model + '.image', rowData), alt: ''})
		);
	}

	this.activateTab = function(tab) {
		if (tab) {
			self.tab = tab;
		} else {
			tab = self.tab;
		}

		var context = $('.media-info');
		$('ul.nav.nav-tabs > li', context).removeClass('active');
		$('ul.nav.nav-tabs > #tab-' + tab, context).addClass('active');
		$('.media-tab-content', context).hide();
		$('#media-tab-content-' + tab, context).show();
	}
	
	this.bindEvents = function() {
	    self.bindSelectImage();
	}
	
	this.bindSelectImage = function() {
		$('.media-thumbs .thumb', $self).click(function(){
			// $('.thumb').removeClass('selected');
			// $(this).addClass('selected');
			var id = $(this).data('thumb');
			self.selected = id;
			// self.showInfo(id);
			self.update();
		});
		// $('.media-thumbs .thumb:first', $self).click();
	}
	
	this.showInfo = function(id) {
		var rowData = self.getDataByID(id);
		self.renderInfo(rowData[self.settings.model]);
		self.bindInfo(rowData);
		self.updateImageURL(id);
		self.activateTab();
	}
	
	this.renderInfo = function(rowData) {
		$('.media-info', $self).html(tmpl('media-info', rowData));
		// var size = $('#media-width', $self).val() + 'x' + $('#media-height', $self).val();
	}
	
	this.updateImageURL = function(id) {
		var rowData = self.getDataByID(id);
		var w = $('#media-w', $self).val();
		var h = $('#media-h', $self).val();
		var size = (w || h) ?  w + 'x' + h : 'noresize';
		$('#media-url', $self).val(self.getImageURL(rowData[self.settings.model], size));
	}
	
	this.getImageURL = function(rowData, size) {
		return '/media/router/index/' + rowData.object_type.toLowerCase() + '/' + rowData.id + '/' + size + '/' + rowData.file + rowData.ext;
	}
	
	this.bindInfo = function(rowData) {
		var context = $('.media-info');
		$('ul.nav.nav-tabs > li > a', context).click(function(){
			var tab = $(this, context).parent().get(0).id.replace(/tab\-/, '');
			self.activateTab(tab);
			self.update();
		});
	}
	
	this.getActionURL = function(url, id, lang) {
		var url = url.replace(/\{\$id\}/ig, id);
		if (lang) {
			url = url.replace(/\{\$lang\}/ig, lang);
		}
		return url;
	}
	
	this.actionDelete = function(id) {
		$.get(self.getActionURL(self.actions.delete, id), null, function(response) {
			if (checkJson(response)) {
	            mediaGrid.setData(response.data);
				self.initSelected();
	            mediaGrid.update();
		    }
		});
	}
	
	this.actionSetMain = function(id, lang) {
		$.get(self.getActionURL(self.actions.setMain, id, lang), null, function(response) {
			if (checkJson(response)) {
	            mediaGrid.setData(response.data);
	            mediaGrid.update();
		    }
		});
	}

	this.actionUpdate = function(id, data) { // id, lang, value
		// data = {data: {id: id, alt: value, lang: lang}};
		$('.media-alt').hide();
		$('.media-alt-loader').show();
		$.post(self.getActionURL(self.actions.update, id), {data: data}, function(response) {
			if (checkJson(response)) {
				mediaGrid.setData(response.data);
				mediaGrid.update();
				$('.media-alt-loader').hide();
				$('.media-alt').show();
			}
		});
	}

	self.init(config);
}