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
	
	static public function getTypes() {
		return array(
			self::STRING => __('String'),
			self::INT => __('Integer'),
			self::FLOAT => __('Float'),
			self::DATE => __('Date'),
			self::DATETIME => __('Datetime'),
			self::TEXTAREA => __('Textarea'),
			self::CHECKBOX => __('Checkbox'),
			self::SELECT => __('Select'),
			self::EMAIL => __('Email'),
			self::URL => __('URL'),
			self::UPLOAD_FILE => __('Upload file'),
			self::EDITOR => __('Editor')
		);
	}
}
