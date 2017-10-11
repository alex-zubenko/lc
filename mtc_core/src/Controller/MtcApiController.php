<?php
namespace Drupal\mtc_core\Controller;

use Drupal\mtc_core\Controller\LcBaseController;
use Drupal\Core\Site\Settings;
use Drupal\user\Entity\User;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;
use MetaTech\PwsAuth\Authenticator;
use MetaTech\PwsAuth\Token;

/*!
 * @package     Drupal\mtc_core\Controller
 * @class       MtcApiController
 * @extends     Drupal\Core\Controller\LcBaseController
 * @author      Matthieu Beunon
 * @date        2017-01-25 12:43:12 CET
 */
class MtcApiController extends LcBaseController
{
    /*! @constant PREFIX_ROUTE_API */
    const PREFIX_ROUTE_API = 'mtc_core.mtc.api.';
    /*! @constant ROLE_SUBSCRIBER */
    const ROLE_SUBSCRIBER  = 'utilisateur_abonne'; 
    /*! @constant ROLE_USER */
    const ROLE_USER        = 'filtered_inscrit';

    /*! @protected @var @static [str] $actionsFree */
    protected static $actionsFree = [
        'prelogin'       ,
        'logout'         ,
        'talk_infos'     ,
        'talk_proposal'  ,
        'auto_login'     ,
        'login_user'     ,
        'on_payment'      ,
        'subscribe_step1',
        'subscribe_step2',
        'subscribe_step3'
    ];

    /*!
     * firewall to all non-free routes using MetaTech\PwsAuth\Authenticator
     * 
     * @method      init
     * @protected
     */
    protected function init()
    {
        $action = $this->getActionName();
        extract($this->params(['apitkn', 'apikey']));
        if (!in_array($action, self::$actionsFree) && !\Drupal::service('mtc_core.mtc_auth')->check($apitkn, $apikey)) {
            sleep(3);
            $resp = new JsonResponse(['done' => false, 'msg' => 'forbidden']);
            $resp->send();
            exit;
        }
    }

    /*!
     * @method      getActionName
     * @protected
     * @return      str
     */
    protected function getActionName()
    {
        return substr($this->getRouteName(), strlen(self::PREFIX_ROUTE_API));
        //~ $route = explode('/', strtok(\Drupal::request()->getRequestUri(),'?'));
        //~ return end($route);
    }

    /*!
     * @method      newUser
     * @private
     * @param       str     $nickname
     * @param       str     $rawPassword
     * @param       str     $email
     * @param       bool    $isSubscriber
     * @return      Drupal\user\Entity\User
     */
    private function newUser($nickname, $rawPassword, $email, $isSubscriber=false, $isActive=true)
    {
        $lang = $this->getLang();
        $user = User::create();
        $user->setUsername($nickname);
        $user->setPassword($rawPassword);
        $user->setEmail($email);
        $user->set('init', $email);
        $user->set('langcode', $lang);
        $user->set('preferred_langcode', $lang);
        $user->set('preferred_admin_langcode', $lang);
        $user->addRole(self::ROLE_USER);
        if ($isSubscriber) {
            $user->addRole(self::ROLE_SUBSCRIBER);
        }
        if ($isActive) {
            $user->activate();
        }
        return $user;
    }

    /*!
     * @method      createUser
     * @public
     * @return      Symfony\Component\HttpFoundation\Response
     */
    public function createUser()
    {
        try {
            $msg  = '';
            $done = false;
            $uid  = null;
            extract($this->params(['nickname', 'password', 'email', 'subscribe']));
            if (!empty($nickname) && !empty($password) && !empty($email)) {
                if (empty(user_load_by_mail($email)) && empty(user_load_by_name($nickname))) {
                    $lang = $this->getLang();
                    $user = $this->newUser($nickname, $password, $email, $subscribe==1);
                    $done = $user->save();
                    $uid  = $user->id();
                    $msg  = "user $nickname successfuly created";
                }
                else {
                    $msg = 'user already exists';
                }
            }
            else {
                $msg = 'invalid input parameters';
            }
        }
        catch(\Exception $e) {
            $msg = "drupal error : ".$e->getMessage();
        }
        return new JsonResponse(compact('done', 'uid', 'msg'));
    }

