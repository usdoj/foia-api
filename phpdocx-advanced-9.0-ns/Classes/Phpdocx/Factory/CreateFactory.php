<?php
namespace Phpdocx\Factory;
/**
 * Simple factory, creates objects to add in other elements
 *
 * @category   Phpdocx
 * @package    factory
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @version    2017.09.11
 * @link       https://www.phpdocx.com
 */
class CreateFactory
{

    /**
     * Create an object
     *
     * @access public
     * @param string $type Object type
     * @return mixed
     * @static
     */
    public static function createObject($type)
    {
        return new $type();
    }

}
