<?php
namespace Drupal\mtc_core\Service;

use Drupal\Core\Site\Settings;
use Symfony\Component\Yaml\Yaml;

/*!
 * Service to manage mtc configuration
 *
 * @package     Drupal\mtc_core\Service
 * @class       ConfigService
 * @author      Matthieu Beunon
 * @date        2017-02-09 13:25:46 CET
 */
class ConfigService
{
    /*! @constant ROOT_KEY */
    const ROOT_KEY = 'mtc';

    /* @protected @var str $path */
    protected $path;

    /*! @protected @static @var [assoc] $availableConf */
    protected static $availableConf;

    /*!
     * @constructor
     * @public
     * @throw       UnexpectedValueException
     */
    public function __construct()
    {
        if (is_null($main = Settings::get(static::ROOT_KEY))) {
            throw new \Exception("missing configuration file for mtc. Check \$settings['".static::ROOT_KEY."']");
        }
        $this->config = Yaml::parse($main);
        $this->path   = dirname($main);
        if (isset($this->config['import'])) {
            foreach ($this->config['import'] as $key => $path) {
                if (!file_exists($this->path . '/'. $path)) {
                    throw new \UnexpectedValueException("wrong value for config $key : file not found ($path)");
                }
                $this->config[$key] = Yaml::parse($this->path . '/'. $path);
            }
            unset($this->config['import']);
        }
        static::$availableConf = array_keys($this->config);
    }

    /*!
     * @method      isAvailable
     * @public
     * @param       str     $name;
     */
    public function isAvailable($name)
    {
        return in_array($name, static::$availableConf);
    }

    /*!
     * @method      isProd
     * @public
     * @return      bool
     */
    public function isProd()
    {
        return $this->config['env']['prod'] == true;
    }

    /*!
     * @method      get
     * @public
     * @param       str     $name;
     * @throw       UnexpectedValueException
     * @return      [assoc]
     */
    public function get($name) {
        if (!$this->isAvailable($name)) {
            throw new \UnexpectedValueException("config $name is not available");
        }
        return $this->config[$name];
    }

}
