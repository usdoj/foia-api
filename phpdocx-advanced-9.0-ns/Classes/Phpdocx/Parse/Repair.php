<?php
namespace Phpdocx\Parse;

use Phpdocx\Logger\PhpdocxLogger;
/**
 * Repair docx files cleaning, removing or adding content
 *
 * @category   Phpdocx
 * @package    parser
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @version    2017.09.11
 * @link       https://www.phpdocx.com
 */
class Repair
{

    /**
     *
     * @access private
     * @var string
     */
    private static $_instance = NULL;

    /**
     *
     * @access private
     * @var array
     */
    private $_xml = array();

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
     * Magic method, returns current XML
     *
     * @access public
     * @return string Return current XML
     */
    public function __toString()
    {
        return $this->_xml;
    }

    /**
     * Singleton, return instance of class
     *
     * @access public
     * @return CreateText
     * @static
     */
    public static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new Repair();
        }
        return self::$_instance;
    }

    /**
     * Getter XML
     *
     * @access public
     */
    public function getXML()
    {
        return $this->_xml;
    }

    /**
     * Setter XML
     *
     * @access public
     */
    public function setXML($xml)
    {
        $this->_xml = $xml;
    }

    /**
     * Add a paragraph to each element in a table that needs it and betweeen
     * tables
     * 
     * @access public
     * @param  $path File path
     */
    public function addParapraphEmptyTablesTags()
    {
        // add parapraph to <w:tc></w:tc>
        $this->_xml = preg_replace('/<w:tc>[\s]*?<\/w:tc>/', '<w:tc><w:p /></w:tc>', $this->_xml);

        // add parapraph to </w:tbl></w:tc>
        $this->_xml = preg_replace('/<\/w:tbl>[\s]*?<\/w:tc>/', '</w:tbl><w:p /></w:tc>', $this->_xml);

        // add parapraph to </w:tbl><w:tbl>
        $this->_xml = preg_replace('/<\/w:tbl>[\s]*?<w:tbl>/', '</w:tbl><w:p /><w:tbl>', $this->_xml);
    }

    /**
     * Modifies the DOCXPath selections to avoid validation issues
     * 
     * @access public
     * @param  DOMNode $node
     * @static
     */
    public static function repairDOCXPath($node)
    {
        //modifies the id attribute of the wp:docPr tag to avoid potential conflicts
        $docPrNodes = $node->getElementsByTagName('docPr');
        foreach ($docPrNodes as $docPrNode) {
            $docPrNode->setAttribute('id', rand(999999, 99999999));
        }
        return $node;
    }

}
