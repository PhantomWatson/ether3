<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;

/**
 * Messages Controller
 *
 * @property \App\Model\Table\MessagesTable $Messages
 */
class MessagesController extends AppController
{
    public $paginate = [
        'limit' => 10,
        'order' => [
            'Messages.created' => 'DESC'
        ]
    ];

    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('RequestHandler');
        $this->loadComponent('Paginator');
    }

    public function index($penpalColor = null)
    {
        $userId = $this->Auth->user('id');
        $this->loadModel('Users');
        $this->set([
            'title_for_layout' => 'Messages',
            'conversations' => $this->Messages->getConversationsIndex($userId),
            'penpalColor' => $penpalColor
        ]);
    }

    /**
     * View method
     *
     * @param string|null $id Message id
     * @return void
     * @throws \Cake\Network\Exception\NotFoundException
     */
    public function view($id = null)
    {
        $message = $this->Messages->get($id, [
            'contain' => ['Recipients', 'Senders']
        ]);
        $this->set('message', $message);
    }

    /**
     * The target of message-sending forms. If requested via AJAX, the view
     * will contain a JSON object describing the results. Otherwise, redirects back
     * to the referer with a flash message.
     */
    public function send()
    {
        if ($this->request->is('post')) {
            // Gather data
            $this->loadModel('Users');
            $recipientId = $this->Users->getIdFromColor($this->request->data['recipient']);
            $this->request->data['recipient_id'] = $recipientId;
            $this->request->data['sender_id'] = $this->Auth->user('id');
            $this->request->data['message'] = trim($this->request->data['message']);

            $errorMsg = null;
            $msgSent = false;
            $message = $this->Messages->newEntity();
            $message = $this->Messages->patchEntity($message, $this->request->data);
            if ($message->errors()) {
                $errors = Hash::flatten($message->errors());
                $errorMsg = implode('<br />', $errors);
            } elseif ($this->Messages->save($message)) {
                $msgSent = true;
            } else {
                $errorMsg = 'There was an error sending that message. Please try again.';
            }

            if ($this->request->is('ajax')) {
                $this->set('result', [
                    'error' => $errorMsg,
                    'success' => $msgSent
                ]);
            } else {
                if ($errorMsg) {
                    $this->Flash->error($errorMsg);
                }
                if ($msgSent) {
                    $this->Flash->success('Message sent');
                }
                return $this->redirect($this->request->referer());
            }

        }
    }

    public function conversation($penpalColor = null)
    {
        $userId = $this->Auth->user('id');
        $this->loadModel('Users');
        $penpalId = $this->Users->getIdFromColor($penpalColor);
        $penpal = $this->Users->get($penpalId);
        $query = $this->Messages->getConversation($userId, $penpalId);
        if (isset($_GET['full'])) {
            $messages = $query->toArray();
        } else {
            $messages = $this->paginate($query)->toArray();
            $messages = array_reverse($messages);
        }
        $this->Messages->setConversationAsRead($userId, $penpalId);

        $this->set([
            'messages' => $messages,
            'penpalId' => $penpalId
        ]);

        if ($this->request->is('ajax')) {
            return $this->render('conversation_page');
        } else {
            $this->set([
                'titleForLayout' => 'Messages with Thinker #'.$penpalColor,
                'penpalColor' => $penpal->color,
                'penpalAcceptsMessages' => $this->Users->acceptsMessages($penpalId),
                'messageEntity' => $this->Messages->newEntity()
            ]);
        }
    }
}
