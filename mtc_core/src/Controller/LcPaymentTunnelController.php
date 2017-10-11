<?php
namespace Drupal\mtc_core\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\mtc_core\Controller\LcBaseController;
use Drupal\mtc_core\Service\SwiftMailerService;
use Drupal\user\Entity\User;

/*!
 * @package     Drupal\mtc_core\Controller
 * @class       LcPaymentTunnelController
 * @extends     Drupal\Core\Controller\LcBaseController
 * @author      Matthieu Beunon
 * @date        2017-01-09 18:24:06 CET
 */
class LcPaymentTunnelController extends LcBaseController
{

    /*!
     * @method      init
     * @protected
     */
    protected function init()
    {
        $force = $this->query('newprog');
        if ($force != '') {
            \Drupal::service("session")->set("force.new.therapy", $force == 1);
        }
    }

    /*!
     * @method      wsEmailAvailable
     * @public
     * @return      Symfony\Component\HttpFoundation\JsonResponse
     */
    public function wsEmailAvailable($email='')
    {
        if ($this->user->isAnonymous()) {
            if (!empty($email)) {
                // check status subscription
                $resp = $this->getWs()->checkSubscriptionStatusByEmail($email);
                if (!is_null($resp) && $resp->data->status !=-1 && $resp->data->status != 0) {
                    return new JsonResponse($resp->data->status);
                }
                $email = urldecode($email);
                $user  = user_load_by_mail($email);
                // user account without subscription
                if (!empty($user) && !empty($user->getAccountName())) {
                    return new JsonResponse(-1);
                }
            }
        }
        return new JsonResponse($this->getWs()->getStatusEmailUnknowned());
    }

    /*!
     * @method      wsNicknameAvailable
     * @public
     * @return      Symfony\Component\HttpFoundation\JsonResponse
     */
    public function wsNicknameAvailable()
    {
        // return reverse due to validator "remote" wich need a true required
        $val = true;
        if (!empty($name = $this->query('username'))) {
            $user = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(compact('name'));
            $val  = empty($user);
        }
        return new JsonResponse($val);
    }

    /*!
     * load offerset relative to specifyed discount code
     *
     * @method      loadOfferSet
     * @protected
     * @param       str     $codeparam
     * @return      Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function loadOfferSet($codeparam='code')
    {
        if (!is_null($code = $this->query($codeparam, null))) {
            // ajax display to get back offers in specified offerset
            return new JsonResponse($this->getWs()->getOffersByCode($codeparam, $code));
        }
        return null;
    }

    /*!
     *
     * @method      hasValidSubscription
     * @protected
     * @return      bool
     */
    protected function hasValidSubscription($refresh=false)
    {
        $valid = false;
        if (!$this->user->isAnonymous()) {
            $subscription = $this->getSubscription($refresh ? 0: 900);
            if (!is_null($subscription)) {
                $valid = isset($subscription->state) && in_array($subscription->state, [1, 101, 3]);
            }
        }
        return $valid;
    }

    /*!
     * @method      getUserInfos
     * @protected
     * @return      [assoc]
     */
    protected function getUserInfos()
    {
        if (!is_null($this->user)) {
            $idUser   = $this->user->id();
            $nickname = $this->user->getAccountName();
            $email    = $this->user->getEmail();
        }
        return compact('idUser', 'nickname', 'email');
    }

    /*!
     * @method      getCgaContent
     * @protected
     * @return      str
     */
    protected function getCgaContent()
    {
        $alias   = '/conditions-generales-dabonnement';
        $content = "missing cga node alias $alias";
        $path    = \Drupal::service('path.alias_manager')->getPathByAlias($alias);
        if(preg_match('/node\/(\d+)/', $path, $matches)) {
            $node    = \Drupal\node\Entity\Node::load($matches[1]);
            $content = $node->body->view('full');
        }
        return $content;
    }

