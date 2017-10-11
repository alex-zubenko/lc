<?php
namespace Drupal\mtc_core\Service;

use MetaTech\Ws\Client;
use MetaTech\PwsAuth\Authenticator;
use Drupal\user\UserInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Utility\Crypt;

/*!
 * Service to manage mtc mediation rest client
 *
 * @package     Drupal\mtc_core\Service
 * @class       WsClientService
 * @author      Matthieu Beunon
 * @date        2017-02-09 13:57:01 CET
 */
class WsClientService
{
    /*! @constant WS_EMAIL_AVAILABLE_UNKNOWNED */
    const WS_EMAIL_AVAILABLE_UNKNOWNED = -2;

    /* @protected @var str $client */
    protected $client;

    /*!
     * @constructor
     * @public
     */
    public function __construct()
    {
        $config        = \Drupal::service('mtc_core.config');
        $this->client  = new Client($config->get('ws'), new Authenticator($config->get('auth')));
    }

    /*!
     * @method      getClient
     * @public
     * @return      Mtc\Rest\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /*!
     * @method      getClient
     * @public
     * @return      object
     */
    public function get($path)
    {
        return $this->client->get($path);
    }

    /*!
     * @method      getClient
     * @public
     * @return      object
     */
    public function post($path, $data = [])
    {
        return $this->client->post($path, $data);
    }

    /*!
     * @method      getOffersByCode
     * @public
     * @param       str     $code
     * @return      object
     */
    public function getOffersByCode($codeparam, $code='')
    {
        $htmlonly   = true;
        $paramRoute = !empty($code) ? '?' . $codeparam.'='.$code : '';
        return $this->post('/ws/offers'.$paramRoute, compact('codeuri', 'codeparam', 'htmlonly'));
    }

    /*!
     * @method      getRecommendedOffer
     * @public
     * @param       str     $code
     * @return      object
     */
    public function getRecommendedOffer($duration=6, $isPremium=1)
    {
        return $this->get("/ws/offer/recommended/$duration/$isPremium");
    }

    /*!
     * @method      getAccount
     * @public
     * @param       Drupal\Core\Session\AccountInterface    $user
     * @return      object
     */
    public function getAccount(AccountInterface $user)
    {
        return $this->get("/ws/account/".$user->id());
    }

    /*!
     * @method      getAccountSummaryContent
     * @public
     * @param       Drupal\Core\Session\AccountInterface    $user
     * @return      object
     */
    public function getAccountSummaryContent(AccountInterface $user)
    {
        $content  = '';
        $response = $this->get("/ws/account/".$user->id()."/summary");
        if (!is_null($response) && $response->done) {
            $content = $response->data->html;
        }
        return $content;
    }

    /*!
     * @method      getTunnelStep1
     * @public
     * @param       str     $qstr
     * @param       [assoc] $data
     * @return      object
     */
    public function getTunnelStep1($qstr = '', $data)
    {
        return $this->post('/ws/tunnel/step1/get'.$qstr, $data);
    }

    /*!
     * @method      getTunnelStep2
     * @public
     * @param       [assoc] $data
     * @return      object
     */
    public function getTunnelStep2($data)
    {
        return $this->post('/ws/tunnel/step2/get', $data);
    }

    /*!
     * @method      getTunnelStep3
     * @public
     * @param       [assoc] $data
     * @return      object
     */
    public function getTunnelStep3($data)
    {
        return $this->post('/ws/tunnel/step3/get', $data);
    }

    /*!
     * @method      getTunnelData
     * @public
     * @param       [assoc] $data
     * @return      object
     */
    public function getTunnelData($data)
    {
        return $this->post('/ws/tunnel/data/get', $data);
    }

    /*!
     * @method      getUserInfosContent
     * @public
     * @param       Drupal\Core\Session\AccountInterface    $user
     * @param       [assoc]                                 $data
     * @return      object
     */
    public function getUserInfosContent(AccountInterface $user, $data)
    {
        $response = $this->post("/ws/user/".$user->id()."/infos", $data);
        if (empty($data) && !is_null($response) && $response->done===false) {
            $response->data = $response->msg;
        }
        return !is_null($response) ? $response->data : 'service momentanément indisponible';
    }

    /*!
     * @method      saveUserInfos
     * @public
     * @param       Drupal\Core\Session\AccountInterface    $user
     * @param       [assoc]                                 $data
     * @return      object
     */
    public function saveUserInfos(AccountInterface $user, $data)
    {
        return $this->post("/ws/user/".$user->id()."/infos/save", $data);
    }

