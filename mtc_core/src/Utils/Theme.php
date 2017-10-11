<?php
namespace Drupal\mtc_core\Utils;

/*!
 * Theme utility
 *
 * @package         Drupal\mtc_core\Utils
 * @class           Theme
 * @author          Matthieu Beunon
 * @date            2017-02-09 16:08:19 CET
 */
class Theme {

    /*!
     * used by linecoaching_theme in mtc_core.module
     *
     * @method      tpl
     * @public
     * @static
     * @param       [assoc]     $variables
     * @param       str         $dir
     * @param       str         $module
     */
    public static function tpl($variables, $dir='', $module = 'mtc_core')
    {
        if (!empty($dir)) {
            $path = drupal_get_path('module', 'mtc_core')."/templates/$dir";
        }
        return compact('variables', 'path');
    }

    /*!
     * prepare a twig template to be rendered with appropriates $vars variables
     *
     * @method      twigRender
     * @protected
     * @param       str     $tplName
     * @param       [assoc] $vars
     * @param       int     $maxAge
     * @return      [assoc]
     */
    public static function prepareTwigRender($tplName, $vars, $maxAge=0)
    {
        $data = ['#theme' => $tplName, '#cache' => [ 'max-age' => $maxAge]];
        foreach($vars as $k => $v) {
            $data["#$k"] = $v;
        }
        return $data;
    }
}
