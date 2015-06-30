<?php
namespace App\Controller;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

/**
 * Static content controller
 *
 * This controller will render views from Template/Pages/
 *
 * @link http://book.cakephp.org/3.0/en/controllers/pages-controller.html
 */
class OverhaulController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->Auth->allow();
    }

    public function stripSlashes()
    {
        $this->loadModel('Thoughts');
        $this->loadModel('Comments');
        $this->loadModel('Messages');
        $this->loadModel('Users');
        $this->Thoughts->overhaulStripSlashes();
        $this->Comments->overhaulStripSlashes();
        $this->Messages->overhaulStripSlashes();
        $this->Users->overhaulStripSlashes();
        $this->set(array(
            'recentActivity' => [],
            'topCloud' => []
        ));
        $this->render('/Pages/home');
    }
}
