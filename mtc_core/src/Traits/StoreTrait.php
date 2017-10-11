<?php
namespace Drupal\mtc_core\Traits;

use Mtc\Core\Util\Tool;

/*!
 * @package     Drupal\mtc_core\Traits
 * @trait       StoreTrait
 *
 * @author      Matthieu Beunon
 * @date        2017-02-06 17:31:05 CET
 */
trait StoreTrait
{
    /*!
     * @method      setStoreData
     * @public
     * @param       str     $key
     * @param       str     $value
     */
    public function setStoreData($key, $value)
    {
        $this->store->set($key, $value);
        $this->store->set("_init_$key", Tool::now());
    }

    /*!
     * @method      getStoreData
     * @public
     * @param       str     $key
     * @param       int     $expire
     */
    public function getStoreData($key, $expire=3600)
    {
        return !$this->isDataExpired($key, $expire) ? $this->store->get($key) : null;
    }

    /*!
     * @method      isDataExpired
     * @public
     * @param       str     $key
     * @param       int     $expire
     */
    public function isDataExpired($key, $expire=3600)
    {
        return !empty($initDate = $this->store->get("_init_$key")) && Tool::genNowTimeDiff($initDate) >= $expire;
    }
}
