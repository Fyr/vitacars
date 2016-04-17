<?
App::uses('AppModel', 'Model');
class GpzOffer extends AppModel {
	public $useTable = false;
	
	const FEATURED_ORIGINAL = 1;
	const FEATURED_ANALOG = 2;
	const ORIGINAL = 3;
	const ANALOG = 4;
	
	static public function options() {
		return array(
			self::FEATURED_ORIGINAL => 'Запрошенный номер (cпец. предложения)',
			self::FEATURED_ANALOG => 'Замены (cпец. предложения)',
			self::ORIGINAL => 'Запрошенный номер',
			self::ANALOG => 'Замены (кроссы)'
		);
	}
}
