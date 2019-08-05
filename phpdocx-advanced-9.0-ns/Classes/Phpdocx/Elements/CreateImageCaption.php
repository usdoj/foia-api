<?php
namespace Phpdocx\Elements;
/**
 * Create image caption using text strings
 *
 * @category   Phpdocx
 * @package    elements
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @version    2017.11.21
 * @link       https://www.phpdocx.com
 */
class CreateImageCaption extends CreateElement
{

    /**
     *
     * @access private
     * @static
     */
    private static $_instance = null;

    /**
     *
     * @access private
     * @var string
     */
    private $_text;

    /**
     *
     * @access private
     * @var bool
     */
    private $_show_label;

    /**
     * Construct
     *
     * @access public
     */
    public function __construct()
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
     * @return CreateCaption
     * @static
     */
    public static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new CreateImageCaption();
        }
        return self::$_instance;
    }
    
    /**
     * Getter. Access to text value var
     *
     * @access public
     * @return string
     */
    public function getText()
    {
        return $this->_text;
    }

    /**
     * Getter. Access to show label var
     *
     * @access public
     * @return bool
     */
    public function getShowLabel()
    {
        return $this->_show_label;
    }

    /**
     * Create Caption
     *
     * @access public
     * @param string $arrArgs[0] Text to add
     */
    public function createCaption()
    {
        $this->_xml = '';
        $args = func_get_args();

        $this->generateP();
        $this->generatePPR();
        $this->generatePSTYLE('Caption');
        $this->generateR();
        
        if ($this->_show_label) {
            $this->generateT('Figure ');
        } else {
            $this->generateT('');
        }

        $this->generateFldSimple();
        if($this->_text != ''){
            $this->generateR();
            $this->generateT($this->_text);
        }
    }

    /**
     * Init a link to assign values to variables
     *
     * @access public
     * @param bool $arrArgs[0]['show_label'] Text to add
     * @param string $arrArgs[0]['text'] URL to add
     */
    public function initCaption()
    {
        $args = func_get_args();

        if (!isset($args[0]['show_label'])) {
            $args[0]['show_label'] = true;
        }
        if (!isset($args[0]['text'])) {
            $args[0]['text'] = '';
        }

        $this->_show_label = $args[0]['show_label'];
        $this->_text = $args[0]['text'];
        
    }

    /**
     * Create fldSimple 
     *
     * @access private
     */
    private function generateFldSimple()
    {
        $beguin = '<'. CreateElement::NAMESPACEWORD .':fldSimple '. CreateElement::NAMESPACEWORD  .':instr=" SEQ Figure \* ARABIC ">
        <'. CreateElement::NAMESPACEWORD .':r><'. CreateElement::NAMESPACEWORD .':rPr><'. CreateElement::NAMESPACEWORD .':noProof/></'. CreateElement::NAMESPACEWORD .':rPr><'. CreateElement::NAMESPACEWORD .':t>';
        $end = '</'. CreateElement::NAMESPACEWORD .':t></'. CreateElement::NAMESPACEWORD .':r></'. CreateElement::NAMESPACEWORD  .':fldSimple>__GENERATESUBR__';

        $simpleField = $beguin.' 1 '.$end;
        $this->_xml = str_replace('__GENERATESUBR__', $simpleField, $this->_xml);
    }
}
