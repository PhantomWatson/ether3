<?php
namespace App\Event;

use App\Model\Table\ThoughtsTable;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Cache\Cache;

class ThoughtListener implements EventListenerInterface
{

    public function implementedEvents()
    {
        return [
            'Model.Thought.created' => [
                ['callable' => 'updatePopulatedThoughtwords']
            ],
            'Model.Thought.updated' => [
                ['callable' => 'updatePopulatedThoughtwords']
            ],
            'Model.Thought.deleted' => [
                ['callable' => 'updatePopulatedThoughtwords']
            ]
        ];
    }

    /**
     * Updates the cache of populated thoughtwords
     *
     * @param Event $event Event
     * @param Entity $entity Entity
     */
    public function updatePopulatedThoughtwords($event, $entity)
    {
        // Exit if entity was updated without changing word
        if (!$entity->isNew() && !$entity->isDirty('word')) {
            return;
        }

        // Exit if this is a new thought on an already-populated thoughtword
        /** @var ThoughtsTable $thoughts */
        $thoughts = TableRegistry::getTableLocator()->get('Thoughts');
        if ($entity->isNew() && $thoughts->getPopulation($entity->word) > 1) {
            return;
        }

        // Thought is either a new thought on a newly-populated thoughtword or a thought
        // with an edited thoughtword that might be changing the list of all populated thoughtwords

        // Get and cache new list now, so the slight delay is experienced by the poster, not the next viewer
        Cache::delete('populatedThoughtwords');
        $thoughts->getWords();
    }
}
