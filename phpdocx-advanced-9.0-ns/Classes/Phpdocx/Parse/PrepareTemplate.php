<?php
namespace Phpdocx\Parse;
/**
 * Prepares the templates for the conversion plugin
 *
 * @category   Phpdocx
 * @package    parser
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @version    2017.09.11
 * @link       https://www.phpdocx.com
 */
class PrepareTemplate
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
     * Prepares the template so it transforms properly through the conversion plugin
     * 
     * @access public
     * @param  zipArchive $doc
     */
    public function prepareTemplateConversionPlugin($doc)
    {
        //Extract the required files:
        //document.xml
        //styles.xml    
        $this->_documentXML = $doc->getFromName('word/document.xml');
        $this->_stylesXML = $doc->getFromName('word/styles.xml');

        //Load them in the DOM
        $this->_documentDOM = new \DOMDocument($this->_documentXML);
        $optionEntityLoader = libxml_disable_entity_loader(true);
        $this->_documentDOM->loadXML($this->_documentXML);
        libxml_disable_entity_loader($optionEntityLoader);
        $this->_stylesDOM = new \DOMDocument($this->_stylesXML);
        $optionEntityLoader = libxml_disable_entity_loader(true);
        $this->_stylesDOM->loadXML($this->_stylesXML);
        libxml_disable_entity_loader($optionEntityLoader);
        //Start the preparation process     
        //Paragraphs
        $this->prepareParagraphs(array('document' => $this->_documentDOM), $this->_stylesDOM);
        //Tables
        //Images
    }

    /**
     * Takes care of the paragraph properties:
     * w:spacing
     * 
     * @access public
     * @param  $options
     */
    private function prepareParagraphs($documents, $styles)
    {
        
    }

}
