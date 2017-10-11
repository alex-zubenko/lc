<?php
namespace Drupal\mtc_core\Utils;

use Drupal\Core\Site\Settings;


/*!
 * SecuredRedirectResponse for mtc
 * do not use TrustedRedirectResponse because drupal use cache for it !
 *
 * @package     Drupal\mtc_core\Service
 * @class       SecuredRedirectResponse
 * @author      Matthieu Beunon
 * @date        2017-03-02 18:48:06 CET
 */
class SecuredRedirectResponse extends \Drupal\Component\HttpFoundation\SecuredRedirectResponse
{
    /**
     * {@inheritdoc}
     */
    protected function isSafe($url)
    {
        $trusted = false;
        foreach(Settings::get('trusted_host_patterns') as $pattern) {
            if (preg_match("/$pattern/", $url, $matches) !== false) {
                $trusted = true;
                break;
            }
        }
        return $trusted;
    }
}
