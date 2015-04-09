<?php
namespace App\Controller;

use App\Controller\AppController;

/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 */
class UsersController extends AppController
{

	public function initialize()
	{
		parent::initialize();
		$this->Auth->allow(['index', 'register', 'login', 'view', 'check_color_availability']);

		if ($this->request->action === 'register') {
			$this->loadComponent('Recaptcha.Recaptcha');
		}
	}

    /**
     * Index method
     *
     * @return void
     */
    public function index()
    {
        $this->set('users', $this->paginate($this->Users));
    }

    /**
     * View method
     *
     * @param string|null $color User color
     * @return void
     * @throws \Cake\Network\Exception\NotFoundException
     */
    public function view($color = null)
    {
        $user = $this->Users->getProfileInfo($color);
        $this->set([
        	'title_for_layout' => "Thinker #$color",
        	'user' => $user
		]);
    }

    public function register()
    {
        $user = $this->Users->newEntity();
        if ($this->request->is('post')) {
        	if ($this->Recaptcha->verify()) {
	        	$user = $this->Users->patchEntity($user, $this->request->data);

	        	// Clean email and color
	        	$cleanEmail = trim($user->email);
				$cleanEmail = strtolower($cleanEmail);
				$cleanColor = strtolower($user->color);
				$cleanColor = preg_replace("/[^a-z0-9]/", '', $cleanColor);

				$user->set([
					'password' => $this->request->data['User']['password'],
					'email' => $cleanEmail,
					'color' => $cleanColor,
					'password_version' => 3
				]);

	            if ($this->Users->save($user)) {
	                $this->Flash->success('Your account has been registered. You may now log in.');
	                return $this->redirect(['action' => 'login']);
	            } else {
	                $this->Flash->error('There was an error registering your account. Please try again.');
	            }
			} else {
				$this->Flash->error('Invalid CAPTCHA response');
			}
        } else {
        	// Select a random available color
			do {
				$this->request->data['color'] = '#';
				for ($n = 1; $n <= 3; $n++) {
					$this->request->data['color'] .= str_pad(dechex(rand(0, 250)), 2, '0', STR_PAD_LEFT);
				}
				$isTaken = $this->Users->colorIsTaken($this->request->data['color']);
			} while ($isTaken);
			$this->set('random_color', true);
		}

		/* So the password fields aren't filled out automatically when the user
		 * is bounced back to the page by a validation error */
		$this->request->data['User']['new_password'] = null;
	    $this->request->data['User']['confirm_password'] = null;

        $this->set([
        	'title_for_layout' => 'Register Account',
		]);
    }

    /**
     * Edit method
     *
     * @param string|null $id User id
     * @return void
     * @throws \Cake\Network\Exception\NotFoundException
     */
    public function edit($id = null)
    {
        $user = $this->Users->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->request->data);
            if ($this->Users->save($user)) {
                $this->Flash->success('The user has been saved.');
                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error('The user could not be saved. Please, try again.');
            }
        }
        $this->set(compact('user'));
    }

    /**
     * Delete method
     *
     * @param string|null $id User id
     * @return void
     * @throws \Cake\Network\Exception\NotFoundException
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Users->get($id);
        if ($this->Users->delete($user)) {
            $this->Flash->success('The user has been deleted.');
        } else {
            $this->Flash->error('The user could not be deleted. Please, try again.');
        }
        return $this->redirect(['action' => 'index']);
    }

	public function login()
	{
		if ($this->request->is('post')) {
			$user = $this->Auth->identify();
			if ($user) {
				$this->Auth->setUser($user);
				if ($this->Auth->authenticationProvider()->needsPasswordRehash()) {
					$user = $this->Users->get($this->Auth->user('id'));
					$user->password = $this->request->data('password');
					$user->password_version = 3;
					$this->Users->save($user);
				}
				return $this->redirect($this->Auth->redirectUrl());
			} else {
				$this->Flash->error('Email or password is incorrect');
			}
		}
		$this->set(array(
			'title_for_layout' => 'Log in'
		));
	}

	public function logout()
	{
		return $this->redirect($this->Auth->logout());
	}

	public function check_color_availability($color = null)
	{
		$this->layout = 'ajax';
		$this->set('available', ! $this->Users->colorIsTaken($color));
	}
}
