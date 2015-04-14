<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

/**
 * Thought Entity.
 */
class Thought extends Entity
{

	/**
	 * Fields that can be mass assigned using newEntity() or patchEntity().
	 *
	 * @var array
	 */
	protected $_accessible = [
		'user_id' => true,
		'word' => true,
		'thought' => true,
		'color' => true,
		'time' => true,
		'edited' => true,
		'comments_enabled' => true,
		'parsedTextCache' => true,
		'cacheTimestamp' => true,
		'anonymous' => true,
		'user' => true,
		'comments' => true,
		'parsed' => true
	];
	public $max_thoughtword_length = 30;

	public function _getParsedTextCache($parsedTextCache) {
		if ($parsedTextCache == '') {
			$thoughts = TableRegistry::get('Thoughts');
			$parsedTextCache = $thoughts->linkThoughtwords($this->_properties['thought']);
			$thought = $thoughts->find('all')->where(['id' => $this->_properties['id']])->first();
			$thought->parsedTextCache = $parsedTextCache;
			$thought->parsed = date('Y-m-d H:i:s');
			$thoughts->save($thought);
		}
		return $parsedTextCache;
	}
}
