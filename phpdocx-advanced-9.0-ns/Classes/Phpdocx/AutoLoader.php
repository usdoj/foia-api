<?php
namespace Phpdocx;
/**
 * Autoloader
 *
 * @category   Phpdocx
 * @package    loader
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @version    2018.02.07
 * @link       https://www.phpdocx.com
 */

class AutoLoader
{

    /**
     * Main tags of relationships XML
     *
     * @access public
     * @static
     */
    public static function load()
    {
        spl_autoload_register(array('Phpdocx\AutoLoader', 'autoloadGenericClasses'));
    }

    /**
     * Autoload phpdocx
     *
     * @access public
     * @param string $className Class to load
     */
    public static function autoloadGenericClasses($namespace)
    {
        $splitpath = explode('\\', $namespace);
        $path = '';
        $name = '';
        $firstword = true;
        for ($i = 0; $i < count($splitpath); $i++) {
            if ($splitpath[$i] && !$firstword) {
                if ($i == count($splitpath) - 1){
                    $name = $splitpath[$i];
                }
                else{
                    $path .= DIRECTORY_SEPARATOR . $splitpath[$i];
                }
            }
            if ($splitpath[$i] && $firstword) {
                if ($splitpath[$i] != __NAMESPACE__){
                    break;
                }
                $firstword = false;
            }
        }
        if (!$firstword) {
            $fullpath = __DIR__ . $path . DIRECTORY_SEPARATOR . $name . '.php';
            if (file_exists($fullpath)) {
                return include_once($fullpath);
            }
            
        }
        return false;
    }

}
