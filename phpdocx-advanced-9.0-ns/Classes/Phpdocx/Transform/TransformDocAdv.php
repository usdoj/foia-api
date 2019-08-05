<?php
namespace Phpdocx\Transform;
use Phpdocx\Clean\CleanTemp;
use Phpdocx\Create\CreateDocx;
use Phpdocx\Logger\PhpdocxLogger;
use Phpdocx\Parse\RepairPDF;
use Phpdocx\Utilities\PhpdocxUtilities;


/**
 * Transform DOCX to PDF, ODT, SXW, RTF, DOC, TXT, HTML or WIKI
 *
 * @category   Phpdocx
 * @package    trasform
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @version    2019.01.31
 * @link       https://www.phpdocx.com
 */

abstract class TransformDocAdv
{
    /**
     *
     * @access protected
     * @var array
     */
    protected $phpdocxconfig;

    /**
     * Construct
     *
     * @access public
     */
    public function __construct()
    {
        $this->phpdocxconfig = PhpdocxUtilities::parseConfig();
    }

    /**
     * Transform document formats
     *
     * @access public
     *
     * @abstract
     * @param $source
     * @param $target
     * @param array $options
     */
    abstract public function transformDocument($source, $target, $options = array());

    /**
     * Check if the extension if supproted
     * 
     * @param string $fileExtension
     * @param array $supportedExtensions
     * @return array files extensions
     */
    protected function checkSupportedExtension($source, $target, $supportedExtensionsSource, $supportedExtensionsTarget) {
        // get the source file info
        $sourceFileInfo = pathinfo($source);
        $sourceExtension = strtolower($sourceFileInfo['extension']);

        if (!in_array($sourceExtension, $supportedExtensionsSource)) {
            PhpdocxLogger::logger('The chosen extension \'' . $sourceExtension . '\' is not supported as source format.', 'fatal');
        }

        // get the target file info
        $targetFileInfo = explode('.', $target);
        $targetExtension = strtolower(array_pop($targetFileInfo));

        if (!in_array($targetExtension, $supportedExtensionsTarget)) {
            PhpdocxLogger::logger('The chosen extension \'' . $targetExtension . '\' is not supported as target format.', 'fatal');
        }

        return array('sourceExtension' => $sourceExtension, 'targetExtension' => $targetExtension);
    }
}