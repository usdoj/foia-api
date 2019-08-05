<?php
namespace Phpdocx\Clean;
/**
 * Delete temp files and folders.
 *
 * @category   Phpdocx
 * @package    clean
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @version    2017.09.11
 * @link       https://www.phpdocx.com
 */
class CleanTemp
{

    /**
     * Construct
     *
     * @access private
     */
    private function __construct()
    {
        
    }

    /**
     * Destruct
     *
     * @access public
     */
    public function __destruct()
    {
        
    }

    /**
     * Delete files and folders
     *
     * @param string $path To delete
     */
    public static function clean($path)
    {
        if (is_file($path)) {
            @unlink($path);
        }
        if (!$dh = @opendir($path)) {
            return;
        }
        while (($obj = readdir($dh))) {
            if ($obj == '.' || $obj == '..') {
                continue;
            }
            if (!@unlink($path . '/' . $obj)) {
                self::clean($path . '/' . $obj);
            }
        }

        closedir($dh);
        @rmdir($path);
    }

}
