<?php
namespace Drupal\mtc_core\Service;

use Drupal\Core\Site\Settings;
use Symfony\Component\Yaml\Yaml;
use Swift_SmtpTransport;
use Swift_Mailer;
use Swift_Message;

/*!
 * Service To manage SwiftMailer
 * 
 * @package     Drupal\mtc_core\Service
 * @class       SwiftMailerService
 * @author      Matthieu Beunon
 * @date        2017-02-09 13:17:58 CET
 */
class SwiftMailerService
{
    /*! @protected @var [] $config */
    protected $config;
    /*! @protected @var [Swift_Mailer] $mailer */
    protected $mailer;

    /*!
     * @constructor
     * @public
     */
    public function __construct()
    {
        $this->config = \Drupal::service('mtc_core.config')->get('swift');
    }

    /*!
     * @method      getMailer
     * @public
     * @param       $str    $name
     * @return      Swift_Mailer
     */
    public function getMailer($name)
    {
        if (!isset($this->config['mailer'][$name])) {
            throw new \Exception("mailer configuration $name is not available");
        }
        if (!isset($this->mailer[$name])) {
            $c = $this->config['mailer'][$name];
            $transport = Swift_SmtpTransport::newInstance($c['host'], $c['port'], $c['encryption'])
                ->setUsername($c['username'])->setPassword($c['password']);
            $this->mailer[$name] = Swift_Mailer::newInstance($transport);
        }
        return $this->mailer[$name];
    }

    /*!
     * @method      send
     * @public
     * @param       $str    $name
     * @param       $str    $email
     * @param       $str    $subject
     * @param       $str    $html
     * @param       $str    $type
     * @return      bool
     */
    public function send($name, $email, $subject, $html, $type='gmail')
    {
        if (!\Drupal::service('mtc_core.config')->isProd()) {
            $email   = $this->config['params']['mail.redirect'];
        }
        try {
            $mailer  = $this->getMailer($type);
            $from    = [$this->config['params']['from.mail'] => $this->config['params']['from.name']];
            $message = Swift_Message::newInstance()
                ->setSubject($subject)
                ->setFrom($from)
                ->setTo([$email => $name])
                ->setBody($html, 'text/html');
            if (!($done = $mailer->send($message))) {
                $done = false;
            }
        }
        catch (\Exception $e) {
            $done = false;
            // @todo log mail
        }
        return $done;
    }
}