    /*!
     * @method      getStepOffer
     * @public
     * @return      [assoc]|Symfony\Component\HttpFoundation\Response
     */
    public function getStepOffer()
    {
        \Drupal::service('page_cache_kill_switch')->trigger();
        $isSubscriber = $this->hasValidSubscription(true);
        $cga          = $this->getCgaContent();
        if ($this->query('step') == 'cga') {
            $step       = 'cga';
            $wsstep     = null;
            return $this->twigRender('mtc_core_ws_payment_tunnel', compact('wsstep', 'step', 'cga', 'isSubscriber'));
        }
        $step       = 'offers';
        $paramRoute = '';
        $codeparam  = 'code';
        // load offer code
        if (!is_null($r = $this->loadOfferSet($codeparam))) {
            return $r;
        }
        // web service token
        $wstk       = $this->query('wstk', null);
        $token      = !empty($wstk) ? $wstk : $this->store->get('user.ws.token');
        if (!empty($wstk)) {
            $this->store->set('user.ws.token', $token);
        }
        $code       = $this->getRequestDiscountCode();

        // possibly preset offerset
        $qstr   = '';
        $dus    = $this->query('dus'); // duration selection
        $tas    = $this->query('tas'); // talk selection
        $qstr   = $this->concatParam(compact('dus', 'tas', 'code'), $qstr);

        extract($this->getUserInfos());

        $wsstep = $this->getWs()->getTunnelStep1($qstr, compact('codeuri', 'codeparam', 'htmlonly', 'token', 'idUser', 'nickname', 'email'));
        if (!is_null($wsstep) && $wsstep->done) {
            //~ $this->store->set('user.ws.token', $wsstep1->user->token);
            if (empty($token) || $token != $wsstep->user->token) {
                $this->store->set('user.ws.token', $wsstep->user->token);
            }
            $token = $this->store->get('user.ws.token');
            if ($wsstep->user->paid) {
                return $this->redirect('mtc_core.ws.tunnel.account');
            }
            elseif(!is_null($wstk) && $wsstep->user->idOffer > 0) {
                return $this->redirectByUri('mtc_core.ws.tunnel.payment');
            }
        }
        $cga = $this->getCgaContent();
        //~ $this->saveSessionIfNeeded();
        //~ var_dump(compact('step','token', 'wstk'));
        return $this->twigRender('mtc_core_ws_payment_tunnel', compact('wsstep', 'step', 'cga', 'isSubscriber'));
    }

    /*!
     * @method      getRequestDiscountCode
     * @private
     * @return      str
     */
    private function getRequestDiscountCode()
    {
        $code = $this->query('check_code');
        if (empty($code)) {
            $config     = \Drupal::service('mtc_core.config')->get('site');
            $cookies    = \Drupal::request()->cookies;
            $cookieName = @$config['insurer']['partnership_cookie_name'];
            if (!empty($cookieName)) {
                preg_match('/([^:]*):(.*)/', $cookies->get($cookieName), $matches);
                if (count($matches) == 3) {
                    $insurer = $matches[1];
                    $code    = $matches[2];
                }
            }
        }
        return $code;
    }

    /*!
     * @method      getStepPayment
     * @protected
     * @return      [assoc]|Symfony\Component\HttpFoundation\Response
     */
    public function getStepPayment()
    {
        $step = 'payment';
        \Drupal::service('page_cache_kill_switch')->trigger();
        extract($this->getUserInfos());
        $token  = $this->store->get('user.ws.token');
        //~ var_dump(compact('idUser', 'nickname', 'email', 'token'));
        $wsstep = empty($token) ? null : $this->getWs()->getTunnelStep2(compact('token', 'idUser', 'nickname', 'email'));
        if (!is_null($wsstep)) {
            if (!isset($wsstep->user) || $wsstep->user->idOffer == 0 || $token != $wsstep->user->token) {
                return $this->redirect('mtc_core.ws.tunnel.offers');
            }
            elseif ($wsstep->user->paid) {
                return $this->redirect('mtc_core.ws.tunnel.account');
            }
        }
        //~ var_dump(compact('step', 'token'));
        return $this->twigRender('mtc_core_ws_payment_tunnel', compact('wsstep', 'step'));
    }

