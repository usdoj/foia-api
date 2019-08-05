<?php

namespace Phpdocx\Utilities;

/**
 * Generate XML parsers of the selected styles in a DOCX
 * 
 * @category   Phpdocx
 * @package    DOCXPath
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @version    2019.01.31
 * @link       https://www.phpdocx.com
 */
class DOCXPathStyles
{        
    /**
     * Creates the required XML parser
     * 
     * @access public
     * @return array
     */
    public function xmlParserStyle($node)
    {
        $parserXML = xml_parser_create();
        xml_parser_set_option($parserXML, XML_OPTION_CASE_FOLDING, 0);
        xml_parse_into_struct($parserXML, $node->ownerDocument->saveXML($node), $values, $indexes);
        xml_parser_free($parserXML);

        return $values;
    }
    
}