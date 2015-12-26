<?php
namespace App\Model\Table;

use Cake\Cache\Cache;
use Cake\Collection\Collection;
use Cake\Event\Event;
use Cake\Log\Log;
use Cake\Network\Exception\BadRequestException;
use Cake\Network\Exception\InternalErrorException;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use League\CommonMark\CommonMarkConverter;
use League\HTMLToMarkdown\HtmlConverter;
use MarkovPHP;

/**
 * Thoughts Model
 */
class ThoughtsTable extends Table
{

    public $maxThoughtwordLength = 30;

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
        $this->addBehavior('Gourmet/CommonMark.CommonMark');
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
            ->add('comments_enabled', 'valid', ['rule' => 'boolean'])
            ->add('anonymous', 'valid', ['rule' => 'boolean']);

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
     * Returns an alphabetized list of all unique populated thoughtwords
     * @return array
     */
    public function getWords()
    {
        return Cache::remember('populatedThoughtwords', function () {
            $populatedThoughtwords = $this
                ->find('all')
                ->select(['word'])
                ->distinct(['word'])
                ->order(['word' => 'ASC'])
                ->extract('word')
                ->toArray();
            $populatedThoughtwordHash = md5(serialize($populatedThoughtwords));
            Cache::write('populatedThoughtwordHash', $populatedThoughtwordHash);
            return $populatedThoughtwords;
        });
    }

    public function getPopulatedThoughtwordHash()
    {
        return Cache::remember('populatedThoughtwordHash', function () {
            $populatedThoughtwords = $this->getWords();
            return md5(serialize($populatedThoughtwords));
        });
    }

    /**
     * Returns a list of the 300 most-populated thoughtwords and their thought counts
     * @return array
     */
    public function getTopCloud()
    {
        return $this->getCloud(300);
    }

    /**
     * Returns a list of all thoughtwords and their thought counts
     * @param int $limit
     * @return array
     */
    public function getCloud($limit = false)
    {
        return Cache::remember('thoughtwordCloud', function () use ($limit) {
            $query = $this->find('list', [
                    'keyField' => 'word',
                    'valueField' => 'count'
                ])
                ->select([
                    'word',
                    'count' => $this->find()->func()->count('*')
                ])
                ->group('word')
                ->order(['count' => 'DESC']);
            if ($limit) {
                $query->limit($limit);
            }
            $result = $query->toArray();
            ksort($result);
            return $result;
        }, 'long');
    }

