<?php
namespace Drupal\mtc_core\Controller;

use Drupal\mtc_core\Controller\LcBaseController;
use Drupal\user\Entity\User;

class LcUserPasswordController extends LcBaseController
{
    public function finishPasswordReset($idUser) {
        $successMsg = '';
        $errorMsg = '';
        \Drupal::service('page_cache_kill_switch')->trigger();
        if ($this->isMethod('POST')) {
            $data = $this->params(['token', 'password']);
            try {
                //update drupal user
                $user = User::load($idUser);
                if (!$user) {
                    throw new \Exception('User not found');
                }
                $user->setPassword($this->param('password'));
                $user->save();
                //relogin user
                user_login_finalize($user);
                //refresh session
                $session = \Drupal::service('session');
                $session->migrate();
                //update boSub user
                $this->getWs()->saveUserPassword($user, $data);
                drupal_set_message('Votre mot de passe à bien été modifié', 'status');
                //~ return $this->redirect('mtc_core.homepage');
            }
            catch(\Exception $e) {
                $errorMsg = $this->t($e->getMessage());
            }
        }
        else {
            try {
                $token        = $this->query('token');
                $response     = [ 'done' => true ];
                $passwordForm = $this->getWs()->getUserPasswordForm($idUser, $token);
            } catch (\Exception $e) {
                $response['msg'] = $e->getMessage();
            }
        }
        return $this->twigRender('mtc_core_finish_password_reset', compact('passwordForm', 'response'));
    }
}
