<?php
namespace App\Controller;

use App\Controller\Component\FlashComponent;
use App\Model\Entity\User;
use App\Model\Table\MessagesTable;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Exception;

/**
 * @property FlashComponent $Flash
 */
class AppController extends Controller
{
    public $helpers = [
        'Time' => [
            'className' => 'EtherTime'
        ],
        'Form' => [
            'templates' => 'ether_form'
        ]
    ];

    /**
     * Initialize function
     *
     * @throws Exception
     * @return void
     */
    public function initialize()
    {
        $this->loadComponent('Cookie');
        $this->loadComponent('Flash');
        $this->loadComponent('Auth', [
            'loginAction' => [
                'controller' => 'Users',
                'action' => 'login'
            ],
            'logoutRedirect' => [
                'controller' => 'Pages',
                'action' => 'home'
            ],
            'authenticate' => [
                'Form' => [
                    'fields' => ['username' => 'email'],
                    'passwordHasher' => [
                        'className' => 'Fallback',
                        'hashers' => ['Default', 'Legacy']
                    ]
                ],
                'Xety/Cake3CookieAuth.Cookie' => [
                    'fields' => ['username' => 'email']
                ]
            ],
            'authorize' => ['Controller']
        ]);
        $this->set('debug', Configure::read('debug'));
    }

    /**
     * Default isAuthorized method, which always returns TRUE, indicating that any logged-in user is authorized
     *
     * @param User|null $user User entity
     * @return bool
     */
    public function isAuthorized($user = null)
    {
        return true;
    }

    /**
     * beforeFilter callback
     *
     * @param Event $event Event object
     * @return void
     */
    public function beforeFilter(Event $event)
    {
        $authError = $this->Auth->user('id')
            ? 'Sorry, you do not have access to that location.'
            : 'Please <a href="/login">log in</a> before you try that.';
        $this->Auth->setConfig('authError', $authError);

        // Automatically login
        if (! $this->Auth->user() && $this->Cookie->read('CookieAuth')) {
            $user = $this->Auth->identify();
            if ($user) {
                $this->Auth->setUser($user);
            } else {
                $this->Cookie->delete('CookieAuth');
            }
        }
    }

    /**
     * beforeRender callback
     *
     * @param Event $event Event object
     * @return void
     */
    public function beforeRender(Event $event)
    {
        $userId = $this->Auth->user('id');
        /** @var MessagesTable $messagesTable */
        $messagesTable = TableRegistry::getTableLocator()->get('Messages');
        $this->set([
            'userId' => $userId,
            'userColor' => $this->Auth->user('color'),
            'loggedIn' => $userId !== null,
            'newMessages' => $userId ? $messagesTable->getNewMessagesCount($userId) : 0
        ]);
    }
}
