<?php
namespace Drupal\mtc_core\Service;

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
class MtcAuthService
{
    /* @protected @var MetaTech\PwsAuth\Authenticator $authenticator */
    protected $authenticator;
    /* @protected @var str $login */
    protected $login;
    /* @protected @var str $key */
    protected $key;

    /*!
     * @constructor
     * @public
     */
    public function __construct()
    {
        $cfg                 = \Drupal::service('mtc_core.config')->get('ws');
        $this->login         = $cfg['login'];
        $this->key           = $cfg['key'];
        $this->authenticator = new Authenticator(\Drupal::service('mtc_core.config')->get('auth'));
    }

    /*!
     * @method      check
     * @public
     * @param       str     $apitkn
     * @param       str     $apikey
     * @return      bool
     */
    public function check($apitkn = null, $apikey = null)
    {
        $authenticated = false;
        try {
            if (!empty($apitkn) && !empty($apikey) && $apikey == $this->key) {
                $token         = $this->authenticator->getTokenFromString($apitkn, $apikey);
                $authenticated = $this->authenticator->isValid($token) && $this->authenticator->check($token, $this->login);
            }
        }
        catch(\Exception $e) {

        }
        return $authenticated;
    }
}
