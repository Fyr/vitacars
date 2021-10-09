Date.MINUTE = 60 * 1000;
Date.HOUR = Date.MINUTE * 60;
Date.DAY = Date.HOUR * 24;

Date.timeDays = function(days) {
	return 86400 * 1000 * days;
};

Date.fromSqlDate = function(mysql_string) { 
	if(typeof mysql_string === 'string')    {
		var t = mysql_string.split(/[- :]/);
		return new Date(t[0], t[1] - 1, t[2], t[3] || 0, t[4] || 0, t[5] || 0);          
	}
	return null;   
};
Date.prototype.toSqlDate = function() { 
	return this.getFullYear() + '-' + zeroFormat(this.getMonth() + 1) + '-' + zeroFormat(this.getDate());
};
Date.prototype.toSqlDateTime = function() { 
	return this.getFullYear() + '-' + zeroFormat(this.getMonth() + 1) + '-' + zeroFormat(this.getDate()) + ' ' + 
		zeroFormat(this.getHours()) + ':' + zeroFormat(this.getMinutes()) + ':' + zeroFormat(this.getSeconds());
};
Date.prototype.hoursMinutes = function(locale, sec) {
	var hours = this.getHours();
	if (locale && locale == 'rus') {
		return zeroFormat(hours) + ':' + zeroFormat(this.getMinutes());
	}
	return zeroFormat((hours > 12) ? hours - 12 : hours) + ':' + zeroFormat(this.getMinutes()) + ((hours >= 12) ? 'pm' : 'am');
};
Date.prototype.fullDate = function(locale) {
	if (locale && locale == 'rus') {
		return zeroFormat(this.getDate()) + '.' + zeroFormat(this.getMonth() + 1) + '.' + this.getFullYear();
	}
	return zeroFormat(this.getMonth() + 1) + '/' + zeroFormat(this.getDate()) + '/' + this.getFullYear();
};
Date.prototype.addDays = function(days) {
	this.setTime(this.getTime() + Date.timeDays(days));
	return this;
};
Date.local2sql = function(localDate) {
	if (localDate.indexOf('.') > 0) {
		var d = localDate.split('.');
		return d[2] + '-' + d[1] + '-' + d[0];
	}
	var d = localDate.split('/');
	return d[2] + '-' + d[0] + '-' + d[1];
};
Date.getPeriod = function(time, locale) {
	var d = new Date();
	d.setTime(time * 1000 + (d.getUTCHours() - d.getHours()) * Date.HOUR);
	return d.hoursMinutes('rus') + ':' + zeroFormat(d.getSeconds());
};
function zeroFormat(n) {
	n = parseInt(n);
	return (n >= 10) ? '' + n : '0' + n;
}