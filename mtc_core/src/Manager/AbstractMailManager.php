<?php
namespace Drupal\mtc_core\Manager;

use Drupal\Core\Session\AccountInterface;
use Drupal\mtc_core\Utils\Theme;

/*!
 * desc
 * 
 * @package        Drupal\mtc_core\Manager
 * @class          AbstractMailManager
 * @abstract
 * @author         Matthieu Beunon
 * @date           2017-02-10 11:20:11 CET
 */
abstract class AbstractMailManager
{
    const DEFAULT_MAILER = 'gmail';

    /*! @protected @var [assoc] $config */
    protected $config;

    /*!
     * @constructor
     * @public
     */
    public function __construct()
    {
        $this->config = \Drupal::service('mtc_core.config')->get('mail');
    }

    /*!
     * @method      getSubject
     * @public
     * @param       str     $key
     * @return      str
     */
    public function getSubject($key)
    {
        $subject = '';
        if (isset($this->config['subjects'][$key])) {
            $subject = $this->config['subjects'][$key];
        }
        return $subject;
    }

    /*!
     * @method      send
     * @public
     * @param       str                                     $key
     * @param       Drupal\Core\Session\AccountInterface    $user
     * @param       object                                  $data
     * @param       str                                     $typeMailer
     */
    public function send($key, $user, $data, $typeMailer)
    {
        $content = Theme::prepareTwigRender($key, compact('data'));
        $swift   = \Drupal::service('mtc_core.swiftmailer');
        return $swift->send($user->getAccountName(), $user->getEmail(), $this->getSubject($key), render($content), $typeMailer);
    }
}