    /*!
     * @method      getUserPasswordForm
     * @public
     * @param       int             $idUser
     * @param       string|null     $token
     *
     * @return      string
     * @throws      Exception
     */
    public function getUserPasswordForm($idUser, $token = null)
    {
        $response = $this->get("/ws/user/$idUser/password-form?token=$token");
        if (!$response) {
            throw new \Exception('service momentanément indisponible');
        }
        if ($response->done === false) {
            throw new \Exception($response->msg);
        }
        return $response->data;
    }

    /*!
     * @method      saveUserPassword
     * @public
     * @param       Drupal\Core\Session\AccountInterface    $user
     * @param       [assoc]                                 $data
     *
     * @return      bool
     * @throws      Exception
     */
    public function saveUserPassword(AccountInterface $user, $data)
    {
        $response = $this->post("/ws/user/" . $user->id() . "/update-password", $data);
        if (!$response) {
            throw new \Exception('service momentanément indisponible');
        }
        if ($response->done === false) {
            throw new \Exception($response->msg);
        }
        return true;
    }

    /*!
     * @method      getNoticePriceContent
     * @public
     * @param       str     $mode
     * @return      object
     */
    public function getNoticePriceContent($mode='mail')
    {
        $content  = '';
        $response = $this->get("/ws/tunnel/notice/$mode/get");
        if (!is_null($response) && $response->done) {
            $content = $response->data;
        }
        return $content;
    }

    /*!
     * -2   : unknowed
     * -1   : drupal account without subscription
     * 10   : awaiting payment
     *  1   : active subscription
     *  2   : paused subscription
     *  3   : active no renewal subscription
     *  4   : closed subscription
     *  101 : active old subscription
     *
     * @method      checkSubscriptionStatusByEmail
     * @public
     * @param       str     $email
     * @return      object
     */
    public function checkSubscriptionStatusByEmail($email)
    {
        return $this->post("/ws/client/check/$email");
    }

    /*!
     * @method      getStatusEmailUnknowned
     * @public
     * @return      int
     */
    public function getStatusEmailUnknowned()
    {
        return static::WS_EMAIL_AVAILABLE_UNKNOWNED;
    }

    /*!
     * @method      updateSvt
     * @public
     * @return      object
     */
    public function updateSvt($idUser, $idSession=null)
    {
        if (empty($idSession)) {
            $idSession = Crypt::hashBase64(\Drupal::service('session')->getId());
        }
        return $this->post("/ws/svt/$idUser/update", compact('idSession'));
    }

    /*!
     * @method      validateInsuredToken
     * @public
     * @param       str     $acktoken
     * @param       bool    $remove
     * @param       bool    $renew
     * @return      object
     */
    public function validateInsuredToken($acktoken, $remove=false, $renew=false)
    {
        return $this->post('/ws/insured/token/validate', compact('acktoken', 'remove', 'renew'));
    }

    /*!
     * @method      getResetPasswordToken
     * @public
     * @param       Drupal\Core\Session\AccountInterface    $user
     * @return      object
     */
    public function getResetPasswordToken(AccountInterface $user)
    {
        return $this->get(sprintf('/ws/user/%s/reset-password-token', $user->id()));
    }

    /*!
     * @method      buyAnnex
     * @public
     * @param       Drupal\Core\Session\AccountInterface    $user
     * @param       [assoc]                                 $data
     * @return      object
     */
    public function buyAnnex(AccountInterface $user, $data)
    {
        $response = $this->post(sprintf('/ws/user/%s/subscription/buy-annex', $user->id()), $data);
        if (!$response) {
            throw new \Exception('Service momentanément indisponible');
        }
        if ($response->done === false) {
            throw new \Exception($response->msg);
        }
        return true;
    }

    /*!
     * @method      getLoginForm
     * @public
     * @return      str
     */
    public function getLoginForm()
    {
        $response = $this->get('/ws/user/login');
        if (!$response) {
            throw new \Exception('Service momentanément indisponible');
        }
        if ($response->done === false) {
            throw new \Exception($response->msg);
        }
        return $response->data;
    }

    /*!
     * @method      authenticateUser
     * @public
     * @param       str     $login
     * @param       str     $password
     * @param       str     $token
     * @return      str
     */
    public function authenticateUser($login, $password, $token)
    {
        $response = $this->post('/ws/user/login-check', compact('login', 'password', 'token'));
        if (!$response) {
            throw new \Exception('Service momentanément indisponible');
        }
        if ($response->done === false) {
            throw new \Exception($response->msg, $response->code);
        }
        return $response->data;
    }
}