    /*!
     * @method      getStepCarePay
     * @protected
     * @return      [assoc]|Symfony\Component\HttpFoundation\Response
     */
    public function getStepCarePay()
    {
        $step = 'carepay';
        extract($this->getUserInfos());
        $token  = $this->store->get('user.ws.token');
        //~ var_dump(compact('idUser', 'nickname', 'email', 'token'));
        $wsstep = null;
        return $this->twigRender('mtc_core_ws_payment_tunnel', compact('wsstep', 'step'));
    }

    /*!
     * @method      getStepAccount
     * @protected
     * @return      [assoc]|Symfony\Component\HttpFoundation\Response
     */
    public function getStepAccount()
    {
        $step    = 'account';
        \Drupal::service('page_cache_kill_switch')->trigger();
        // $this->store->delete('user.payment.conversion');
        extract($this->getUserInfos());
        $session    = \Drupal::service('session');
        $token      = $this->store->get('user.ws.token');
        $error      = $session->get('tunnel.error.msg');
        $field      = $session->get('tunnel.error.field');
        $session->remove('tunnel.error.msg');
        $session->remove('tunnel.error.field');
        $wsstep     = empty($token) ? null : $this->getWs()->getTunnelStep3(compact('token', 'idUser', 'nickname', 'email', 'error', 'field'));
        if (!is_null($wsstep)) {
            if ($wsstep->user->finalized == true) {
                return $this->redirect('mtc_core.ws.tunnel.thanks');
            }
            $config = \Drupal::service('mtc_core.config');
            $wsstep->conversion = false;
            $wsstep->isProd     = $config->isProd();
            $wsstep->tags       = $config->get('tags');
            if (!isset($wsstep->user) || !$wsstep->user->paid || $token != $wsstep->user->token) {
                return $this->redirect('mtc_core.ws.tunnel.offers');
            }
            elseif (!$this->store->get('user.payment.conversion')) {
                $wsstep->conversion = true;
                $this->store->set('user.payment.conversion', true);
            }
        }
        $wsstep->warning = $this->store->get('user.ws.warning');
        //~ var_dump(compact('step', 'token'));
        return $this->twigRender('mtc_core_ws_payment_tunnel', compact('wsstep', 'step'));
    }

    /*!
     * @method      getStepThanks
     * @protected
     * @return      [assoc]|Symfony\Component\HttpFoundation\Response
     */
    public function getStepThanks()
    {
        extract($this->getUserInfos());
        $session   = \Drupal::service('session');
        $step      = 'thanks';
        $token     = $this->store->get('user.ws.token');
        if (empty($token)) {
            error_log(' > '. __line__ . ' : ' . __method__);
            $token = $session->get('tunnel.token');
            error_log('token : '. $token);
        }
        $clean     = false;
        $withOrder = true;
        $wsstep    = empty($token) ? null : $this->getWs()->getTunnelData(compact('token', 'clean', 'withOrder'));
        if (!is_null($wsstep) && $wsstep->done) {
            $order = $wsstep->data->order;
            if (isset($wsstep->user) && $wsstep->user->paid && $token == $wsstep->user->token) {
                // > uncomment to test multiple email send
                //~ $this->store->delete('user.confirm.order');
                // <
                if (!$this->store->get('user.confirm.order')) {
                    $uid     = $session->get('user.renewal.uid') ?: $wsstep->user->idUser;
                    // autolog the user even if he was already logged (his uid may has changed)
                    $usr     = User::load($uid, true);
                    user_login_finalize($usr);
                    $session->migrate();
                    $this->store->set('user.ws.token', $token);
                    unset($_SESSION['mtc_core']['anonymous']);
                    $this->getWs()->updateSvt($uid);
                    $wsstep->data->order->notice_price = $this->getWs()->getNoticePriceContent();
                    if (!$this->store->get('user.confirm.order')) {
                        $sent = $this->mail('confirmOrder', $wsstep->data->order);
                        $this->store->set('user.confirm.order', $sent);
                    }
                }
                return $this->twigRender('mtc_core_ws_payment_tunnel', compact('wsstep', 'step'));
            }
        }
        //~ var_dump(compact('step'));
        return $this->redirect('mtc_core.ws.tunnel.offers');
    }
}
