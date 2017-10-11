<?php
namespace Drupal\mtc_core\Manager;

use Drupal\mtc_core\Manager\AbstractMailManager;
use Drupal\Core\Session\AccountInterface;

/*!
 * desc
 * 
 * @class          MailManager
 * @package        Drupal\mtc_core\Manager
 * @author         Matthieu Beunon
 * @date           2017-02-10 11:21:11 CET
 */
class MailManager extends AbstractMailManager
{
    /*!
     * @method      confirmOrder
     * @public
     * @param       Drupal\Core\Session\AccountInterface    $user
     * @param       object                                  $data
     * @param       str                                     $typeMailer
     */
    public function confirmOrder(AccountInterface $user, $data, $typeMailer=MailManager::DEFAULT_MAILER)
    {
        $key = 'lc_subscription_order_confirm';
        return $this->send($key, $user, $data, $typeMailer);
    }

    /*!
     * @method      confirmOrder
     * @public
     * @param       Drupal\Core\Session\AccountInterface    $user
     * @param       object                                  $data
     * @param       str                                     $typeMailer
     */
    public function talkProposal(AccountInterface $user, $data, $typeMailer=MailManager::DEFAULT_MAILER)
    {
        $key = 'lc_talk_proposal';
        return $this->send($key, $user, $data, $typeMailer);
    }

    /*!
     * @method      resetPassword
     * @public
     * @param       Drupal\Core\Session\AccountInterface    $user
     * @param       object                                  $data
     * @param       str                                     $typeMailer
     */
    public function resetPassword(AccountInterface $user, $data, $typeMailer = MailManager::DEFAULT_MAILER)
    {
        return $this->send('lc_reset_password', $user, $data, $typeMailer);
    }
}
