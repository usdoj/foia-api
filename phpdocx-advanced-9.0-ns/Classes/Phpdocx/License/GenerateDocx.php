<?php
namespace Phpdocx\License;
use Phpdocx\Utilities\PhpdocxUtilities;
/**
 * Check for a valid license
 *
 * @category   Phpdocx
 * @package    license
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @version    2017.09.11
 * @link       https://www.phpdocx.com
 */
class GenerateDocx
{

    /**
     * Check for a valid license
     *
     * @access public
     * @return boolean
     */
    public static function beginDocx()
    {
        $xzerod = '';
        $xzeroc = '';
        $xzeroi = '';
        $phpdocxconfig = PhpdocxUtilities::parseConfig();

        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return;
        }

        if (!isset($_SERVER['SERVER_NAME'])) {
            return;
        } else {
            $xzerod = trim($phpdocxconfig['license']['code']);
            $xzeroc = trim($_SERVER['SERVER_NAME']);
            $xzeroi = trim($_SERVER['SERVER_ADDR']);

            if (empty($xzeroi)) {
                $xzeroi = $xzeroc;
            }
        }
        if (
            preg_match('/^192.168./', $xzeroi) ||
            preg_match('/^172./', $xzeroi) ||
            preg_match('/^10./', $xzeroi) ||
            preg_match('/^127./', $xzeroi) ||
            preg_match('/^::1/', $xzeroi) ||
            preg_match('/localhost/', $xzeroc)
        ) {
            return;
        } elseif ($xzerod == md5($xzeroc . '_basic_docx')) {
            return;
        } elseif ($xzerod == md5($xzeroc . '_advanced_docx')) {
            return;
        } elseif ($xzerod == md5($xzeroc . '_premium_docx')) {
            return;
        } elseif ($xzerod == md5($xzeroi . '_premium_docx')) {
            return;
        }

        if (!preg_match('/^www./', $xzeroc)) {
            $xzeroc = 'www.' . $xzeroc;
        }
        if ($xzerod == md5($xzeroc . '_basic_docx')) {
            return;
        } elseif ($xzerod == md5($xzeroc . '_advanced_docx')) {
            return;
        } elseif ($xzerod == md5($xzeroc . '_premium_docx')) {
            return;
        }

        $serverNameSeg = explode('.', trim($_SERVER['SERVER_NAME']));
        $serverNamePart = '';
        $serverNameSegI = count($serverNameSeg);
        for ($i = $serverNameSegI-1; $i >= 0; $i--) { 
            if (empty($serverNamePart)) {
                $serverNamePart = $serverNameSeg[$i];
            } else {
                $serverNamePart = $serverNameSeg[$i] . '.' . $serverNamePart;
            }
            if ($xzerod == md5($serverNamePart . '_basic_docx')) {
                return;
            } elseif ($xzerod == md5($serverNamePart . '_advanced_docx')) {
                return;
            } elseif ($xzerod == md5($serverNamePart . '_premium_docx')) {
                return;
            }
        }

        throw new \Exception('There is not a valid license');
    }

}
