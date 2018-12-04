<?php
namespace App\Model\Table;

use App\Model\Entity\Thought;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use League\HTMLToMarkdown\HtmlConverter;

/**
 * Comments Model
 *
 * @property \App\Model\Table\ThoughtsTable|\Cake\ORM\Association\BelongsTo $Thoughts
 * @property \App\Model\Table\UsersTable|\Cake\ORM\Association\BelongsTo $Users
 * @method \App\Model\Entity\Comment get($primaryKey, $options = [])
 * @method \App\Model\Entity\Comment newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Comment[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Comment|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Comment patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Comment[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Comment findOrCreate($search, callable $callback = null, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CommentsTable extends Table
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        $this->setTable('comments');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Thoughts', [
            'foreignKey' => 'thought_id'
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id'
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
            ->add('thought_id', 'valid', ['rule' => 'numeric'])
            ->requirePresence('thought_id', 'create')
            ->notEmpty('thought_id')
            ->add('thought_id', 'comments_enabled', [
                'rule' => function ($value, $context) {
                    $thoughtsTable = TableRegistry::getTableLocator()->get('Thoughts');
                    /** @var Thought $thought Thought record */
                    $thought = $thoughtsTable->find()
                        ->select(['comments_enabled'])
                        ->where(['id' => $value])
                        ->first();

                    return $thought && $thought->comments_enabled;
                },
                'message' => 'That thought has comments disabled'
            ])
            ->add('user_id', 'valid', ['rule' => 'numeric'])
            ->requirePresence('user_id', 'create')
            ->notEmpty('user_id')
            ->requirePresence('comment', 'create')
            ->notEmpty('comment');

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
        $rules->add($rules->existsIn(['thought_id'], 'Thoughts'));
        $rules->add($rules->existsIn(['user_id'], 'Users'));
        return $rules;
    }

    /**
     * Removs slashes that were a leftover of the anti-injection-attack strategy of the olllllld Ether
     */
    public function overhaulStripSlashes()
    {
        $comments = $this->find('all')
            ->select(['id', 'comment'])
            ->where(['comment LIKE' => '%\\\\\'%'])
            ->order(['id' => 'ASC']);
        foreach ($comments as $comment) {
            echo $comment->comment;
            $fixed = stripslashes($comment->comment);
            $comment->comment = $fixed;
            $this->save($comment);
            echo " => $fixed<br />";
        }
    }

    public function overhaulToMarkdown()
    {
        $field = 'comment';
        $results = $this->find('all')
            ->select(['id', $field])
            ->where([
                "$field LIKE" => '%<%',
                'markdown' => false
            ])
            ->order(['id' => 'ASC']);
        if ($results->count() == 0) {
            echo "No {$field}s to convert";
        }
        foreach ($results as $result) {
            $converter = new HtmlConverter(['strip_tags' => false]);
            $markdown = $converter->convert($result->$field);
            $result->$field = $markdown;
            $result->markdown = true;
            if ($this->save($result)) {
                echo "Converted $field #$result->id<br />";
            } else {
                echo "ERROR converting $field #$result->id<br />";
            }
        }
    }
}
