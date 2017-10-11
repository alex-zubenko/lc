<?php
namespace Drupal\mtc_core\Controller;

use Drupal\mtc_core\Controller\LcBaseController;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/*!
 * @package     Drupal\mtc_core\Controller
 * @class       LcUserLoginController
 * @extends     Drupal\Core\Controller\LcBaseController
 * @author      Dmitri Landa, Matthieu Beunon
 * @date        2017-07-24 12:24:06 CET
 */
class LcUserLoginController extends LcBaseController
{

    /**
     * @method      displayLogin
     * @return      \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function displayLogin()
    {
        $done = true;
        $msg  = '';
        try {
            $data = $this->getWs()->getLoginForm();
        }
        catch (\Exception $e) {
            $data = null;
            $done = false;
            $msg  = $e->getMessage();
        }
        return new JsonResponse(compact('done', 'msg', 'data'));
    }

    /**
     * @method      login
     * @return      \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function login()
    {
        // view simply set SHOW_MTC_LOGIN flag
        $loginForm = null;
        return $this->twigRender('mtc_core_user_login', compact('loginForm'));
    }

    /**
     * @method      getUserNameIfLoginEmail
     * @param       str     $login
     * @return      str
     */
    private function getUserNameIfLoginEmail($login)
    {
        if (preg_match('/@/', $login) > 0) {
            $u = user_load_by_mail($login);
            if (!empty($u)) {
                $login = $u->getUsername();
            }
        }
        return $login;
    }

    /**
     * @method      checkLogin
     * @return      \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function checkLogin()
    {
        extract($this->params(['login', 'password', 'token']));
        if (empty(trim($login)) || empty(trim($password))) {
            sleep(1);
            return $this->onLoginFailure("Merci de renseigner les champs identifiant et mot de passe", $token);
        }
        try {
            $token = null;
            $login = $this->getUserNameIfLoginEmail($login);
            $resp  = $this->getWs()->authenticateUser($login, $password, $this->param('token'));
            if (!$resp->done) {
                $token = $resp->data;
            }
            $user = User::load($resp->idUser);
        }
        catch (\Exception $e) {
            /** @var \Drupal\user\UserAuth $a */
            $userAuth = \Drupal::service('user.auth');
            switch ($e->getCode()) {
                //some drupal users (admins, free) not exists in bosub, but still valid
                case 404:
                    //bad credentials - return an error
                    if (!$idUser = $userAuth->authenticate($login, $password)) {
                        break;
                    }
                    //credentials valid - complete login
                    $user = User::load($idUser);

                    return $this->onLoginSuccess($user);

                //MIGRATION HOOK
                //passwords are different, check in drupal and update bosub if valid
                case 403:

                    //bad credentials - return an error
                    if (!$idUser = $userAuth->authenticate($login, $password)) {
                        break;
                    }
                    //credentials valid - need to sync user password with BoSub
                    $user = User::load($idUser);
                    try {
                        // > need to bypass ctrl on password definition
                        $force = 1;
                        // <
                        $this->getWs()->saveUserPassword($user, compact('password', 'token', 'force'));
                        return $this->onLoginSuccess($user);
                    }
                    catch (\Exception $e2) {
                        $e = $e2;
                        break;
                    }
                default :
            }

            //common error - handle it in a regular way
            return $this->onLoginFailure($e->getMessage(), $token);
        }

        return $this->onLoginSuccess($user);
    }

    /**
     * @method      onLoginSuccess
     * @param       \Drupal\user\Entity\User     $user
     * @return      \Symfony\Component\HttpFoundation\JsonResponse
     */
    private function onLoginSuccess($user)
    {
        user_login_finalize($user);
        //refresh session
        $session = \Drupal::service('session');
        $session->migrate();

        return new JsonResponse([
            'done' => true,
            //~ 'msg'  => $this->t("You have been successfully logged in. Please wait while the page is refreshed."),
            'msg'  => "Vous avez été connecté avec succès. La page va se recharger.",
            'data' => [
                'redirect' => $this->getUrlGenerator()->generateFromRoute('mtc_core.subscriber.home.program')
            ]
        ]);
    }

    /**
     * @method      onLoginFailure
     * @param       string      $message
     * @param       string      $token
     * @return      \Symfony\Component\HttpFoundation\JsonResponse
     */
    private function onLoginFailure($message, $token)
    {
        return new JsonResponse([
            'done'  => false,
            'token' => $token,
            'msg'   => $message
        ]);
    }
}
