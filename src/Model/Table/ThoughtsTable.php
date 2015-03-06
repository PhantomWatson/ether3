<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Collection\Collection;

/**
 * Thoughts Model
 */
class ThoughtsTable extends Table
{

	/**
	 * Initialize method
	 *
	 * @param array $config The configuration for the Table.
	 * @return void
	 */
	public function initialize(array $config)
	{
		$this->table('thoughts');
		$this->displayField('id');
		$this->primaryKey('id');
		$this->addBehavior('Timestamp');
		$this->belongsTo('Users', [
			'foreignKey' => 'user_id'
		]);
		$this->hasMany('Comments', [
			'foreignKey' => 'thought_id'
		]);
	}

	/**
	 * Default validation rules.
	 *
	 * @param \Cake\Validation\Validator $validator instance
	 * @return \Cake\Validation\Validator
	 */
	public function validationDefault(Validator $validator)
	{
		$validator
			->add('id', 'valid', ['rule' => 'numeric'])
			->allowEmpty('id', 'create')
			->add('user_id', 'valid', ['rule' => 'numeric'])
			->allowEmpty('user_id')
			->requirePresence('word', 'create')
			->notEmpty('word')
			->requirePresence('thought', 'create')
			->add('thought', [
				'length' => [
					'rule' => ['minLength', 20],
					'message' => 'That thought is way too short! Please enter at least 20 characters.'
				]
			])
			->requirePresence('color', 'create')
			->notEmpty('color')
			->add('time', 'valid', ['rule' => 'numeric'])
			->requirePresence('time', 'create')
			->notEmpty('time')
			->add('edited', 'valid', ['rule' => 'numeric'])
			->requirePresence('edited', 'create')
			->notEmpty('edited')
			->add('comments_enabled', 'valid', ['rule' => 'numeric'])
			->requirePresence('comments_enabled', 'create')
			->notEmpty('comments_enabled')
			->requirePresence('parsedTextCache', 'create')
			->notEmpty('parsedTextCache')
			->add('cacheTimestamp', 'valid', ['rule' => 'numeric'])
			->requirePresence('cacheTimestamp', 'create')
			->notEmpty('cacheTimestamp')
			->add('anonymous', 'valid', ['rule' => 'boolean'])
			->requirePresence('anonymous', 'create')
			->notEmpty('anonymous');

		return $validator;
	}

	/**
	 * Returns a rules checker object that will be used for validating
	 * application integrity.
	 *
	 * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @return \Cake\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker $rules)
	{
		$rules->add($rules->existsIn(['user_id'], 'Users'));
		return $rules;
	}

	/**
	 * Returns an alphabetized list of all unique thoughtwords
	 * @return array
	 */
	public function getWords() {
		return $this
			->find('all')
			->select(['word'])
			->distinct(['word'])
			->order(['word' => 'ASC'])
			->extract('word')
			->toArray();
	}

	/**
	 * Returns a list of the 300 most-populated thoughtwords and their thought counts
	 * @return array
	 */
	public function getTopCloud() {
		return $this->getCloud(300);
	}

	/**
	 * Returns a list of all thoughtwords and their thought counts
	 * @param int $limit
	 * @return array
	 */
	public function getCloud($limit = false) {
		$query = $this
			->find('list')
			->select([
				'keyField' => 'word',
				'valueField' => 'COUNT(*) as count'
			])
			->group('word')
			->order(['count' => 'DESC']);
		if ($limit) {
			$query->limit($limit);
		}
		$result = $query->toArray();
		ksort($result);
		return $result;
	}

	/**
	 * Returns a count of unique populated thoughtwords
	 * @return int
	 */
	public function getWordCount() {
		return $this
			->find('all')
			->select(['word'])
			->distinct(['word'])
			->count();
	}

	/**
	 * Returns a random populated thoughtword
	 * @return string
	 */
	public function getRandomPopulatedThoughtWord() {
		$result = $this
			->select(['word'])
			->order('RAND()')
			->first();
		return $result['Thought']['word'];
	}
}