    /*!
     * @method      updateUser
     * @public
     * @return      Symfony\Component\HttpFoundation\Response
     */
    public function updateUser()
    {
        try {
            $msg  = '';
            $done = false;
            $uid  = null;
            extract($this->params(['uid', 'nickname', 'password', 'email', 'status']));
            if (!empty($uid) && !empty($user = User::load($uid, true))) {
                if (!empty($nickname) && $nickname != $user->getAccountName()) {
                    $user->setUserName($nickname);
                }
                if (!empty($email) && $email != $user->getEmail()) {
                    $user->setEmail($email);
                }
                if (!empty($password) && $password != $user->getPassword()) {
                    $user->setPassword($password);
                }
                if (!empty($status)) {
                    if ($status == 1) {
                        $user->activate();
                    }
                    else {
                        $user->block();
                    }
                }
                if (($done = $user->save())) {;
                    $msg  = "user $uid successfuly updated";
                }
                else {
                    $msg = "update user $uid failed";
                }
            }
            else {
                $msg = "user $uid does not exists";
            }
        }
        catch(\Exception $e) {
            $msg = "drupal error : ".$e->getMessage();
        }
        //~ $route = \Drupal::routeMatch();
        $route = explode('/', strtok(\Drupal::request()->getRequestUri(),'?'));
        $route = end($route);
        return new JsonResponse(compact('done', 'uid', 'msg', 'route'));
    }

    /*!
     * @method      updateRoles
     * @public
     * @return      Symfony\Component\HttpFoundation\Response
     */
    public function updateRoles()
    {
        try {
            $msg  = '';
            $done = false;
            $uid  = null;
            extract($this->params(['uid', 'subscribe']));
            if (!empty($uid) && !empty($user = User::load($uid, true))) {
                $user->addRole(self::ROLE_USER);
                if ($subscribe==1) {
                    $user->addRole(self::ROLE_SUBSCRIBER);
                }
                else {
                    $user->removeRole(self::ROLE_SUBSCRIBER);
                }
                if (($done = $user->save())) {
                    $msg = "roles user $uid successfuly updated";
                }
            }
            else {
                $msg = "user $uid does not exists";
            }
        }
        catch(\Exception $e) {
            $msg = "drupal error : ".$e->getMessage();
        }
        return new JsonResponse('');
    }

    /*!
     * @method      loginUser
     * @public
     * @return      Symfony\Component\HttpFoundation\Response
     */
    public function loginUser()
    {
        return new JsonResponse('');
    }

    /*!
     * @method      onPayment
     * @public
     * @return      Symfony\Component\HttpFoundation\Response
     */
    public function onPayment()
    {
        $token    = $this->query('token');
        if (!empty($token)) {
            $response = $this->getWs()->post('/ws/register/data', compact('token'));
            if (!is_null($response) && $response->done) {
                if ($this->user->isAnonymous()) {
                    user_login_finalize(user_load_by_name($response->data->login));
                }
                $this->store->delete('subscription.summary');
                $this->getSubscription(0);
            }
        }
        return $this->redirect('mtc_core.subscription.home');
    }

    /*!
     * @method      talkInfos
     * @public
     * @return      Symfony\Component\HttpFoundation\Response
     */
    public function talkInfos()
    {
        $data = '';
        if (!$this->user->isAnonymous()) {
            $subscription = $this->getSubscription(boolval($this->query('refresh')) ? 0 : 3600);
            $data = $subscription->talkinfos ?: null;
            if (isset($data->restTalk)) {
                $data->consumeTalk = $data->restTalk;
                unset($data->idEvent);
                if ($data->state = 1 && $data->type=='list') {
                    unset($data->scheduled);
                }
                unset($data->restTalk);
            }
            foreach(['idMigrate', 'comment', 'processed', 'programAccess', 'created', 'updated', 'tel1', 'tel2', 
                     'idUser', 'idProgram', 'idPerson', 'email', 'access', 'emailing', 'nickname'] as $key) {
               unset($data->$key);
            }
            if (isset($data->coach)) {
                $data->coach->url = '/' . \Drupal::theme()->getActiveTheme()->getPath() .'/images/coach-'.$data->coach->username.'.jpg';
            }
        }
        return new JsonResponse($data);
    }

    /*!
     * @method      talkProposal
     * @public
     * @return      Symfony\Component\HttpFoundation\Response
     */
    public function talkProposal()
    {
        // https://mtc-prod:MTC-nppacn%237514@linecoaching.metacoaching.pro/mtc_api/talk_proposal?v=1&rdv1=2017-04-01T10:00:00.000Z&rdv2=2017-04-04T13:30:00.000Z&rdv3=2017-03-31T15:30:00.000Z&activity=Activit%C3%A9%20%7C%20Personnalisons%20ensemble%20votre%20parcours&last_date=2017-04-24T12%3A15%3A34.000Z&link=%2Ftherapy%2Factivity%2F10497%2Ffirst-page&step=J'observe%20mon%20comportement%20alimentaire
        try {
            $data = $msg = '';
            $done = false;
            if (!$this->user->isAnonymous()) {
                $config = \Drupal::service('mtc_core.config')->get('site');
                // date rdv
                $params   = $this->queries(['rdv1', 'rdv2', 'rdv3', 'activity', 'last_date', 'link', 'step']);
                $params['login'] = $this->user->getUsername();
                if (!empty($config['uid_coach'] && !empty($coach = User::load($config['uid_coach'], true)))) {
                    $done = $this->mail('talkProposal', $params, $coach);
                }
                // send response to emitter
                $data = compact('done', 'msg');
            }
        }
        catch(\Exception $e) {
            $done = false;
            $msg  = $e->getMessage().' '.$e->getTraceAsString();
            $data = compact('done', 'msg');
        }
        return new JsonResponse($data);
    }


