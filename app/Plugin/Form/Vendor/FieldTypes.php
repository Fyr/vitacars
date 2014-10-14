<?
class FieldTypes {
	const STRING  = 1;
	const INT = 2;
	const FLOAT = 3;
	const DATE = 4;
	const DATETIME = 5;
	const TEXTAREA = 6;
	const CHECKBOX = 7;
	const SELECT = 8;
	const EMAIL = 9;
	const URL = 10;
	const UPLOAD_FILE = 11;
	const EDITOR = 12;
	const MULTISELECT = 13;
	const FORMULA = 14;
	
	static public function getTypes() {
		return array(
			self::STRING => __d('form', 'String'),
			self::INT => __d('form', 'Integer'),
			self::FLOAT => __d('form', 'Float'),
			self::DATE => __d('form', 'Date'),
			self::DATETIME => __d('form', 'Datetime'),
			self::TEXTAREA => __d('form', 'Textarea'),
			self::CHECKBOX => __d('form', 'Checkbox'),
			self::SELECT => __d('form', 'Select'),
			self::MULTISELECT => __d('form', 'Multi-select'),
			self::EMAIL => __d('form', 'Email'),
			self::URL => __d('form', 'URL'),
			self::UPLOAD_FILE => __d('form', 'Upload file'),
			self::EDITOR => __d('form', 'Editor'),
			self::FORMULA => __d('form', 'Formula')
		);
	}
}
