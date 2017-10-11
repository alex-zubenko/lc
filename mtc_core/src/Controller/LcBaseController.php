<?php
namespace Drupal\mtc_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Drupal\Core\Session\SessionManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\mtc_core\Traits\StoreTrait;
use Drupal\mtc_core\Service\SwiftMailerService;
use Drupal\mtc_core\Utils\Theme;

/*!
 * @package     Drupal\mtc_core\Controller
 * @class       LcBaseController
 * @extends     Drupal\Core\Controller\ControllerBase
 * @author      Matthieu Beunon
 * @date        2017-01-06 17:42:05 CET
 */
abstract class LcBaseController extends ControllerBase
{
    use StoreTrait;

    /*! @protected @var Drupal\mtc_core\Service\WsClientService $ws */
    protected $ws;
    /*! @protected @var Drupal\Core\Session\AccountProxy $user */
    protected $user;
    /*! @protected @var Drupal\user\PrivateTempStore $store */
    protected $store;
    /*! @protected @var Drupal\Core\Session\SessionManager $sessionManager */
    protected $sessionManager;

    /*!
     * @constructor
     * @public
     * @param       Drupal\Core\Session\SessionManager      $sessionManager
     * @param       Drupal\Core\Session\AccountInterface    $user
     * @param       Drupal\user\PrivateTempStoreFactory     $tempStore
     */
    public function __construct(SessionManager $sessionManager, AccountInterface $user, PrivateTempStoreFactory $tempStore)
    {
        $this->user           = $user;
        $this->sessionManager = $sessionManager;
        $this->store          = $tempStore->get('mtc_core');
        // @mbe :
        // drupal didn't manage session for anonymous user
        // we must start session manually
        if ($this->user->isAnonymous() && !isset($_SESSION['mtc_core']['anonymous'])) {
            $_SESSION['mtc_core']['anonymous'] = 1;
            $this->sessionManager->start();
        }
        $this->init();
    }

    /*!
     * @method      create
     * @public
     * @static
     * @return      Drupal\mtc_core\Controller\LcBaseController
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('session_manager'),
            $container->get('current_user'),
            $container->get('user.private_tempstore')
        );
    }

    /*!
     * @method      init
     * @protected
     */
    protected function init()
    {
    }

    /*!
     * @method      getRouteName
     * @protected
     * @return      str
     */
    public function getRouteName()
    {
        return \Drupal::service('current_route_match')->getRouteName();
    }

    /*!
     * @method      getRouteByUri
     * @param       str     $uri
     * @protected
     */
    public function getRouteByUri($uri)
    {
        $url   = \Drupal::service('path.validator')->getUrlIfValid($uri);
        $route = !$url ? null : $url->getRouteName();
        return $route;
    }

    /*!
     * @method      getLang
     * @protected
     * @return      int
     */
    protected function getLang()
    {
        return \Drupal::languageManager()->getCurrentLanguage()->getId();
    }

    /**
     * get mtc rest client service
     *
     * @method      getWs
     * @protected
     * @return      \Drupal\mtc_core\Service\WsClientService
     */
    protected function getWs()
    {
        return \Drupal::service('mtc_core.ws_client');
    }

    /*!
     * send $type mail to $this->user with $data
     *
     * @method      mail
     * @protected
     * @return      bool
     */
    protected function mail($type, $data, $user=null)
    {
        $sent = false;
        if (!$this->user->isAnonymous()) {
            $sent = call_user_func_array([\Drupal::service('mtc_core.mail_manager'), $type], [is_null($user) ? $this->user : $user, $data]);
        }
        return $sent;
    }

    /*!
     * get user subscription account via ws
     *
     * @method      getSubscription
     * @protected
     * @return      object
     */
    protected function getSubscription($expire=3600)
    {
        $subscription = null;
        if (!$this->user->isAnonymous()) {
            $subscription = $this->getStoreData('subscription', $expire);
            if (is_null($subscription)) {
                $response = $this->getWs()->getAccount($this->user);
                if (!is_null($response) && $response->done) {
                    $subscription = $response->data->account;
                    $this->setStoreData('subscription', $subscription);
                }
            }
        }
        return $subscription;
    }

    /*!
     * check current request method
     *
     * @method      isMethod
     * @protected
     * @param       str     $method
     * @return      bool
     */
    protected function isMethod($method)
    {
        return \Drupal::request()->isMethod($method);
    }

    /*!
     * get the specifiyed post params in a uniq assoc array
     * use extract to get back the var wich is automatically loaded in current context (scope)
     *
     * @method      params
     * @protected
     * @param       [str]   $fields
     * @return      [assoc]
     */
    protected function params($fields)
    {
        foreach ($fields as $param) {
            $$param = \Drupal::request()->request->get($param);
        }
        return compact($fields);
    }

    /*!
     * get the specified post param
     *
     * @method      param
     * @public
     * @str         str     $name
     * @str         mixed   $defaultValue
     * @return      str
     */
    public function param($name, $defaultValue='')
    {
        $p = \Drupal::request()->request->get($name);
        return $p !='' ? $p : $defaultValue;
    }

    /*!
     * get the specifiyed get params in a uniq assoc array
     * use extract to get back the var wich is automatically loaded in current context (scope)
     *
     * @method      queries
     * @public
     * @param       [assoc]     $fields
     * @param       mixed       $defaultValues
     * @return      [assoc]
     */
    public function queries($fields, $defaultValues='')
    {
        foreach ($fields as $param){
            $$param = $this->query($param, $defaultValues);
        }
        return compact($fields);
    }

    /*!
     * get the specified get param
     *
     * @method      query
     * @public
     * @str         str     $name
     * @str         mixed   $defaultValue
     * @return      str
     */
    public function query($name, $defaultValue='')
    {
        $p = \Drupal::request()->query->get($name);
        return $p !='' ? $p : $defaultValue;
    }

    /*!
     * @method      concatParam
     * @public
     * @str         str     $param
     * @str         str     $qstr
     * @return      str
     */
    public function concatParam($params, $qstr='')
    {
        if (is_array($params)) {
            foreach($params as $k => $v) {
                $qstr .= (empty($qstr) ? '?' : '&') . "$k=$v";
            }
        }
        return $qstr;
    }

    /*!
     * render a twig template with appropriates $vars variables
     *
     * @method      twigRender
     * @protected
     * @param       str     $tplName
     * @param       [assoc] $vars
     * @param       int     $maxAge
     * @return      [assoc]
     */
    protected function twigRender($tplName, $vars, $maxAge=0)
    {
        return Theme::prepareTwigRender($tplName, $vars, $maxAge);
    }
}