    /*!
     * @method      validateTunnelStep1
     * @public
     * @return      Symfony\Component\HttpFoundation\Response
     */
    public function validateTunnelStep1()
    {
        $done      = false;
        extract($this->params(['token', 'day', 'month', 'year']));
        if (!empty($token)) {
            $birthday  = "$day/$month/$year";
            $response  = $this->getWs()->post('/ws/tunnel/step1/validate', $_POST + compact('birthday'));
            $done      = !is_null($response) && $response->done;
        }
        $route     = $done ? 'mtc_core.ws.tunnel.payment' : 'mtc_core.ws.tunnel.offers';
        return $this->redirect($route);
    }

    /*!
     * @method      validateTunnelStep2
     * @public
     * @return      Symfony\Component\HttpFoundation\Response
     */
    public function validateTunnelStep2()
    {
        $done     = false;
        extract($this->params(['token']));
        if (!empty($token)) {
            $response = $this->getWs()->post('/ws/tunnel/step2/validate', $_POST);
            $done     = !is_null($response) && $response->done;
            $route    = $done ? 'mtc_core.ws.tunnel.account' : 'mtc_core.ws.tunnel.payment';
        }
        if (empty($token) || !isset($response->user)) {
            $route = 'mtc_core.ws.tunnel.offers';
        }
        //~ var_dump(compact('token', 'route', 'response', 'done'));
        return $this->redirect($route);
    }

    /*!
     * @method      validateTunnelStep3
     * @public
     * @return      Symfony\Component\HttpFoundation\Response
     */
    public function validateTunnelStep3()
    {
        $this->store->delete('user.ws.warning');
        $session  = \Drupal::service('session');
        $done     = false;
        $account  = null;
        $datapost = $this->params(['confirmEmail', 'token', 'country', 'username', 'pwd', 'address', 'address_more', 'city', 'international_phone', 'phone', 'zip_code']);
        $dataws   = array(
            'confirmEmail' => $datapost['confirmEmail'],
            'token'        => $datapost['token'],
            'idCountry'    => $datapost['country'],
            'address1'     => $datapost['address'],
            'address2'     => $datapost['address_more'],
            'city'         => $datapost['city'],
            'telforeign'   => $datapost['international_phone']=='on' ? 1 : 0,
            'tel1'         => $datapost['phone'],
            'zipcode'      => $datapost['zip_code']
        );
        $response = $this->getWs()->post('/ws/tunnel/contact/set', $dataws);
        if (!is_null($response)) {
            $lang     = $this->getLang();
            $person   = $response->data->person;
            $contact  = $response->data->contact;
            $access   = $response->data->access;
            $sessuser = $response->user;
            $logged   = !$this->user->isAnonymous() && $this->user->id() > 0;
            $renewalSuffix      = '.remtc-'.substr($datapost['token'], 0, 4);
            $renewalOldNickname = $logged ? $this->user->getUsername() . $renewalSuffix : null;
            $renewalOldEmail    = $logged ? $access->email . $renewalSuffix : null;
            if (!$logged || $sessuser->idUser == 0) {
                $nickname = $logged ? $this->user->getUsername() : $datapost['username'];
                $account  = user_load_by_name(!empty($access->nickname) ? $access->nickname : $datapost['username']);
                if (!$account || $account->id() == 0) {
                    $account = $this->newUser($datapost['username'], $datapost['pwd'], $access->email, true);
                    $account->save();
                }
                else {
                    if (!$account) {
                        $account = null;
                        $this->store->set('user.ws.warning', "le pseudo ".$datapost['username']." n'est pas disponible");
                    }
                    // renewal subscription
                    else {
                        if ($this->matchRuleNewTherapyUser($account)) {
                            $account->setUsername($renewalOldNickname);
                            $account->setEmail($renewalOldEmail);
                            $account->block();
                            $account->save();
                            $account = $this->newUser($nickname, $session->get('unblocked.pass'), $access->email, true);
                        }
                        // ensure unblock old therapy has correct roles
                        $account->addRole(self::ROLE_USER);
                        $account->addRole(self::ROLE_SUBSCRIBER);
                        $account->save();
                        $session->set('user.renewal.uid', $account->id());
                    }
                }
                if (!empty($account) && $account->id() > 0) {
                    $sessuser->idUser   = $account->id();
                    $sessuser->nickname = $account->getAccountName();
                    $done = true;
                }
            }
            elseif($logged) {
                $done = true;
            }
            if ($done) {
                $dataws   = array(
                    'token'      => $sessuser->token,
                    'nickname'   => $sessuser->nickname,
                    'idUser'     => $sessuser->idUser,
                    'password'   => $datapost['pwd'],
                    'renewalsub' => $logged
                );
                $response = $this->getWs()->post('/ws/tunnel/step3/validate', $dataws);
            }
        }
        $route     = isset($sessuser) && $sessuser->idUser > 0 ? "mtc_core.ws.tunnel.thanks" : "mtc_core.ws.tunnel.account";
        $resptoken = isset($sessuser) ? $sessuser->token : null;
        $session->set('tunnel.token', $datapost['token']);
        error_log('token : '. $session->get('tunnel.token'));
        $this->store->set('user.ws.token', $datapost['token']);
        return $this->redirect($route);
    }

