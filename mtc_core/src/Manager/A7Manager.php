<?php

/**
 * @file
 * Contains \Drupal\mtc_core\ManagerSvtManager.
 */
namespace Drupal\mtc_core\Manager;

use Drupal\Core\Database\Database;
use Drupal\mtc_core\Manager\A7ManagerInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Class A7Manager.
 *
 * @package Drupal\mtc_core
 */
class A7Manager implements A7ManagerInterface
{

    /**
     * The entity manager.
     *
     * @var \Drupal\Core\Entity\EntityManagerInterface
     */
    protected $entityManager;

    /** @var url  url for content update */
    protected $a7UrlUpdate;

    /** @var url  url to check if email exists in a7 database */
    protected $a7UrlCheckEmail;

    /** @var url  url used for sending emails */
    protected $a7UrlSendEmail;

    /** @var string  table used for storing a7 mailing reuqest */
    protected $a7MailingTable;

    /**
     * @constructor
     */
    public function __construct()
    {
        $config                = \Drupal::service('mtc_core.config')->get('a7');
        $this->a7UrlUpdate     = $config['api'] . '/' . $config['update'];
        $this->a7UrlCheckEmail = $config['api'] . '/' . $config['check'];
        $this->a7UrlSendEmail  = $config['api'] . '/' . $config['send'];
        $this->a7MailingTable  = $config['table'];
    }

    /**
     * @param       str         $mail
     * @return      [assoc]
     */
    public function addNewsletterSubscription($mail)
    {
        $conn = Database::getConnection();
        if (empty($mail)) {
            return ['status' => FALSE, 'code' => 0];
        }
        if (!\Drupal::service('email.validator')->isValid($mail)) {
            return ['status' => FALSE, 'code' => 1];
        }
        $host = explode('@', $mail);
        $host = $host[1];
        if ($socket = @fsockopen($host, 80, $errno, $errstr, 7)) {
            fclose($socket);
        }
        else {
            return ['status' => FALSE, 'code' => 2];
        }

        $url = $this->a7UrlUpdate . "?abonnement_newsletter=Y&email=$mail&optin=1";
        // insert email
        $conn->insert($this->a7MailingTable)->fields([
            'email'   => $mail,
            'etat'    => A7Manager::STATE_NEW,
            'requete' => $url,
        ])->execute();

        return ['status' => TRUE, 'code' => 200];
    }
}
