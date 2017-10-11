<?php
namespace Drupal\mtc_core\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Drupal\mtc_core\Utils\SecuredRedirectResponse;
//~ use Drupal\Core\Routing\TrustedRedirectResponse;
use \Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\user\Entity\User;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * kernel controller listener to perform insured user autologin
 *
 * @package     Drupal\mtc_core\EventSubscriber
 * @class       InitSubscriber
 * @author      Matthieu Beunon
 * @date        2017-03-01 16:21:04 CET
 */
class InitSubscriber implements EventSubscriberInterface
{
   /**
    * {@inheritdoc}
    */
    static function getSubscribedEvents()
    {
        // do not use 'kernel.request' because drupal user is not initialized
        $events[KernelEvents::CONTROLLER] = ['onRequest'];
        $events[KernelEvents::REQUEST][]  = ['initializeMtcCore'];
        return $events;
    }

   /**
    * @public
    * @param    \Symfony\Component\HttpFoundation\ParameterBag  $cookies
    */
    public function autologinInsuredUser($cookies)
    {
        // passthrue no insured user
        $done = true;
        if (!is_null($cookies) && $cookies->has('mediation_insured')) {
            $done = false;
            $resp = \Drupal::service('mtc_core.ws_client')->validateInsuredToken($cookies->get('mediation_insured'), false);
            if (!is_null($resp) && $resp->done) {
                $session = \Drupal::service('session');
                if (!isset($_SESSION['mtc_core']['anonymous'])) {
                    $_SESSION['mtc_core']['anonymous'] = 1;
                    $session->start();
                }
                user_login_finalize(User::load($resp->data->idUser, true));
                $session->migrate();
                unset($_SESSION['mtc_core']);
                $done = true;
            }
        }
        return $done;
    }

   /**
    * This method is called whenever the kernel.controller event is
    * dispatched.
    *
    * @param Symfony\Component\HttpKernel\Event\FilterControllerEvent   $event
    */
    public function onRequest(FilterControllerEvent $event)
    {
        if (\Drupal::currentUser()->isAnonymous()) {
            if (!$this->autologinInsuredUser($event->getRequest()->cookies)) {
                $config   = \Drupal::service('mtc_core.config')->get('portal');
                $route    = $event->getRequest()->attributes->get('_route');
                $uri      = $config['redirect'];
                if ($route != 'mtc_core.mtc.api.prelogin') {
                    $event->setController(function() use ($uri) {
                        //~ $response->headers->clearCookie('mediation_insured');
                        return new SecuredRedirectResponse($uri);
                    });
                }
            };
        }
    }

   /**
    * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
    *   The event to process.
    */
    public function initializeMtcCore(GetResponseEvent $event)
    {
        $this->initPartnership($event);
    }

   /**
    * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
    *   The event to process.
    */
    public function initPartnership(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $referer = $request->server->get('HTTP_REFERER');
        $config  = \Drupal::service('mtc_core.config')->get('site');

        foreach(['axa'] as $insurer) {
            $target     = $config['insurer'][$insurer]['target'];
            $ref        = $config['insurer'][$insurer]['referer'];
            $cookieVal  = $config['insurer'][$insurer]['cookie_value'];
            $expire     = $config['insurer'][$insurer]['expire'];
            $cookieName = $config['insurer']['partnership_cookie_name'];

            if (!empty($target) && $request->getPathInfo() == $target) {
                \Drupal::service('page_cache_kill_switch')->trigger();
                if (!empty($expire) && !empty($ref) && !empty($cookieVal) && !empty($referer) && $referer == $ref) {
                    setcookie($cookieName, $insurer . ':' . $cookieVal, strtotime($expire), '/');
                }
            }
        }
    }
}