    /*!
     * check if user is an old subscriber wich can go on new therapeutic way
     * 
     * @method      matchRuleNewTherapyUser
     * @private
     * @return      bool
     */
    public function matchRuleNewTherapyUser(User $account)
    {
        $config  = \Drupal::service('mtc_core.config')->get('site');
        $done    = false;
        if ($config['max_uid_old_subscribers'] > $account->id()) {
            $session = \Drupal::service('session');
            // > uncomment to test
            // $session->set('unblocked.access', 1492118520);
            // <
            $access = intval($session->get('unblocked.access'));
            error_log(' > '. __method__ . ' uid : '.$account->id().' - nickname : ' . $account->getUsername() . ' - lastaccessed : '. date('Y-m-d H:i:s', $access) . ' force : '.intval($session->get('force.new.therapy')));
            if ($access > 0 && !($done = boolval($session->get('force.new.therapy')))) {
                $config = \Drupal::service('mtc_core.config')->get('site');
                $limval = isset($config['max_time_old_subscribers']) ? intval($config['max_time_old_subscribers']) : 7776000;
                $limit  = time() - $limval;
                error_log('limit : ' . date('Y-m-d H:i:s', $limit) . " [$limit; max_time_old_subscribers : $limval]");
                $done = $access < $limit;
            }
        }
        error_log(' < '. __method__ . ' done ? '.intval($done));
        return $done;
    }

    /*!
     * called by mtc portail with appropriate token. 
     * check the token with mtc mediation
     * then create a cookie to ensure autologin, see EventSubscriber\InitSubscriber.php
     * 
     * @method      preLoginInsured
     * @public
     * @return      Symfony\Component\HttpFoundation\Response
     */
    public function preLoginInsured()
    {
        $resp     = $this->getWs()->validateInsuredToken($this->query('acktoken'), false, true);
        $response = new JsonResponse('');
        if (!is_null($resp) && $resp->done) {
            $response->headers->setCookie(new Cookie('mediation_insured', $resp->data->acktoken, time() + 1800));
        }
        return $response;
    }

    /*!
     * @method      logoutInsured
     * @public
     * @return      Symfony\Component\HttpFoundation\Response
     */
    public function logoutInsured()
    {
        user_logout();
        //~ $resp     = $this->getWs()->validateInsuredToken($this->query('acktoken'), false, true);
        $response = new JsonResponse('');
        return $response;
    }

    /*!
     * @method      renameOldBlockUsers
     * @public
     * @return      Symfony\Component\HttpFoundation\Response
     */
    public function renameOldBlockUsers()
    {
        $config  = \Drupal::service('mtc_core.config')->get('site');
        $params  = $this->params(['ids']);
        $done    = false;
        $data    = [];
        $account = null;
        try {
            foreach($params['ids'] as $uid) {
                if (!empty($uid) && $uid < $config['max_uid_old_subscribers'] && !empty($account = User::load($uid, true))) {
                    if ($account->isBlocked()) {
                        if (preg_match('/\.$/', $account->getUsername()) < 1) {
                            $account->setUsername($account->getUsername().'.');
                            $account->setEmail($account->getEmail().'.mtcold'); 
                            if ($account->save()) {
                                $data[] = $uid;
                            }
                        }
                    }
                }
            }
            $done = !empty($data);
        }
        catch (\Exception $e) {
            $msg = $e->getMessage();
        }
        $response = new JsonResponse(compact('done', 'data', 'msg'));
        return $response;
    }

}