    /**
     * Returns a count of unique populated thoughtwords
     * @return int
     */
    public function getWordCount()
    {
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
    public function getRandomPopulatedThoughtWord()
    {
        return $this->find('all')
            ->select(['word'])
            ->order('RAND()')
            ->first()
            ->word;
    }

    /**
     * Returns a random thought
     * @return Entity
     */
    public function getRandomThought()
    {
        $allThoughtIds = $this->getAllIds();
        $key = array_rand($allThoughtIds);
        $thoughtId = $allThoughtIds[$key];
        $thought = $this->find('all')
            ->select(['id', 'word', 'thought', 'formatted_thought', 'anonymous', 'formatting_key'])
            ->where(['Thoughts.id' => $thoughtId])
            ->contain([
                'Users' => function ($q) {
                    return $q->select(['id', 'color']);
                }
            ])
            ->first();

        // Generate or refresh formatted_thought if necessary and save result
        if (empty($thought->formatted_thought) || empty($thought->formatting_key) || $thought->formatting_key != $this->getPopulatedThoughtwordHash()) {
            $thought->formatted_thought = $this->formatThought($thought->thought);
            $this->save($thought);
        }

        return $thought;
    }

    /**
     * Returns the beginning 300 characters of a thought for the front
     * page "random thought", with all tags but bold and italics removed.
     *
     * @param Entity $thought
     * @return Entity $thought
     */
    public function excerpt($thought)
    {
        $t = $thought->formatted_thought;

        // Replace breaks with spaces to avoid "First line.Second line."
        $t = str_replace(['<p>', '</p>'], '', $t);
        $t = str_replace(['<br />', '<br>'], ' ', $t);

        $allowedTags = '<i><b><em><strong>';
        $t = strip_tags($t, $allowedTags);

        $t = Text::truncate($t, 300, [
            'html' => true,
            'exact' => false
        ]);
        $t = trim($t);

        $thought->formatted_thought = $t;

        return $thought;
    }

    /**
     * Returns an array of ['first letter' => [words beginning with that letter], ...]
     * @return array
     */
    public function getAlphabeticallyGroupedWords()
    {
        $words = $this->getWords();
        $categorized = [];
        foreach ($words as $word) {
            $first_letter = substr($word, 0, 1);
            if (is_numeric($first_letter)) {
                $categorized['#'][] = $word;
            } else {
                $categorized[$first_letter][] = $word;
            }
        }
        ksort($categorized);
        return $categorized;
    }

    /**
     * Used to get paginated thoughts and comments combined
     * @param Query $query
     * @param array $options
     * @return Query
     */
    public function findRecentActivity(Query $query, array $options)
    {
        $combinedQuery = $this->getThoughtsAndComments();
        $limit = 10;
        $offset = $query->clause('offset');
        $direction = isset($_GET['direction']) ? strtolower($_GET['direction']) : 'desc';
        if (! in_array($direction, ['asc', 'desc'])) {
            throw new BadRequestException('Invalid sorting direction');
        }
        $combinedQuery->epilog("ORDER BY created $direction LIMIT $limit OFFSET $offset");
        $combinedQuery->counter(function ($query) {
            $comments = TableRegistry::get('Comments');
            return $comments->find('all')->count() + $this->find('all')->count();
        });
        return $combinedQuery;
    }

    public function getThoughtsAndComments()
    {
        $thoughts = TableRegistry::get('Thoughts');
        $thoughtsQuery = $thoughts->find('all');
        $thoughtsQuery
            ->select([
                'created' => 'Thoughts.created',
                'thought_id' => 'Thoughts.id',
                'thought_word' => 'Thoughts.word',
                'thought_anonymous' => 'Thoughts.anonymous',
                'comment_id' => 0
            ])
            ->contain([
                'Users' => [
                    'fields' => ['id', 'color']
                ]
            ]);

        $comments = TableRegistry::get('Comments');
        $commentsQuery = $comments
            ->find('all')
            ->select([
                'created' => 'Comments.created',
                'thought_id' => 'Thoughts.id',
                'thought_word' => 'Thoughts.word',
                'thought_anonymous' => 'Thoughts.anonymous',
                'comment_id' => 'Comments.id'
            ])
            ->contain([
                'Users' => [
                    'fields' => ['id', 'color']
                ]
            ])
            ->join([
                'table' => 'thoughts',
                'alias' => 'Thoughts',
                'conditions' => 'Comments.thought_id = Thoughts.id'
            ]);
        return $thoughtsQuery->unionAll($commentsQuery);
    }

    /**
     * Converts $word into a valid thoughtword (alphanumeric, lowercase, no spaces, max length enforced)
     * @param string $word
     * @return string
     */
    public function formatThoughtword($word)
    {
        $word = preg_replace('/[^a-zA-Z0-9]/', '', $word);
        if (strlen($word) > $this->maxThoughtwordLength) {
            $word = substr($word, 0, $this->maxThoughtwordLength);
        }
        return strtolower($word);
    }

    /**
     * Checks to see if the thought in $this->request->data is already in the database
     * @return int|boolean Either the ID of the existing thought or FALSE
     */
    public function isDuplicate($userId, $thought)
    {
        $results = $this
            ->findByUserIdAndThought($userId, $thought)
            ->select(['id'])
            ->order(['Thought.created' => 'DESC'])
            ->first()
            ->toArray();
        return isset($results['Thought']['id']) ? $results['Thought']['id'] : false;
    }

    public function getFromWord($word)
    {
        return $this->find('all')
            ->select(['id', 'user_id', 'word', 'thought', 'comments_enabled', 'formatted_thought', 'formatting_key', 'anonymous', 'created', 'modified'])
            ->where(['word' => $word])
            ->order(['Thoughts.created' => 'DESC'])
            ->contain([
                'Users' => function ($q) {
                    return $q->select(['id', 'color']);
                },
                'Comments' => function ($q) {
                    return $q
                        ->select(['id', 'thought_id', 'user_id', 'comment', 'formatted_comment', 'formatting_key'])
                        ->contain([
                            'Users' => function ($q) {
                                return $q->select(['id', 'color']);
                            }
                        ])
                        ->order(['Comments.created' => 'ASC']);
                },
            ])
            ->toArray();
    }

    /**
     * Convert the user-entered contents of a thought to what will
     * be displayed (with Markdown to HTML, thoughtwords linked, etc.)
     * @param string $thought
     * @return string
     */
    public function formatThought($thought)
    {
        // Remove all HTML added by the user
        $thought = $this->stripTags($thought, true);

        // Convert Markdown to HTML, then strip all tags not whitelisted
        $thought = $this->parseMarkdown($thought);
        $thought = $this->stripTags($thought);

        $thought = $this->linkThoughtwords($thought);
        $thought = $this->addWordBreaks($thought);
        return $thought;
    }

    public function parseMarkdown($input)
    {
        $converter = new CommonMarkConverter();
        return $converter->convertToHtml($input);
    }

    public function stripTags($input, $allTags = false)
    {
        $allowedTags = '<i><b><em><strong><ul><ol><li><p><br><wbr><blockquote>';
        if ($allTags) {
            return strip_tags($input);
        }
        return strip_tags($input, $allowedTags);
    }

    /**
     * Returns $input with links around every thoughtword
     * @param string $input
     * @return string
     */
    public function linkThoughtwords($input)
    {
        $thoughtwords = $this->getWords();
        $input = stripslashes($input); // Unnecessary after slashes are stripped out of the database
        $trimPattern = "/(^[^a-zA-Z0-9]+|[^a-zA-Z0-9]+$)/"; // Pattern used to isolate leading/trailing non-alphanumeric characters
        $nonalphanumericPattern = '/[^a-zA-Z0-9]/';
        $whitespaceAndTagsPattern = "/( |\n|\r|<i>|<\/i>|<b>|<\/b>)/";
        $entirelyNonalphanumericPattern = '/^[^a-zA-Z0-9]+$/';
        $formattedText = '';
        $textBroken = preg_split($whitespaceAndTagsPattern, $input, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($textBroken as $n => $textChunk) {
            // Chunk is a delimiter
            if (preg_match($whitespaceAndTagsPattern, $textChunk)) {
                $formattedText .= $textChunk;
                continue;
            }

            // Lowercase, alphanumeric-only version of chunk
            $word = strtolower(preg_replace($nonalphanumericPattern, "", $textChunk));

            // Chunk is ineligible for linking
            if (! in_array($word, $thoughtwords)) {
                $formattedText .= $textChunk;
                continue;
            }

            $url = Router::url(['controller' => 'Thoughts', 'action' => 'word', $word, 'plugin' => false]);

            // Thoughtword is intact inside chunk
            // (So leave leading/trailing non-alphanumeric character out of link)
            $stripos = stripos($textChunk, $word);
            if ($stripos !== false) {
                $unformattedWord = substr($textChunk, $stripos, strlen($word));
                $formattedText .= str_replace(
                    $unformattedWord,
                    '<a href="'.$url.'" class="thoughtword">'.$unformattedWord.'</a>',
                    $textChunk
                );
                continue;
            }

            // Thoughtword is broken up (such as the word 'funhouse' is broken up in 'fun-house')
            // (So include intervening non-alphanumeric characters in link, but not leading/trailing)
            $splitChunk = preg_split($trimPattern, $textChunk, -1, PREG_SPLIT_DELIM_CAPTURE);

            // Removes empty subchunks
            foreach ($splitChunk as $key => $subchunk) {
                if ($subchunk == '') {
                    unset($splitChunk[$key]);
                }
            }
            $splitChunk = array_values($splitChunk); // Resets keys
            $lastKey = count($splitChunk) - 1;

            // If the chunk of text LEADS with non-alphanumeric characters, don't include them in the link.
            $firstChunk = $splitChunk[0];
            if ($leadingCharacters = preg_match($entirelyNonalphanumericPattern, $firstChunk)) {
                $formattedText .= $firstChunk;
                array_shift($splitChunk);
                $lastKey--;
            }

            // If the chunk of text ENDS with non-alphanumeric characters, don't include them in the link.
            $lastChunk = $splitChunk[$lastKey];
            if ($trailingCharacters = preg_match($entirelyNonalphanumericPattern, $lastChunk)) {
                array_pop($splitChunk);
            }

            $linkedChunk = ($leadingCharacters || $trailingCharacters) ? implode("", $splitChunk) : $textChunk;
            $formattedText .= '<a href="'.$url.'" class="thoughtword">'.$linkedChunk.'</a>';

            if ($trailingCharacters) {
                $formattedText .= $lastChunk;
            }
        }
        return $formattedText;
    }

    public function addWordBreaks($input)
    {
        $whitespaceAndTagsPattern = "/( |\n|\r|<[^>]*>)/";
        $output = '';
        $textBroken = preg_split($whitespaceAndTagsPattern, $input, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($textBroken as $n => $textChunk) {
            if ($textChunk == '') {
                continue;
            } elseif ($textChunk[0] == '<') {
                $output .= $textChunk;
            } elseif (strlen($textChunk) > $this->maxThoughtwordLength) {
                $output .= chunk_split($textChunk, $this->maxThoughtwordLength, "<wbr />");
            } else {
                $output .= $textChunk;
            }
        }

        return $output;
    }

    public function afterDelete($event, $entity, $options = [])
    {
        $event = new Event('Model.Thought.deleted', $this, compact('entity', 'options'));
        $this->eventManager()->dispatch($event);
    }

    public function getAuthorId($thoughtId)
    {
        return $this->get($thoughtId, [
            'fields' => ['user_id']
        ])->user_id;
    }

    public function getCount()
    {
        return $this->find('all')->count();
    }

    public function getPopulation($word)
    {
        return $this->find('all')->where(['word' => $word])->count();
    }

    /**
     * Finds a batch of thoughts with out-of-date formatting
     * (e.g. because of newly-populated thoughtwords)
     *
     * @param int $limit
     * @return array
     */
    public function getThoughtsForReformatting($limit = null)
    {
        $populatedThoughtwordHash = $this->getPopulatedThoughtwordHash();
        return $this
            ->find('all')
            ->select(['id', 'thought'])
            ->where([
                'OR' => [
                    function ($exp, $q) {
                        return $exp->isNull('formatting_key');
                    },
                    'formatting_key IS NOT' => $populatedThoughtwordHash
                ]
            ])
            ->limit($limit)
            ->order(['created' => 'DESC']);
    }

    /**
     * Collects a batch of $limit thoughts with out-of-date formatting
     * and updates them.
     *
     * @param int|null $limit
     */
    public function reformatStaleThoughts($limit = null)
    {
        $query = $this->getThoughtsForReformatting($limit);
        if ($query->count() === 0) {
            Log::write('info', 'No stale thoughts found.');
            return;
        }

        foreach ($query as $thought) {
            $thought->formatted_thought = $this->formatThought($thought->thought);
            // Thoughts.formatting_key automatically set by Thought::_setFormattedThought()
            $this->save($thought);
            Log::write('info', 'Refreshed formatting for thought '.$thought->id);
        }
    }

    /**
     * Removs slashes that were a leftover of the anti-injection-attack strategy of the olllllld Ether
     */
    public function overhaulStripSlashes()
    {
        $thoughts = $this->find('all')
            ->select(['id', 'thought'])
            ->where(['thought LIKE' => '%\\\\%'])
            ->order(['id' => 'ASC']);
        foreach ($thoughts as $thought) {
            echo $thought->thought;
            $fixed = stripslashes($thought->thought);
            $thought->thought = $fixed;
            $this->save($thought);
            echo " => $fixed<br />";
        }
    }

    public function overhaulToMarkdown()
    {
        $field = 'thought';
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

    public function getAllIds()
    {
        return Cache::remember('allThoughtIds', function () {
            return $this->find('list')
                ->select(['id'])
                ->toArray();
        }, 'long');
    }

    public function generateFromUser($userId, $blockSize, $wordLength)
    {
        $ids = $this->find('list')
            ->select(['id'])
            ->where(['user_id' => 1])
            ->order('rand()')
            ->toArray();
        $thoughts = $this->find('all')
            ->select(['thought'])
            ->where(function ($exp, $q) use ($ids) {
                return $exp->in('id', $ids);
            })
            ->toArray();
        $thoughts = Hash::extract($thoughts, '{n}.thought');
        $sample = implode(' ', $thoughts);
        return $this->generate($sample, $blockSize, $wordLength);
    }

    public function generate($sample, $blockSize, $wordLength)
    {
        $chain = new MarkovPHP\WordChain($sample, $blockSize);
        $wordLength = round($wordLength / $blockSize);
        $results = $chain->generate($wordLength);
        return $this->parseMarkdown($results);
    }
}
