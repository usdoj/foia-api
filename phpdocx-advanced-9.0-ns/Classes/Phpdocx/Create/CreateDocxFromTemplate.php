<?php
namespace Phpdocx\Create;
use Phpdocx\Logger\PhpdocxLogger;
use Phpdocx\Elements\WordFragment;
use Phpdocx\Tracking\Tracking;
/**
 * Use an existing DOCX as the document template
 *
 * @category   Phpdocx
 * @package    create
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @version    2019.01.31
 * @link       https://www.phpdocx.com
 */
class CreateDocxFromTemplate extends CreateDocx
{
    /**
     *
     * @access public
     * @static
     * @var boolean
     */
    public static $_preprocessed = false;

    /**
     *
     * @access public
     * @static
     * @var string
     */
    public static $_templateSymbol = '$';

    /**
     *
     * @access public
     * @static
     * @var string
     */
    public static $_templateBlockSymbol = 'BLOCK_';

    /**
     * Construct
     * @param mixed $docxTemplatePath path to the template we wish to use
     * @param array $options
     * The available keys and values are:
     *  'preprocessed' (boolean) if true the variables will not be 'repaired'. Default value is false
     * @access public
     */
    public function __construct($docxTemplatePath, $options = array())
    {
        if (empty($docxTemplatePath)) {
            PhpdocxLogger::logger('The template path can not be empty', 'fatal');
        }
        parent::__construct($baseTemplatePath = PHPDOCX_BASE_TEMPLATE, $docxTemplatePath);
        if (!empty($options['preprocessed'])) {
            self::$_preprocessed = true;
        }
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
     * Getter. Return template symbol
     *
     * @access public
     * @return string
     */
    public function getTemplateSymbol()
    {
        return self::$_templateSymbol;
    }

    /**
     * Setter. Set template symbol
     *
     * @access public
     * @param string $templateSymbol
     */
    public function setTemplateSymbol($templateSymbol = '$')
    {
        self::$_templateSymbol = $templateSymbol;
    }

    /**
     * Getter. Return template block symbol
     *
     * @access public
     * @return string
     */
    public function getTemplateBlockSymbol()
    {
        return self::$_templateBlockSymbol;
    }

    /**
     * Setter. Set template symbol
     *
     * @access public
     * @param string $templateSymbol
     */
    public function setTemplateBlockSymbol($templateBlockSymbol = 'BLOCK_')
    {
        self::$_templateBlockSymbol = $templateBlockSymbol;
    }

    /**
     * Clear all the placeholders variables which start with the block template symbol
     *
     * @access public
     */
    public function clearBlocks()
    {
        $loadContent = $this->_documentXMLElement . '<w:body>' .
                $this->_wordDocumentC . '</w:body></w:document>';
        // Sometimes Word splits tags so they have to be repared
        // Like this time we do not know the exact variable name we can not use 
        // the repairVariable method directly
        $documentSymbol = explode(self::$_templateSymbol, $loadContent);
        foreach ($documentSymbol as $documentSymbolValue) {
            if (strpos(strip_tags($documentSymbolValue), self::$_templateBlockSymbol) !== false) {
                $loadContent = str_replace($documentSymbolValue, strip_tags($documentSymbolValue), $loadContent);
            }
        }
        $domDocument = new \DomDocument();
        $optionEntityLoader = libxml_disable_entity_loader(true);
        $domDocument->loadXML($loadContent);
        libxml_disable_entity_loader($optionEntityLoader);

        //Use XPath to find all paragraphs that include a BLOCK variable name
        $name = self::$_templateSymbol . self::$_templateBlockSymbol;
        $xpath = new \DOMXPath($domDocument);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $query = '//w:p[w:r/w:t[text()[contains(.,"' . $name . '")]]]';
        $affectedNodes = $xpath->query($query);
        foreach ($affectedNodes as $node) {
            $paragraphContents = $node->ownerDocument->saveXML($node);
            $paragraphText = strip_tags($paragraphContents);
            if (($pos = strpos($paragraphText, $name, 0)) !== false) {
                //If we remove a paragraph inside a table cell we need to take special care
                if ($node->parentNode->nodeName == 'w:tc') {
                    $tcChilds = $node->parentNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'p');
                    if ($tcChilds->length > 1) {
                        $node->parentNode->removeChild($node);
                    } else {
                        $emptyP = $domDocument->createElement("w:p");
                        $node->parentNode->appendChild($emptyP);
                        $node->parentNode->removeChild($node);
                    }
                } else {
                    $node->parentNode->removeChild($node);
                }
            }
        }
        $stringDoc = $domDocument->saveXML();
        $bodyTag = explode('<w:body>', $stringDoc);
        $this->_wordDocumentC = str_replace('</w:body></w:document>', '', $bodyTag[1]);
    }

    /**
     * Clones an existing block
     *
     * @access public
     * @param string $blockName Block name
     * @param int $occurrence Block occurrence
     * @return void
     */
    public function cloneBlock($blockName, $occurrence = 1)
    {
        if (!file_exists(dirname(__FILE__) . '/../Utilities/DOCXPath.php')) {
            PhpdocxLogger::logger('This method is not available for your license.', 'fatal');
        }

        if (!self::$_preprocessed) {
            $variables = $this->getTemplateVariables();
            $this->processTemplate($variables);
        }

        // each block has two placeholders, so get the first position of the occurrence
        $occurrence *= 2;
        $occurrence--;

        $target = 'document';
        list($domDocument, $domXpath) = $this->getWordContentDOM($target);

        $contentNodesReferencedWordContent = $domXpath->query('//w:p[w:r/w:t[text()[contains(.,"'.self::$_templateSymbol.self::$_templateBlockSymbol.$blockName.self::$_templateSymbol.'")]]][' . $occurrence . ']/following-sibling::*');

        $contentNodeReferencedToWordContent = $domXpath->query('//w:p[w:r/w:t[text()[contains(.,"'.self::$_templateSymbol.self::$_templateBlockSymbol.$blockName.self::$_templateSymbol.'")]]][' . ($occurrence + 1) . ']');

        $contentNodeReferencedToWordContentParent = $domXpath->query('//w:p[w:r/w:t[text()[contains(.,"'.self::$_templateSymbol.self::$_templateBlockSymbol.$blockName.self::$_templateSymbol.'")]]][' . ($occurrence + 1) . ']/..');

        if ($contentNodesReferencedWordContent->length > 0 && $contentNodeReferencedToWordContent > 0) {
            $cursor = $domDocument->createElement('cursor', 'WordFragment');
            $contentNodeReferencedToWordContentParent->item(0)->insertBefore($cursor, $contentNodeReferencedToWordContent->item(0)->nextSibling);
            $stringDoc = $domDocument->saveXML();
            $bodyTag = explode('<w:body>', $stringDoc);
            $this->_wordDocumentC = str_replace('</w:body></w:document>', '', $bodyTag[1]);

            $referenceWordContentXML = $domDocument->saveXML($contentNodeReferencedToWordContent->item(0));
            foreach ($contentNodesReferencedWordContent as $contentNodeReferencedWordContent) {
                if ($contentNodeReferencedWordContent->nodeValue == self::$_templateSymbol.self::$_templateBlockSymbol.$blockName.self::$_templateSymbol) {
                    break;
                }
                $referenceWordContentXML .= $domDocument->saveXML($contentNodeReferencedWordContent);
            }
            $referenceWordContentXML .= $domDocument->saveXML($contentNodeReferencedToWordContent->item(0));
            $this->_wordDocumentC = str_replace('<cursor>WordFragment</cursor>', $referenceWordContentXML, $this->_wordDocumentC);
        }
    }

    /**
     * Removes all content between two block variables
     *
     * @access public
     * @param string $blockName Block name
     */
    public function deleteTemplateBlock($blockName)
    {
        $aType = array(self::$_templateBlockSymbol/* , 'TAB_' */); //deletables types
        foreach ($aType as $type) {
            $variableName = $type . $blockName;
            $loadContent = $this->_documentXMLElement . '<w:body>' .
                    $this->_wordDocumentC . '</w:body></w:document>';
            if (!self::$_preprocessed) {
                $loadContent = $this->repairVariables(array($variableName => ''), $loadContent);
            }
            $loadContent = preg_replace('/\\' . self::$_templateSymbol . $type . $blockName . '\\' . self::$_templateSymbol . '[.|\s|\S]*?\\' . self::$_templateSymbol . $type . $blockName . '\\' . self::$_templateSymbol . '/ms', self::$_templateSymbol . $variableName . self::$_templateSymbol, $loadContent);
            //Use XPath to find all paragraphs that include the variable name
            $name = self::$_templateSymbol . $variableName . self::$_templateSymbol;
            $domDocument = new \DOMDocument();
            $optionEntityLoader = libxml_disable_entity_loader(true);
            $domDocument->loadXML($loadContent);
            libxml_disable_entity_loader($optionEntityLoader);
            $xpath = new \DOMXPath($domDocument);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $query = '//w:p[w:r/w:t[text()[contains(.,"' . $variableName . '")]]]';
            $affectedNodes = $xpath->query($query);
            foreach ($affectedNodes as $node) {
                $paragraphContents = $node->ownerDocument->saveXML($node);
                $paragraphText = strip_tags($paragraphContents);
                if (($pos = strpos($paragraphText, $name, 0)) !== false) {
                    //If we remove a paragraph inside a table cell we need to take special care
                    if ($node->parentNode->nodeName == 'w:tc') {
                        $tcChilds = $node->parentNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'p');
                        if ($tcChilds->length > 1) {
                            $node->parentNode->removeChild($node);
                        } else {
                            $emptyP = $domDocument->createElement("w:p");
                            $node->parentNode->appendChild($emptyP);
                            $node->parentNode->removeChild($node);
                        }
                    } else {
                        $node->parentNode->removeChild($node);
                    }
                }
            }
            $bodyTag = explode('<w:body>', $domDocument->saveXML());
            $this->_wordDocumentC = str_replace('</w:body></w:document>', '', $bodyTag[1]);
        }
    }
    
    /**
     * Returns the template variables
     *
     * @access public
     * @param $target may be all (default), document, header, footer, footnotes, endnotes or comments
     * @param array $prefixes (optional) if nonempty it will only return the 
     * variables that start with the given prefixes
     * @return array
     */
    public function getTemplateVariables($target = 'all', $prefixes = array(), $variables = array())
    {
        $targetTypes = array('document', 'header', 'footer', 'footnotes', 'endnotes', 'comments');

        if ($target == 'document') {
            $documentSymbol = explode(self::$_templateSymbol, $this->_wordDocumentC);
            $variables = $this->extractVariables($target, $documentSymbol, $variables);
        } else if ($target == 'header') {
            $xpathHeaders = simplexml_import_dom($this->_contentTypeT);
            $xpathHeaders->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/package/2006/content-types');
            $xpathHeadersResults = $xpathHeaders->xpath('ns:Override[@ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"]');
            foreach ($xpathHeadersResults as $headersResults) {
                $header = substr($headersResults['PartName'], 1);
                $loadContent = $this->getFromZip($header);
                if (empty($loadContent)) {
                    $loadContent = $this->_modifiedHeadersFooters[$header];
                }
                $documentSymbol = explode(self::$_templateSymbol, $loadContent);
                $variables = $this->extractVariables($target, $documentSymbol, $variables);
            }
        } else if ($target == 'footer') {
            $xpathFooters = simplexml_import_dom($this->_contentTypeT);
            $xpathFooters->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/package/2006/content-types');
            $xpathFootersResults = $xpathFooters->xpath('ns:Override[@ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"]');
            foreach ($xpathFootersResults as $footersResults) {
                $footer = substr($footersResults['PartName'], 1);
                $loadContent = $this->getFromZip($footer);
                if (empty($loadContent)) {
                    $loadContent = $this->_modifiedHeadersFooters[$footer];
                }
                $documentSymbol = explode(self::$_templateSymbol, $loadContent);
                $variables = $this->extractVariables($target, $documentSymbol, $variables);
            }
        } else if ($target == 'footnotes') {
            $documentSymbol = explode(self::$_templateSymbol, $this->_wordFootnotesT->saveXML());
            $variables = $this->extractVariables($target, $documentSymbol, $variables);
        } else if ($target == 'endnotes') {
            $documentSymbol = explode(self::$_templateSymbol, $this->_wordEndnotesT->saveXML());
            $variables = $this->extractVariables($target, $documentSymbol, $variables);
        } else if ($target == 'comments') {
            $documentSymbol = explode(self::$_templateSymbol, $this->_wordCommentsT->saveXML());
            $variables = $this->extractVariables($target, $documentSymbol, $variables);
        } else if ($target == 'all') {
            foreach ($targetTypes as $targets) {
                $variables = $this->getTemplateVariables($targets, $prefixes, $variables);
            }
        }

        return $variables;
    }

    /**
     * Modify the value of an input field
     *
     * @access public
     * @param array $data with the key the name of the variable and the value the value of the input text
     *
     */
    public function modifyInputFields($data)
    {
        $loadContent = $this->_documentXMLElement . '<w:body>' .
                $this->_wordDocumentC . '</w:body></w:document>';
        $domDocument = new \DOMDocument();
        $optionEntityLoader = libxml_disable_entity_loader(true);
        $domDocument->loadXML($loadContent);
        libxml_disable_entity_loader($optionEntityLoader);
        $docXPath = new \DOMXPath($domDocument);
        $docXPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $docXPath->registerNamespace('w14', 'http://schemas.microsoft.com/office/word/2010/wordml');
        foreach ($data as $var => $value) {
            //check for legacy checkboxes
            $queryDoc = '//w:ffData[w:name[@w:val="' . $var . '"]]';
            $affectedNodes = $docXPath->query($queryDoc);
            foreach ($affectedNodes as $node) {
                //get the parent p Node
                $pNode = $node->parentNode->parentNode->parentNode;
                //we should take into account that there could be more than one field per paragraph
                $preliminaryQuery = './/w:r[descendant::w:ffData and count(preceding-sibling::w:r[descendant::w:name[@w:val = "' . $var . '"]]) < 1]';
                $previousInputs = $docXPath->query($preliminaryQuery, $pNode)->length;
                $position = $previousInputs - 1;
                $query = './/w:r[count(preceding-sibling::w:r[descendant::w:fldChar[@w:fldCharType = "separate"]]) >= ' . ($position + 1);
                $query .= ' and count(preceding-sibling::w:r[descendant::w:fldChar[@w:fldCharType = "end"]]) < ' . ($position + 1);
                $query .= ' and not(descendant::w:fldChar)]';
                $rNodes = $docXPath->query($query, $pNode);
                $rCount = 0;
                foreach ($rNodes as $rNode) {
                    if ($rCount == 0) {
                        $rNode->getElementsByTagName('t')->item(0)->nodeValue = $value;
                    } else {
                        $rNode->setAttribute('w:remove', 1);
                    }
                    $rCount++;
                }
                //remove the unwanted rNodes
                $query = './/w:r[@w:remove="1"]';
                $removeNodes = $docXPath->query($query, $pNode);
                $length = $removeNodes->length;
                for ($j = $length - 1; $j > -1; $j--) {
                    $removeNodes->item($j)->parentNode->removeChild($removeNodes->item($j));
                }
            }
            //Now we look for Word 2010 sdt checkboxes
            $queryDoc = '//w:sdtPr[w:tag[@w:val="' . $var . '"]]';
            $affectedNodes = $docXPath->query($queryDoc);
            foreach ($affectedNodes as $node) {
                $sdtNode = $node->parentNode;
                $query = './/w:t[1]';
                $tNode = $docXPath->query($query, $sdtNode)->item(0)->nodeValue = $value;
            }
        }

        $stringDoc = $domDocument->saveXML();
        $bodyTag = explode('<w:body>', $stringDoc);
        $this->_wordDocumentC = str_replace('</w:body></w:document>', '', $bodyTag[1]);
    }

    /**
     * Processes the template to repair all listed variables
     *
     * @access public
     * @param array $variables an array of arrays of variables that should be repaired. 
     * Posible keys and values are:
     *  'document' array of variables within the main document
     *  'headers' array of variables within the headers
     *  'footers' array of variables within the footers
     *  'footnotes' array of variables within the footnotes
     *  'endnotes' array of variables within the endnotes
     *  'comments' array of variables within the comments
     * If the array is empty the variables will be tried to be extracted automatically.
     * @return array
     */
    public function processTemplate($variables = array())
    {
        self::$_preprocessed = true;
        if (is_array($variables) && count($variables) == 0) {
            $variables = $this->getTemplateVariables();
        }
        foreach ($variables as $target => $varList) {
            $variableList = array_flip($varList);
            if ($target == 'document') {
                $loadContent = $this->_documentXMLElement . '<w:body>' .
                        $this->_wordDocumentC . '</w:body></w:document>';
                $stringDoc = $this->repairVariables($variableList, $loadContent);
                $bodyTag = explode('<w:body>', $stringDoc);
                $this->_wordDocumentC = str_replace('</w:body></w:document>', '', $bodyTag[1]);
            } else if ($target == 'footnotes') {
                $content = $this->_wordFootnotesT->saveXML();
                $XML = $this->repairVariables($variableList, $content);
                $this->_wordFootnotesT = new \DOMDocument();
                $optionEntityLoader = libxml_disable_entity_loader(true);
                $this->_wordFootnotesT->loadXML($XML);
                libxml_disable_entity_loader($optionEntityLoader);
            } else if ($target == 'endnotes') {
                $content = $this->_wordEndnotesT->saveXML();
                $XML = $this->repairVariables($variableList, $content);
                $this->_wordEndnotesT = new \DOMDocument();
                $optionEntityLoader = libxml_disable_entity_loader(true);
                $this->_wordEndnotesT->loadXML($XML);
                libxml_disable_entity_loader($optionEntityLoader);
            } else if ($target == 'comments') {
                $content = $this->_wordCommentsT->saveXML();
                $XML = $this->repairVariables($variableList, $content);
                $this->_wordCommentsT = new \DOMDocument();
                $optionEntityLoader = libxml_disable_entity_loader(true);
                $this->_wordCommentsT->loadXML($XML);
                libxml_disable_entity_loader($optionEntityLoader);
            } else if ($target == 'headers') {
                $xpathHeaders = simplexml_import_dom($this->_contentTypeT);
                $xpathHeaders->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/package/2006/content-types');
                $xpathHeadersResults = $xpathHeaders->xpath('ns:Override[@ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"]');
                foreach ($xpathHeadersResults as $headersResults) {
                    $header = substr($headersResults['PartName'], 1);
                    $loadContent = $this->getFromZip($header);
                    if (empty($loadContent)) {
                        $loadContent = $this->_modifiedHeadersFooters[$header];
                    }
                    $dom = $this->repairVariables($variableList, $loadContent);
                    $this->_modifiedHeadersFooters[$header] = $dom->saveXML();
                    $this->saveToZip($dom, $header);
                }
            } else if ($target == 'footers') {
                $xpathFooters = simplexml_import_dom($this->_contentTypeT);
                $xpathFooters->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/package/2006/content-types');
                $xpathFootersResults = $xpathFooters->xpath('ns:Override[@ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"]');
                foreach ($xpathFootersResults as $footersResults) {
                    $footer = substr($footersResults['PartName'], 1);
                    $loadContent = $this->getFromZip($footer);
                    if (empty($loadContent)) {
                        $loadContent = $this->_modifiedHeadersFooters[$footer];
                    }
                    $dom = $this->repairVariables($variableList, $loadContent);
                    $this->_modifiedHeadersFooters[$footer] = $dom->saveXML();
                    $this->saveToZip($dom, $footer);
                }
            }
        }
    }

    /**
     * Removes a template variable with its container paragraph
     *
     * @access public
     * @param string $variableName
     * @param string $type can be block or inline
     * @param string $target it can be document (default value), header, footer, footnote, endnote, comment
     */
    public function removeTemplateVariable($variableName, $type = 'block', $target = 'document')
    {

        if ($type == 'inline') {
            $this->replaceVariableByText(array($variableName => ''), array('target' => $target));
        } else {
            if ($target == 'document') {
                $loadContent = $this->_documentXMLElement . '<w:body>' .
                        $this->_wordDocumentC . '</w:body></w:document>';
                $stringDoc = $this->removeVariableBlock($variableName, $loadContent);
                $bodyTag = explode('<w:body>', $stringDoc);
                $this->_wordDocumentC = str_replace('</w:body></w:document>', '', $bodyTag[1]);
            } else if ($target == 'footnote') {
                $dom = $this->removeVariableBlock($variableName, $this->_wordFootnotesT->saveXML());
                $optionEntityLoader = libxml_disable_entity_loader(true);
                $this->_wordFootnotesT->loadXML($dom);
                libxml_disable_entity_loader($optionEntityLoader);
            } else if ($target == 'endnote') {
                $dom = $this->removeVariableBlock($variableName, $this->_wordEndnotesT->saveXML());
                $optionEntityLoader = libxml_disable_entity_loader(true);
                $this->_wordEndnotesT->loadXML($dom);
                libxml_disable_entity_loader($optionEntityLoader);
            } else if ($target == 'comment') {
                $dom = $this->removeVariableBlock($variableName, $this->_wordCommentsT->saveXML());
                $optionEntityLoader = libxml_disable_entity_loader(true);
                $this->_wordCommentsT->loadXML($dom);
                libxml_disable_entity_loader($optionEntityLoader);
            } else if ($target == 'header') {
                $xpathHeaders = simplexml_import_dom($this->_contentTypeT);
                $xpathHeaders->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/package/2006/content-types');
                $xpathHeadersResults = $xpathHeaders->xpath('ns:Override[@ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"]');
                foreach ($xpathHeadersResults as $headersResults) {
                    $header = substr($headersResults['PartName'], 1);
                    $loadContent = $this->getFromZip($header);
                    if (empty($loadContent)) {
                        $loadContent = $this->_modifiedHeadersFooters[$header];
                    }
                    $dom = $this->removeVariableBlock($variableName, $loadContent);
                    if (is_string($dom)) {
                        $this->_modifiedHeadersFooters[$header] = $dom;
                    } else {
                        $this->_modifiedHeadersFooters[$header] = $dom->saveXML();
                    }
                    $this->saveToZip($dom, $header);
                }
            } else if ($target == 'footer') {
                $xpathFooters = simplexml_import_dom($this->_contentTypeT);
                $xpathFooters->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/package/2006/content-types');
                $xpathFootersResults = $xpathFooters->xpath('ns:Override[@ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"]');
                foreach ($xpathFootersResults as $footersResults) {
                    $footer = substr($footersResults['PartName'], 1);
                    $loadContent = $this->getFromZip($footer);
                    if (empty($loadContent)) {
                        $loadContent = $this->_modifiedHeadersFooters[$footer];
                    }
                    $dom = $this->removeVariableBlock($variableName, $loadContent);
                    if (is_string($dom)) {
                        $this->_modifiedHeadersFooters[$footer] = $dom;
                    } else {
                        $this->_modifiedHeadersFooters[$footer] = $dom->saveXML();
                    }
                    $this->saveToZip($dom, $footer);
                }
            }
        }
    }
    
    /**
     * Replaces a single variable within a list by a list of items
     *
     * @access public
     * @param string $variable
     * @param array $listValues
     * @param string $options
     * 'target': document (default), header, footer
     * 'firstMatch' (boolean) if true it only replaces the first variable match. Default is set to false.
     * 'parseLineBreaks' (boolean) if true (default is false) parses the line breaks to include them in the Word document
     * 'type' (string) inline (default) or block; used by WordFragment values
     * @return void
     */
    public function replaceListVariable($variable, $listValues, $options = array())
    {
        if (isset($options['firstMatch'])) {
            $firstMatch = $options['firstMatch'];
        } else {
            $firstMatch = false;
        }

        $type = 'inline';
        if (isset($options['type'])) {
            $type = $options['type'];
        }

        if (isset($options['target'])) {
            $target = $options['target'];
        } else {
            $target = 'document';
        }

        if ($target == 'document') {
            $loadContent = $this->_documentXMLElement . '<w:body>' . $this->_wordDocumentC . '</w:body></w:document>';
            if (!self::$_preprocessed) {
                $loadContent = $this->repairVariables(array($variable => ''), $loadContent);
            }
            $optionEntityLoader = libxml_disable_entity_loader(true);
            $dom = simplexml_load_string($loadContent);
            libxml_disable_entity_loader($optionEntityLoader);
            $dom->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $search = self::$_templateSymbol . $variable . self::$_templateSymbol;
            $query = '//w:p[w:r/w:t[text()[contains(., "' . $search . '")]]]';
            if ($firstMatch) {
                $query = '(' . $query . ')[1]';
            }

            // if the content has WordFragments, replace each WordFragment by a plain placeholder. This holder
            // is replaced by the WordFragment value using the replaceVariableByWordFragment method
            $wordFragmentsValues = array();
            foreach ($listValues as $listKey => $listValue) {
                if ($listValue instanceof WordFragment) {
                    $uniqueId = uniqid(mt_rand(999, 9999));
                    $uniqueKey = $this->getTemplateSymbol() . $uniqueId . $this->getTemplateSymbol();
                    $wordFragmentsValues[$uniqueId] = $listValues[$listKey];
                    $listValues[$listKey] = $uniqueKey;
                }
            }

            $foundNodes = $dom->xpath($query);
            foreach ($foundNodes as $node) {
                $domNode = dom_import_simplexml($node);
                foreach ($listValues as $key => $value) {
                    $newNode = $domNode->cloneNode(true);
                    $textNodes = $newNode->getElementsBytagName('t');
                    foreach ($textNodes as $text) {
                        $sxText = simplexml_import_dom($text);
                        $strNode = (string) $sxText;
                        if (isset($options['parseLineBreaks']) && $options['parseLineBreaks']) {
                            //parse $val for \n\r, \r\n, \n or \r and carriage returns
                            $value = str_replace(array('\n\r', '\r\n', '\n', '\r', "\n\r", "\r\n", "\n", "\r"), '__LINEBREAK__', $value);
                        }
                        $strNodeReplaced = str_replace($search, $value, $strNode);
                        $sxText[0] = $strNodeReplaced;
                    }
                    $domNode->parentNode->insertBefore($newNode, $domNode);
                }
                $domNode->parentNode->removeChild($domNode);
            }
            $stringDoc = $dom->asXML();
            $bodyTag = explode('<w:body>', $stringDoc);
            $this->_wordDocumentC = str_replace('</w:body></w:document>', '', $bodyTag[1]);
            $this->_wordDocumentC = str_replace('__LINEBREAK__', '</w:t><w:br/><w:t>', $this->_wordDocumentC);

            // replace existing WordFragment placeholders
            if (count($wordFragmentsValues) > 0) {
                $this->replaceVariableByWordFragment($wordFragmentsValues, array('type' => $type));
            }
        } elseif ($target == 'header') {
            // if the content has WordFragments, replace each WordFragment by a plain placeholder. This holder
            // is replaced by the WordFragment value using the replaceVariableByWordFragment method
            $wordFragmentsValues = array();
            foreach ($listValues as $listKey => $listValue) {
                if ($listValue instanceof WordFragment) {
                    $uniqueId = uniqid(mt_rand(999, 9999));
                    $uniqueKey = $this->getTemplateSymbol() . $uniqueId . $this->getTemplateSymbol();
                    $wordFragmentsValues[$uniqueId] = $listValues[$listKey];
                    $listValues[$listKey] = $uniqueKey;
                }
            }

            $xpathHeaders = simplexml_import_dom($this->_contentTypeT);
            $xpathHeaders->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/package/2006/content-types');
            $xpathHeadersResults = $xpathHeaders->xpath('ns:Override[@ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"]');
            foreach ($xpathHeadersResults as $headersResults) {
                $header = substr($headersResults['PartName'], 1);
                $loadContent = $this->getFromZip($header);
                if (empty($loadContent)) {
                    $loadContent = $this->_modifiedHeadersFooters[$header];
                }
                if (!empty($loadContent)) {
                    if (!self::$_preprocessed) {
                        $loadContent = $this->repairVariables(array($variable => ''), $loadContent);
                    }
                    $optionEntityLoader = libxml_disable_entity_loader(true);
                    $dom = simplexml_load_string($loadContent);
                    libxml_disable_entity_loader($optionEntityLoader);
                    $dom->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                    $search = self::$_templateSymbol . $variable . self::$_templateSymbol;
                    $query = '//w:p[w:r/w:t[text()[contains(., "' . $search . '")]]]';
                    if ($firstMatch) {
                        $query = '(' . $query . ')[1]';
                    }

                    $foundNodes = $dom->xpath($query);
                    foreach ($foundNodes as $node) {
                        $domNode = dom_import_simplexml($node);
                        foreach ($listValues as $key => $value) {
                            $newNode = $domNode->cloneNode(true);
                            $textNodes = $newNode->getElementsBytagName('t');
                            foreach ($textNodes as $text) {
                                $sxText = simplexml_import_dom($text);
                                $strNode = (string) $sxText;
                                if (isset($options['parseLineBreaks']) && $options['parseLineBreaks']) {
                                    //parse $val for \n\r, \r\n, \n or \r and carriage returns
                                    $value = str_replace(array('\n\r', '\r\n', '\n', '\r', "\n\r", "\r\n", "\n", "\r"), '__LINEBREAK__', $value);
                                }
                                $strNodeReplaced = str_replace($search, $value, $strNode);
                                $sxText[0] = $strNodeReplaced;
                            }
                            $domNode->parentNode->insertBefore($newNode, $domNode);
                        }
                        $domNode->parentNode->removeChild($domNode);
                    }
                    $stringDoc = $dom->asXML();

                    $this->_modifiedHeadersFooters[$header] = $stringDoc;
                    $this->saveToZip($dom, $header);
                }
            }

            // replace existing WordFragment placeholders
            if (count($wordFragmentsValues) > 0) {
                $this->replaceVariableByWordFragment($wordFragmentsValues, array('type' => $type, 'target' => 'header'));
            }
        } elseif ($target == 'footer') {
            // if the content has WordFragments, replace each WordFragment by a plain placeholder. This holder
            // is replaced by the WordFragment value using the replaceVariableByWordFragment method
            $wordFragmentsValues = array();
            foreach ($listValues as $listKey => $listValue) {
                if ($listValue instanceof WordFragment) {
                    $uniqueId = uniqid(mt_rand(999, 9999));
                    $uniqueKey = $this->getTemplateSymbol() . $uniqueId . $this->getTemplateSymbol();
                    $wordFragmentsValues[$uniqueId] = $listValues[$listKey];
                    $listValues[$listKey] = $uniqueKey;
                }
            }

            $xpathFooters = simplexml_import_dom($this->_contentTypeT);
            $xpathFooters->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/package/2006/content-types');
            $xpathFootersResults = $xpathFooters->xpath('ns:Override[@ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"]');
            foreach ($xpathFootersResults as $footersResults) {
                $footer = substr($footersResults['PartName'], 1);
                $loadContent = $this->getFromZip($footer);
                if (empty($loadContent)) {
                    $loadContent = $this->_modifiedHeadersFooters[$footer];
                }
                if (!empty($loadContent)) {
                    if (!self::$_preprocessed) {
                        $loadContent = $this->repairVariables(array($variable => ''), $loadContent);
                    }
                    $optionEntityLoader = libxml_disable_entity_loader(true);
                    $dom = simplexml_load_string($loadContent);
                    libxml_disable_entity_loader($optionEntityLoader);
                    $dom->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                    $search = self::$_templateSymbol . $variable . self::$_templateSymbol;
                    $query = '//w:p[w:r/w:t[text()[contains(., "' . $search . '")]]]';
                    if ($firstMatch) {
                        $query = '(' . $query . ')[1]';
                    }

                    $foundNodes = $dom->xpath($query);
                    foreach ($foundNodes as $node) {
                        $domNode = dom_import_simplexml($node);
                        foreach ($listValues as $key => $value) {
                            $newNode = $domNode->cloneNode(true);
                            $textNodes = $newNode->getElementsBytagName('t');
                            foreach ($textNodes as $text) {
                                $sxText = simplexml_import_dom($text);
                                $strNode = (string) $sxText;
                                if (isset($options['parseLineBreaks']) && $options['parseLineBreaks']) {
                                    //parse $val for \n\r, \r\n, \n or \r and carriage returns
                                    $value = str_replace(array('\n\r', '\r\n', '\n', '\r', "\n\r", "\r\n", "\n", "\r"), '__LINEBREAK__', $value);
                                }
                                $strNodeReplaced = str_replace($search, $value, $strNode);
                                $sxText[0] = $strNodeReplaced;
                            }
                            $domNode->parentNode->insertBefore($newNode, $domNode);
                        }
                        $domNode->parentNode->removeChild($domNode);
                    }
                    $stringDoc = $dom->asXML();

                    $this->_modifiedHeadersFooters[$footer] = $stringDoc;
                    $this->saveToZip($dom, $footer);
                }
            }

            // replace existing WordFragment placeholders
            if (count($wordFragmentsValues) > 0) {
                $this->replaceVariableByWordFragment($wordFragmentsValues, array('type' => $type, 'target' => 'footer'));
            }
        }
        
    }
    
    /**
     * Replaces a placeholder image by an external image
     * 
     * @access public
     * @param string $variable this variable uniquely identifies the image we want to replace
     * @param string $src path to the substitution image
     * @param string $options
     * 'firstMatch' (boolean) if true it only replaces the first variable match. Default is set to false.
     * 'target': document, header, footer, footnote, endnote, comment
     * 'width' (mixed): the value in cm (float) or 'auto' (use image size), 0 to not change the previous size
     * 'height' (mixed): the value in cm (float) or 'auto' (use image size), 0 to not change the previous size
     * 'dpi' (int): dots per inch. This parameter is only taken into account if width or height are set to auto.
     * 'mime' (string) forces a mime (image/jpg, image/jpeg, image/png, image/gif)
     * 'streamMode' (bool) if true, uses src path as stream. PHP 5.4 or greater needed to autodetect the mime type; otherwise set it using mime option. Default is false
     * If any of these formatting parameters is not set, the width and/or height of the placeholder image will be preserved
     * @return void
     */
    public function replacePlaceholderImage($variable, $src, $options = array())
    {
        if (isset($options['target'])) {
            $target = $options['target'];
        } else {
            $target = 'document';
        }

        if ($target == 'document') {
            $loadContent = $this->_documentXMLElement . '<w:body>' .
                    $this->_wordDocumentC . '</w:body></w:document>';
            $stringDoc = $this->Image4Image($variable, $src, $options, $loadContent)->saveXML();
            $bodyTag = explode('<w:body>', $stringDoc);
            $this->_wordDocumentC = str_replace('</w:body></w:document>', '', $bodyTag[1]);
        } else if ($target == 'footnote') {
            $dom = $this->Image4Image($variable, $src, $options, $this->_wordFootnotesT->saveXML(), $target)->saveXML();
            $optionEntityLoader = libxml_disable_entity_loader(true);
            $this->_wordFootnotesT->loadXML($dom);
            libxml_disable_entity_loader($optionEntityLoader);
        } else if ($target == 'endnote') {
            $dom = $this->Image4Image($variable, $src, $options, $this->_wordEndnotesT->saveXML(), $target)->saveXML();
            $optionEntityLoader = libxml_disable_entity_loader(true);
            $this->_wordEndnotesT->loadXML($dom);
            libxml_disable_entity_loader($optionEntityLoader);
        } else if ($target == 'comment') {
            $dom = $this->Image4Image($variable, $src, $options, $this->_wordCommentsT->saveXML(), $target)->saveXML();
            $optionEntityLoader = libxml_disable_entity_loader(true);
            $this->_wordCommentsT->loadXML($dom);
            libxml_disable_entity_loader($optionEntityLoader);
        } else if ($target == 'header') {
            $xpathHeaders = simplexml_import_dom($this->_contentTypeT);
            $xpathHeaders->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/package/2006/content-types');
            $xpathHeadersResults = $xpathHeaders->xpath('ns:Override[@ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"]');
            foreach ($xpathHeadersResults as $headersResults) {
                $header = substr($headersResults['PartName'], 1);
                $rels = substr($header, 5);
                $rels = substr($rels, 0, -4);
                $loadContent = $this->getFromZip($header);
                if (empty($loadContent)) {
                    $loadContent = $this->_modifiedHeadersFooters[$header];
                }
                $dom = $this->Image4Image($variable, $src, $options, $loadContent, $rels);
                $this->_modifiedHeadersFooters[$header] = $dom->saveXML();
                $this->saveToZip($dom, $header);
            }
        } else if ($target == 'footer') {
            $xpathFooters = simplexml_import_dom($this->_contentTypeT);
            $xpathFooters->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/package/2006/content-types');
            $xpathFootersResults = $xpathFooters->xpath('ns:Override[@ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"]');
            foreach ($xpathFootersResults as $footersResults) {
                $footer = substr($footersResults['PartName'], 1);
                $rels = substr($footer, 5);
                $rels = substr($rels, 0, -4);
                $loadContent = $this->getFromZip($footer);
                if (empty($loadContent)) {
                    $loadContent = $this->_modifiedHeadersFooters[$footer];
                }
                $dom = $this->Image4Image($variable, $src, $options, $loadContent, $rels);
                $this->_modifiedHeadersFooters[$footer] = $dom->saveXML();
                $this->saveToZip($dom, $footer);
            }
        }
    }
    
    /**
     * Do the actual substitution of the variables in a 'table set of rows'
     *
     * @access public 
     * @param array $vars
     * @param string $options
     * 'target': document (default), header, footer
     * 'firstMatch' (boolean) if true it only replaces the first variable match. Default is set to false.
     * 'parseLineBreaks' (boolean) if true (default is false) parses the line breaks to include them in the Word document
     * 'type' (string) inline or block (default); used by WordFragment values
     * @return void
     */
    public function replaceTableVariable($vars, $options = array())
    {
        if (isset($options['firstMatch'])) {
            $firstMatch = $options['firstMatch'];
        } else {
            $firstMatch = false;
        }

        $type = 'block';
        if (isset($options['type'])) {
            $type = $options['type'];
        }

        if (isset($options['target'])) {
            $target = $options['target'];
        } else {
            $target = 'document';
        }

        if ($target == 'document') {
            $varKeys = array_keys($vars[0]);
            //We build an array to clean the table variables
            $toRepair = array();
            foreach ($varKeys as $key => $value) {
                $toRepair[$value] = '';
            }
            $loadContent = $this->_documentXMLElement . '<w:body>' .
                    $this->_wordDocumentC . '</w:body></w:document>';
            if (!self::$_preprocessed) {
                $loadContent = $this->repairVariables($toRepair, $loadContent);
            }
            $optionEntityLoader = libxml_disable_entity_loader(true);
            $dom = simplexml_load_string($loadContent);
            libxml_disable_entity_loader($optionEntityLoader);
            $dom->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $search = array();
            for ($j = 0; $j < count($varKeys); $j++) {
                $search[$j] = self::$_templateSymbol . $varKeys[$j] . self::$_templateSymbol;
            }
            $queryArray = array();
            for ($j = 0; $j < count($search); $j++) {
                $queryArray[$j] = '//w:tr[w:tc/w:p/w:r/w:t[text()[contains(., "' . $search[$j] . '")]]]';
            }
            $query = join(' | ', $queryArray);
            $foundNodes = $dom->xpath($query);
            $tableCounter = 0;
            $referenceNode = '';
            $parentNode = '';

            // if the content has WordFragments, replace each WordFragment by a plain placeholder. This holder
            // is replaced by the WordFragment value using the replaceVariableByWordFragment method
            $wordFragmentsValues = array();
            foreach ($vars as &$varsRow) {
                foreach ($varsRow as $varKeyRow => $varValueRow) {
                    if ($varValueRow instanceof WordFragment) {
                        $uniqueId = uniqid(mt_rand(999, 9999));
                        $uniqueKey = $this->getTemplateSymbol() . $uniqueId . $this->getTemplateSymbol();
                        $wordFragmentsValues[$uniqueId] = $varsRow[$varKeyRow];
                        $varsRow[$varKeyRow] = $uniqueKey;
                    }
                }
            }

            foreach ($vars as $key => $rowValue) {
                $tableCounter = 0;
                foreach ($foundNodes as $node) {
                    $domNode = dom_import_simplexml($node);
                    if (!is_object($referenceNode) || !$domNode->parentNode->isSameNode($parentNode)) {
                        $referenceNode = $domNode;
                        $parentNode = $domNode->parentNode;
                        $tableCounter++;
                    }
                    if (!$firstMatch || ($firstMatch && $tableCounter < 2)) {
                        $newNode = $domNode->cloneNode(true);
                        $textNodes = $newNode->getElementsBytagName('t');
                        foreach ($textNodes as $text) {
                            for ($k = 0; $k < count($search); $k++) {
                                $sxText = simplexml_import_dom($text);
                                $strNode = (string) $sxText;
                                if (!empty($rowValue[$varKeys[$k]]) ||
                                        $rowValue[$varKeys[$k]] === 0 ||
                                        $rowValue[$varKeys[$k]] === "0") {
                                    if (isset($options['parseLineBreaks']) && $options['parseLineBreaks']) {
                                        //parse $val for \n\r, \r\n, \n or \r and carriage returns
                                        $rowValue[$varKeys[$k]] = str_replace(array('\n\r', '\r\n', '\n', '\r', "\n\r", "\r\n", "\n", "\r"), '__LINEBREAK__', $rowValue[$varKeys[$k]]);
                                    }
                                    $strNode = str_replace($search[$k], $rowValue[$varKeys[$k]], $strNode);
                                } else {
                                    $strNode = str_replace($search[$k], '', $strNode);
                                }
                                $sxText[0] = $strNode;
                            }
                        }
                        $parentNode->insertBefore($newNode, $referenceNode);
                    }
                }
            }
            //Remove the original nodes
            $tableCounter2 = 0;
            foreach ($foundNodes as $node) {
                $domNode = dom_import_simplexml($node);
                if ($firstMatch && !$domNode->parentNode->isSameNode($parentNode)) {
                    $parentNode = $domNode->parentNode;
                    $tableCounter2++;
                }
                if ($tableCounter2 < 2) {
                    $domNode->parentNode->removeChild($domNode);
                }
            }

            $stringDoc = $dom->asXML();
            $bodyTag = explode('<w:body>', $stringDoc);
            $this->_wordDocumentC = str_replace('</w:body></w:document>', '', $bodyTag[1]);
            $this->_wordDocumentC = str_replace('__LINEBREAK__', '</w:t><w:br/><w:t>', $this->_wordDocumentC);

            // replace existing WordFragment placeholders
            if (count($wordFragmentsValues) > 0) {
                $this->replaceVariableByWordFragment($wordFragmentsValues, array('type' => $type));
            }
        } elseif ($target == 'header') {
            $xpathHeaders = simplexml_import_dom($this->_contentTypeT);
            $xpathHeaders->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/package/2006/content-types');
            $xpathHeadersResults = $xpathHeaders->xpath('ns:Override[@ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"]');
            foreach ($xpathHeadersResults as $headersResults) {
                $header = substr($headersResults['PartName'], 1);
                $loadContent = $this->getFromZip($header);
                if (empty($loadContent)) {
                    $loadContent = $this->_modifiedHeadersFooters[$header];
                }
                if (!empty($loadContent)) {
                    $varKeys = array_keys($vars[0]);
                    //We build an array to clean the table variables
                    $toRepair = array();
                    foreach ($varKeys as $key => $value) {
                        $toRepair[$value] = '';
                    }

                    if (!self::$_preprocessed) {
                        $loadContent = $this->repairVariables($toRepair, $loadContent);
                    }
                    $optionEntityLoader = libxml_disable_entity_loader(true);
                    $dom = simplexml_load_string($loadContent);
                    libxml_disable_entity_loader($optionEntityLoader);
                    $dom->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                    $search = array();
                    for ($j = 0; $j < count($varKeys); $j++) {
                        $search[$j] = self::$_templateSymbol . $varKeys[$j] . self::$_templateSymbol;
                    }
                    $queryArray = array();
                    for ($j = 0; $j < count($search); $j++) {
                        $queryArray[$j] = '//w:tr[w:tc/w:p/w:r/w:t[text()[contains(., "' . $search[$j] . '")]]]';
                    }
                    $query = join(' | ', $queryArray);
                    $foundNodes = $dom->xpath($query);
                    $tableCounter = 0;
                    $referenceNode = '';
                    $parentNode = '';

                    // if the content has WordFragments, replace each WordFragmen by a plain placeholder. This holder
                    // is replaced by the WordFragment value using the replaceVariableByWordFragment method
                    $wordFragmentsValues = array();
                    foreach ($vars as &$varsRow) {
                        foreach ($varsRow as $varKeyRow => $varValueRow) {
                            if ($varValueRow instanceof WordFragment) {
                                $uniqueId = uniqid(mt_rand(999, 9999));
                                $uniqueKey = $this->getTemplateSymbol() . $uniqueId . $this->getTemplateSymbol();
                                $wordFragmentsValues[$uniqueId] = $varsRow[$varKeyRow];
                                $varsRow[$varKeyRow] = $uniqueKey;
                            }
                        }
                    }

                    foreach ($vars as $key => $rowValue) {
                        foreach ($foundNodes as $node) {
                            $domNode = dom_import_simplexml($node);
                            if (!is_object($referenceNode) || !$domNode->parentNode->isSameNode($parentNode)) {
                                $referenceNode = $domNode;
                                $parentNode = $domNode->parentNode;
                                $tableCounter++;
                            }
                            if (!$firstMatch || ($firstMatch && $tableCounter < 2)) {
                                $newNode = $domNode->cloneNode(true);
                                $textNodes = $newNode->getElementsBytagName('t');
                                foreach ($textNodes as $text) {
                                    for ($k = 0; $k < count($search); $k++) {
                                        $sxText = simplexml_import_dom($text);
                                        $strNode = (string) $sxText;
                                        if (!empty($rowValue[$varKeys[$k]]) ||
                                                $rowValue[$varKeys[$k]] === 0 ||
                                                $rowValue[$varKeys[$k]] === "0") {
                                            if (isset($options['parseLineBreaks']) && $options['parseLineBreaks']) {
                                                //parse $val for \n\r, \r\n, \n or \r and carriage returns
                                                $rowValue[$varKeys[$k]] = str_replace(array('\n\r', '\r\n', '\n', '\r', "\n\r", "\r\n", "\n", "\r"), '__LINEBREAK__', $rowValue[$varKeys[$k]]);
                                            }
                                            $strNode = str_replace($search[$k], $rowValue[$varKeys[$k]], $strNode);
                                        } else {
                                            $strNode = str_replace($search[$k], '', $strNode);
                                        }
                                        $sxText[0] = $strNode;
                                    }
                                }
                                $parentNode->insertBefore($newNode, $referenceNode);
                            }
                        }
                    }
                    //Remove the original nodes
                    $tableCounter2 = 0;
                    foreach ($foundNodes as $node) {
                        $domNode = dom_import_simplexml($node);
                        if ($firstMatch && !$domNode->parentNode->isSameNode($parentNode)) {
                            $parentNode = $domNode->parentNode;
                            $tableCounter2++;
                        }
                        if ($tableCounter2 < 2) {
                            $domNode->parentNode->removeChild($domNode);
                        }
                    }

                    $stringDoc = $dom->asXML();

                    $this->_modifiedHeadersFooters[$header] = $stringDoc;
                    $this->saveToZip($dom, $header);

                    // replace existing WordFragment placeholders
                    if (count($wordFragmentsValues) > 0) {
                        $this->replaceVariableByWordFragment($wordFragmentsValues, array('type' => $type, 'target' => 'header'));
                    }
                }
            }
        } elseif ($target == 'footer') {
            $varKeys = array_keys($vars[0]);
            //We build an array to clean the table variables
            $toRepair = array();
            foreach ($varKeys as $key => $value) {
                $toRepair[$value] = '';
            }

            $xpathFooters = simplexml_import_dom($this->_contentTypeT);
            $xpathFooters->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/package/2006/content-types');
            $xpathFootersResults = $xpathFooters->xpath('ns:Override[@ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"]');
            foreach ($xpathFootersResults as $footersResults) {
                $footer = substr($footersResults['PartName'], 1);
                $loadContent = $this->getFromZip($footer);
                if (empty($loadContent)) {
                    $loadContent = $this->_modifiedHeadersFooters[$footer];
                }
                if (!empty($loadContent)) {
                    $varKeys = array_keys($vars[0]);
                    //We build an array to clean the table variables
                    $toRepair = array();
                    foreach ($varKeys as $key => $value) {
                        $toRepair[$value] = '';
                    }

                    if (!self::$_preprocessed) {
                        $loadContent = $this->repairVariables($toRepair, $loadContent);
                    }
                    $optionEntityLoader = libxml_disable_entity_loader(true);
                    $dom = simplexml_load_string($loadContent);
                    libxml_disable_entity_loader($optionEntityLoader);
                    $dom->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                    $search = array();
                    for ($j = 0; $j < count($varKeys); $j++) {
                        $search[$j] = self::$_templateSymbol . $varKeys[$j] . self::$_templateSymbol;
                    }
                    $queryArray = array();
                    for ($j = 0; $j < count($search); $j++) {
                        $queryArray[$j] = '//w:tr[w:tc/w:p/w:r/w:t[text()[contains(., "' . $search[$j] . '")]]]';
                    }
                    $query = join(' | ', $queryArray);
                    $foundNodes = $dom->xpath($query);
                    $tableCounter = 0;
                    $referenceNode = '';
                    $parentNode = '';

                    // if the content has WordFragments, replace each WordFragmen by a plain placeholder. This holder
                    // is replaced by the WordFragment value using the replaceVariableByWordFragment method
                    $wordFragmentsValues = array();
                    foreach ($vars as &$varsRow) {
                        foreach ($varsRow as $varKeyRow => $varValueRow) {
                            if ($varValueRow instanceof WordFragment) {
                                $uniqueId = uniqid(mt_rand(999, 9999));
                                $uniqueKey = $this->getTemplateSymbol() . $uniqueId . $this->getTemplateSymbol();
                                $wordFragmentsValues[$uniqueId] = $varsRow[$varKeyRow];
                                $varsRow[$varKeyRow] = $uniqueKey;
                            }
                        }
                    }

                    foreach ($vars as $key => $rowValue) {
                        foreach ($foundNodes as $node) {
                            $domNode = dom_import_simplexml($node);
                            if (!is_object($referenceNode) || !$domNode->parentNode->isSameNode($parentNode)) {
                                $referenceNode = $domNode;
                                $parentNode = $domNode->parentNode;
                                $tableCounter++;
                            }
                            if (!$firstMatch || ($firstMatch && $tableCounter < 2)) {
                                $newNode = $domNode->cloneNode(true);
                                $textNodes = $newNode->getElementsBytagName('t');
                                foreach ($textNodes as $text) {
                                    for ($k = 0; $k < count($search); $k++) {
                                        $sxText = simplexml_import_dom($text);
                                        $strNode = (string) $sxText;
                                        if (!empty($rowValue[$varKeys[$k]]) ||
                                                $rowValue[$varKeys[$k]] === 0 ||
                                                $rowValue[$varKeys[$k]] === "0") {
                                            if (isset($options['parseLineBreaks']) && $options['parseLineBreaks']) {
                                                //parse $val for \n\r, \r\n, \n or \r and carriage returns
                                                $rowValue[$varKeys[$k]] = str_replace(array('\n\r', '\r\n', '\n', '\r', "\n\r", "\r\n", "\n", "\r"), '__LINEBREAK__', $rowValue[$varKeys[$k]]);
                                            }
                                            $strNode = str_replace($search[$k], $rowValue[$varKeys[$k]], $strNode);
                                        } else {
                                            $strNode = str_replace($search[$k], '', $strNode);
                                        }
                                        $sxText[0] = $strNode;
                                    }
                                }
                                $parentNode->insertBefore($newNode, $referenceNode);
                            }
                        }
                    }
                    //Remove the original nodes
                    $tableCounter2 = 0;
                    foreach ($foundNodes as $node) {
                        $domNode = dom_import_simplexml($node);
                        if ($firstMatch && !$domNode->parentNode->isSameNode($parentNode)) {
                            $parentNode = $domNode->parentNode;
                            $tableCounter2++;
                        }
                        if ($tableCounter2 < 2) {
                            $domNode->parentNode->removeChild($domNode);
                        }
                    }
                    
                    $stringDoc = $dom->asXML();

                    $this->_modifiedHeadersFooters[$footer] = $stringDoc;
                    $this->saveToZip($dom, $footer);

                    // replace existing WordFragment placeholders
                    if (count($wordFragmentsValues) > 0) {
                        $this->replaceVariableByWordFragment($wordFragmentsValues, array('type' => $type, 'target' => 'footer'));
                    }
                }
            }
        }
    }
    
    /**
     * Replaces an array of variables by external files
     *
     * @access public
     * @param array $variables
     *  keys: variable names
     *  values: path to the external DOCX, RTF, HTML or MHT file
     * @param array $options
     * 'firstMatch' (boolean) if true it only replaces the first variable match. Default is set to false.
     * 'matchSource' (boolean) if true (default value)tries to preserve as much as posible the styles of the docx to be included
     * 'preprocess' (boolean) if true does some preprocessing on the docx file to add
     *  WARNING: beware that the docx to insert gets modified so please make a safeguard copy first
     * @return void
     */
    public function replaceVariableByExternalFile($variables, $options = array())
    {
        foreach ($variables as $key => $value) {
            $options['src'] = $value;
            $extension = strtoupper($this->getFileExtension($value));
            switch ($extension) {
                case 'DOCX':
                    if (!isset($options['matchSource'])) {
                        $options['matchSource'] = true;
                    }
                    $file = new WordFragment($this);
                    $file->addDOCX($options);
                    $this->replaceVariableByWordFragment(array($key => $file), $options);
                    break;
                case 'HTML':
                    $options['html'] = file_get_contents($value);
                    $file = new WordFragment($this);
                    $file->addHTML($options);
                    $this->replaceVariableByWordFragment(array($key => $file), $options);
                    break;
                case 'RTF':
                    $file = new WordFragment($this);
                    $file->addRTF($options);
                    $this->replaceVariableByWordFragment(array($key => $file), $options);
                    break;
                case 'MHT':
                    $file = new WordFragment($this);
                    $file->addMHT($options);
                    $this->replaceVariableByWordFragment(array($key => $file), $options);
                    break;
                default:
                    PhpdocxLogger::logger('Invalid file extension', 'fatal');
            }
        }
    }

    /**
     * Replace a template variable with WordML obtained from HTML via the
     * embedHTML method.
     *
     * @access public
     * @param string $var Value of the variable.
     * @param type inline or block
     * @param string $html HTML source
     * @param array $options:
     * 'isFile' (boolean),
     * 'addDefaultStyles' (boolean) true as default, if false prevents adding default styles when strictWordStyles is false
     * 'baseURL' (string),
     * 'customListStyles' (bool) if true try to use the predefined custom lists
     * 'downloadImages' (boolean),
     * 'filter' (string) could be an string denoting the id, class or tag to be filtered.
     * If you want only a class introduce .classname, #idName for an id or <htmlTag> for a particular tag. One can also use
     * standard XPath expresions supported by PHP.
     * 'firstMatch' (boolean) if true it only replaces the first variable match. Default is set to false,
     * 'generateCustomListStyles' (bool) default as true. If true generates automatically the custom list styles from the list styles (decimal, lower-alpha, lower-latin, lower-roman, upper-alpha, upper-latin, upper-roman)
     * 'parseAnchors' (boolean),
     * 'parseDivs' (paragraph, table): parses divs as paragraphs or tables,
     * 'parseFloats' (boolean),
     * 'removeLineBreaks' (boolean), if true removes line breaks that can be generated when transforming HTML,
     * 'strictWordStyles' (boolean) if true ignores all CSS styles and uses the styles set via the wordStyles option (see next)
     * 'target': document, header, footer, footnote, endnote, comment,
     * 'useHTMLExtended' (boolean)  if true uses HTML extended tags. Default as false
     * 'wordStyles' (array) associates a particular class, id or HTML tag to a Word style
     *
     * @return void
     */
    public function replaceVariableByHTML($var, $type = 'block', $html = '<html><body></body></html>', $options = array())
    {
        if (!extension_loaded('tidy')){
            throw new \Exception('Please install and enable Tidy for PHP (http://php.net/manual/en/book.tidy.php) to transform HTML to DOCX.');
        }

        if (isset($options['target'])) {
            $target = $options['target'];
        } else {
            $target = 'document';
        }
        if (isset($options['firstMatch'])) {
            $firstMatch = $options['firstMatch'];
        } else {
            $firstMatch = false;
        }
        $options['type'] = $type;
        $htmlFragment = new WordFragment($this, $target);
        $htmlFragment->embedHTML($html, $options);
        $this->replaceVariableByWordFragment(array($var => $htmlFragment), $options);
    }

    /**
     * Replaces an array of variables by their values
     *
     * @access public
     * @param array $variables
     *  keys: variable names
     *  values: text we want to insert
     * @param string $options
     * 'target': document (default), header, footer, footnote, endnote, comment
     * 'firstMatch' (boolean) if true it only replaces the first variable match. Default is set to false.
     * 'parseLineBreaks' (boolean) if true (default is false) parses the line breaks to include them in the Word document
     * 'raw' (boolean) if true (default is false) replaces the variable by a string regardless the variable scope (tag values, attributes...). 
     *     Only allows to replace a variable by a plain string. Use with caution
     * @return void
     */
    public function replaceVariableByText($variables, $options = array())
    {
        if (isset($options['target'])) {
            $target = $options['target'];
        } else {
            $target = 'document';
        }

        if (isset($options['raw']) && $options['raw'] === true) {
            foreach ($variables as $keyVariable => $valueVariable) {
                if ($target == 'document') {
                    $this->_wordDocumentC = str_replace(self::$_templateSymbol . $keyVariable . self::$_templateSymbol, $valueVariable, $this->_wordDocumentC);
                } else if ($target == 'footnote') {
                    $newFootnotesTXML = str_replace(self::$_templateSymbol . $keyVariable . self::$_templateSymbol, $valueVariable, $this->_wordFootnotesT->saveXML());
                    $this->_wordFootnotesT->loadXML($newFootnotesTXML);
                } else if ($target == 'endnote') {
                    $newEndnotesTXML = str_replace(self::$_templateSymbol . $keyVariable . self::$_templateSymbol, $valueVariable, $this->_wordEndnotesT->saveXML());
                    $this->_wordEndnotesT->loadXML($newEndnotesTXML);
                } else if ($target == 'comment') {
                    $newCommentsTXML = str_replace(self::$_templateSymbol . $keyVariable . self::$_templateSymbol, $valueVariable, $this->_wordCommentsT->saveXML());
                    $this->_wordCommentsT->loadXML($newCommentsTXML);
                } else if ($target == 'header') {
                    $xpathHeaders = simplexml_import_dom($this->_contentTypeT);
                    $xpathHeaders->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/package/2006/content-types');
                    $xpathHeadersResults = $xpathHeaders->xpath('ns:Override[@ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"]');
                    foreach ($xpathHeadersResults as $headersResults) {
                        $header = substr($headersResults['PartName'], 1);
                        $loadContent = $this->getFromZip($header);
                        if (empty($loadContent)) {
                            $loadContent = $this->_modifiedHeadersFooters[$header];
                        }
                        if (!empty($loadContent)) {
                            $newHeaderTXML = str_replace(self::$_templateSymbol . $keyVariable . self::$_templateSymbol, $valueVariable, $loadContent);
                            $this->_modifiedHeadersFooters[$header] = $newHeaderTXML;
                            $this->saveToZip($newHeaderTXML, $header);
                        }
                    }
                } else if ($target == 'footer') {
                    $xpathFooters = simplexml_import_dom($this->_contentTypeT);
                    $xpathFooters->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/package/2006/content-types');
                    $xpathFootersResults = $xpathFooters->xpath('ns:Override[@ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"]');
                    foreach ($xpathFootersResults as $footersResults) {
                        $footer = substr($footersResults['PartName'], 1);
                        $loadContent = $this->getFromZip($footer);
                        if (empty($loadContent)) {
                            $loadContent = $this->_modifiedHeadersFooters[$footer];
                        }
                        if (!empty($loadContent)) {
                            $newFooterTXML = str_replace(self::$_templateSymbol . $keyVariable . self::$_templateSymbol, $valueVariable, $loadContent);
                            $this->_modifiedHeadersFooters[$footer] = $newHeaderTXML;
                            $this->saveToZip($newFooterTXML, $footer);
                        }
                    }
                }
            }
        } else {
            if ($target == 'document') {
                $loadContent = $this->_documentXMLElement . '<w:body>' . $this->_wordDocumentC . '</w:body></w:document>';
                $dom = $this->variable2Text($variables, $loadContent, $options);
                $stringDoc = $dom->asXML();
                $bodyTag = explode('<w:body>', $stringDoc);
                $this->_wordDocumentC = str_replace('</w:body></w:document>', '', $bodyTag[1]);
            } else if ($target == 'footnote') {
                $dom = $this->variable2Text($variables, $this->_wordFootnotesT->saveXML(), $options)->saveXML();
                $optionEntityLoader = libxml_disable_entity_loader(true);
                $this->_wordFootnotesT->loadXML($dom);
                libxml_disable_entity_loader($optionEntityLoader);
            } else if ($target == 'endnote') {
                $dom = $this->variable2Text($variables, $this->_wordEndnotesT->saveXML(), $options)->saveXML();
                $optionEntityLoader = libxml_disable_entity_loader(true);
                $this->_wordEndnotesT->loadXML($dom);
                libxml_disable_entity_loader($optionEntityLoader);
            } else if ($target == 'comment') {
                $dom = $this->variable2Text($variables, $this->_wordCommentsT->saveXML(), $options)->saveXML();
                $optionEntityLoader = libxml_disable_entity_loader(true);
                $this->_wordCommentsT->loadXML($dom);
                libxml_disable_entity_loader($optionEntityLoader);
            } else if ($target == 'header') {
                $xpathHeaders = simplexml_import_dom($this->_contentTypeT);
                $xpathHeaders->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/package/2006/content-types');
                $xpathHeadersResults = $xpathHeaders->xpath('ns:Override[@ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"]');
                foreach ($xpathHeadersResults as $headersResults) {
                    $header = substr($headersResults['PartName'], 1);
                    $loadContent = $this->getFromZip($header);
                    if (empty($loadContent)) {
                        $loadContent = $this->_modifiedHeadersFooters[$header];
                    }
                    if (!empty($loadContent)) {
                        $dom = $this->variable2Text($variables, $loadContent, $options);
                        $this->_modifiedHeadersFooters[$header] = $dom->saveXML();
                        $this->saveToZip($dom, $header);
                    }
                }
            } else if ($target == 'footer') {
                $xpathFooters = simplexml_import_dom($this->_contentTypeT);
                $xpathFooters->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/package/2006/content-types');
                $xpathFootersResults = $xpathFooters->xpath('ns:Override[@ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"]');
                foreach ($xpathFootersResults as $footersResults) {
                    $footer = substr($footersResults['PartName'], 1);
                    $loadContent = $this->getFromZip($footer);
                    if (empty($loadContent)) {
                        $loadContent = $this->_modifiedHeadersFooters[$footer];
                    }
                    if (!empty($loadContent)) {
                        $dom = $this->variable2Text($variables, $loadContent, $options);
                        $this->_modifiedHeadersFooters[$footer] = $dom->saveXML();
                        $this->saveToZip($dom, $footer);
                    }
                }
            }
        }
    }

    /**
     * Replaces an array of variables by Word Fragments
     *
     * @access public
     * @param array $variables
     *  keys: variable names
     *  values: instances of the WordFragment or DOCXPath result objects
     * @param string $options
     * 'target': document (default), header, footer, footnote, endnote or comment
     * 'firstMatch' (boolean) if true it only replaces the first variable match. Default is set to false.
     * 'type': inline (only replaces the variable) or block (removes the variable and its containing paragraph)
     * @return void
     */
    public function replaceVariableByWordFragment($variables, $options = array())
    {
        if (isset($options['firstMatch'])) {
            $firstMatch = $options['firstMatch'];
        } else {
            $firstMatch = false;
        }
        if (isset($options['target'])) {
            $target = $options['target'];
        } else {
            $target = 'document';
        }
        if (isset($options['type'])) {
            $type = $options['type'];
        } else {
            $type = 'block';
        }
        if ($target == 'document') {
            $loadContent = $this->_documentXMLElement . '<w:body>' .
                    $this->_wordDocumentC . '</w:body></w:document>';
            $stringDoc = $this->variable4WordFragment($variables, $type, $loadContent, $firstMatch);
            $bodyTag = explode('<w:body>', $stringDoc);
            $this->_wordDocumentC = str_replace('</w:body></w:document>', '', $bodyTag[1]);
        } else if ($target == 'footnote') {
            $stringDoc = $this->variable4WordFragment($variables, $type, $this->_wordFootnotesT->saveXML(), $firstMatch);
            $optionEntityLoader = libxml_disable_entity_loader(true);
            $this->_wordFootnotesT->loadXML($stringDoc);
            libxml_disable_entity_loader($optionEntityLoader);
        } else if ($target == 'endnote') {
            $stringDoc = $this->variable4WordFragment($variables, $type, $this->_wordEndnotesT->saveXML(), $firstMatch);
            $optionEntityLoader = libxml_disable_entity_loader(true);
            $this->_wordEndnotesT->loadXML($stringDoc);
            libxml_disable_entity_loader($optionEntityLoader);
        } else if ($target == 'comment') {
            $stringDoc = $this->variable4WordFragment($variables, $type, $this->_wordCommentsT->saveXML(), $firstMatch);
            $optionEntityLoader = libxml_disable_entity_loader(true);
            $this->_wordCommentsT->loadXML($stringDoc);
            libxml_disable_entity_loader($optionEntityLoader);
        } else if ($target == 'header') {
            $xpathHeaders = simplexml_import_dom($this->_contentTypeT);
            $xpathHeaders->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/package/2006/content-types');
            $xpathHeadersResults = $xpathHeaders->xpath('ns:Override[@ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"]');
            foreach ($xpathHeadersResults as $headersResults) {
                foreach ($variables as $keyVariable => $valueVariable) {
                    $header = substr($headersResults['PartName'], 1);
                    $headerContent = $this->getFromZip($header);
                    if (empty($headerContent)) {
                        $headerContent = $this->_modifiedHeadersFooters[$header];
                    }
                    $headerName = explode('/', $header);
                    $domHeader = $this->variableHeaderFooterByWordFragments(array($keyVariable => $valueVariable), $type, $headerName[1], $headerContent, $firstMatch);
                    $this->_modifiedHeadersFooters[$header] = $domHeader;
                    $this->saveToZip($domHeader, $header);
                }
            }
        } else if ($target == 'footer') {
            $xpathFooters = simplexml_import_dom($this->_contentTypeT);
            $xpathFooters->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/package/2006/content-types');
            $xpathFootersResults = $xpathFooters->xpath('ns:Override[@ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"]');
            foreach ($xpathFootersResults as $footersResults) {
                foreach ($variables as $keyVariable => $valueVariable) {
                    $footer = substr($footersResults['PartName'], 1);
                    $footerContent = $this->getFromZip($footer);
                    if (empty($footerContent)) {
                        $footerContent = $this->_modifiedHeadersFooters[$footer];
                    }
                    $footerName = explode('/', $footer);
                    $domFooter = $this->variableHeaderFooterByWordFragments(array($keyVariable => $valueVariable), $type, $footerName[1], $footerContent, $firstMatch);
                    $this->_modifiedHeadersFooters[$footer] = $domFooter;
                    $this->saveToZip($domFooter, $footer);
                }
            }
        }
    }
    
    /**
     * Replaces an array of variables by plain WordML
     * WARNING: the system does not validate the WordML against any scheme so
     * you have to make sure by your own that the used WORDML is correctly encoded
     * and moreover has NO relationships that require to modify the rels files.
     *
     * @access public
     * @param array $variables
     *  keys: variable names
     *  values: WordML code
     * @param string $options
     * 'firstMatch' (boolean) if true it only replaces the first variable match. Default is set to false.
     * 'type': inline (only replaces the variable) or block (removes the variable and its containing paragraph)
     * 'target': document (default). By the time being header, footer, footnote, endnote, comment are not supported
     * @return void
     */
    public function replaceVariableByWordML($variables, $options = array('type' => 'block'))
    {
        $counter = 0;
        foreach ($variables as $key => $value) {
            ${'wf_' . $counter} = new WordFragment();
            ${'wf_' . $counter}->addRawWordML($value);
            $variables[$key] = ${'wf_' . $counter};
            $counter++;
        }
        $this->replaceVariableByWordFragment($variables, $options);
    }
    
    /**
     * Checks or unchecks template checkboxes
     *
     * @access public
     * @param array $variables 
     *  keys: variable names
     *  values: 1 (check), 0 (uncheck)
     */
    public function tickCheckboxes($variables)
    {
        $loadContent = $this->_documentXMLElement . '<w:body>' .
                $this->_wordDocumentC . '</w:body></w:document>';
        $domDocument = new \DOMDocument();
        $optionEntityLoader = libxml_disable_entity_loader(true);
        $domDocument->loadXML($loadContent);
        libxml_disable_entity_loader($optionEntityLoader);
        $docXPath = new \DOMXPath($domDocument);
        $docXPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $docXPath->registerNamespace('w14', 'http://schemas.microsoft.com/office/word/2010/wordml');
        foreach ($variables as $var => $value) {
            if (empty($value)) {
                $value = 0;
            } else {
                $value = 1;
            }
            //First we check for legacy checkboxes
            $searchTerm = self::$_templateSymbol . $var . self::$_templateSymbol;
            $queryDoc = '//w:ffData[w:statusText[@w:val="' . $searchTerm . '"]]';
            $affectedNodes = $docXPath->query($queryDoc);
            foreach ($affectedNodes as $node) {
                $nodeVals = $node->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'default');
                $nodeVals->item(0)->setAttribute('w:val', $value);
            }
            //Now we look for Word 2010 sdt checkboxes
            $queryDoc = '//w:sdtPr[w:tag[@w:val="' . $searchTerm . '"]]';
            $affectedNodes = $docXPath->query($queryDoc);
            foreach ($affectedNodes as $node) {
                $nodeVals = $node->getElementsByTagNameNS('http://schemas.microsoft.com/office/word/2010/wordml', 'checked');
                $nodeVals->item(0)->setAttribute('w14:val', $value);
                //Now change the selected symbol for checked or unchecked
                $sdt = $node->parentNode;
                $txt = $sdt->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 't');
                if ($value == 1) {
                    $txt->item(0)->nodeValue = '☒';
                } else {
                    $txt->item(0)->nodeValue = '☐';
                }
            }
        }

        $stringDoc = $domDocument->saveXML();
        $bodyTag = explode('<w:body>', $stringDoc);
        $this->_wordDocumentC = str_replace('</w:body></w:document>', '', $bodyTag[1]);
    }

    /**
     * Extract the PHPDocX type variables from an existing template
     *
     * @access private
     */
    private function extractVariables($target, $documentSymbol, $variables)
    {
        $i = 0;
        foreach ($documentSymbol as $documentSymbolValue) {
            // avoid first and last values and even positions
            if ($i == 0 || $i == count($documentSymbol) || $i % 2 == 0) {
                $i++;
                continue;
            } else {
                $i++;
                if (empty($prefixes)) {
                    $variables[$target][] = strip_tags($documentSymbolValue);
                } else {
                    foreach ($prefixes as $value) {
                        if (($pos = strpos($documentSymbolValue, $value, 0)) !== false) {
                            $variables[$target][] = strip_tags($documentSymbolValue);
                        }
                    }
                }
            }
        }
        return $variables;
    }
    
    
    /**
     * Gets jpg image dpi
     *
     * @access private
     * @param string $filename
     * @return array
     */
    private function getDpiJpg($filename)
    {
        $a = fopen($filename, 'r');
        $string = fread($a, 20);
        fclose($a);
        $type = hexdec(bin2hex(substr($string, 13, 1)));
        $data = bin2hex(substr($string, 14, 4));
        if ($type == 1) {
            $x = substr($data, 0, 4);
            $y = substr($data, 4, 4);
            return array(hexdec($x), hexdec($y));
        } else if ($type == 2) {
            $x = floor(hexdec(substr($data, 0, 4)) / 2.54);
            $y = floor(hexdec(substr($data, 4, 4)) / 2.54);
            return array($x, $y);
        } else {
            return array(96, 96);
        }
    }

    /**
     * Gets png image dpi
     *
     * @access private
     * @param string $filename
     * @return array
     */
    private function getDpiPng($filename)
    {
        $pngScaleFactor = 29.5;
        $a = fopen($filename, 'r');
        $string = fread($a, 1000);
        $aux = strpos($string, 'pHYs');
        if ($aux > 0) {
            $type = hexdec(bin2hex(substr($string, $aux + strlen('pHYs') + 16, 1)));
        }
        if ($aux > 0 && $type = 1) {
            $data = bin2hex(substr($string, $aux + strlen('pHYs'), 16));
            fclose($a);
            $x = substr($data, 0, 8);
            $y = substr($data, 8, 8);
            return array(round(hexdec($x) / $pngScaleFactor), round(hexdec($y) / $pngScaleFactor));
        } else {
            return array(96, 96);
        }
    }

    /**
     * Gets the file extension
     *
     * @access private
     * @param string $filename
     * @return string
     */
    private function getFileExtension($filename)
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        
        return $extension;
    }

    /**
     * Replaces a placeholder images by external images
     * 
     * @access public
     * @param string $variable this variable uniquely identifies the image we want to replace
     * @param string $src path to the substitution image
     * @param string $options
     * 'target': document, header, footer, footnote, endnote, comment
     * 'width' (mixed): the value in cm (float) or 'auto' (use image size), 0 to not change the previous size
     * 'height' (mixed): the value in cm (float) or 'auto' (use image size), 0 to not change the previous size
     * 'dpi' (int): dots per inch. This parameter is only taken into account if width or height are set to auto.
     * 'streamMode' (bool) if true, use src path as stream. Default is false
     * If any of these formatting parameters is not set, the width and/or height of the placeholder image will be preserved
     * @param (string) $loadContent st
     * @param (string) $rels
     * @return \DOMDocument Object
     */
    private function Image4Image($variable, $src, $options = array(), $loadContent, $rels = 'document')
    {
        if (!file_exists($src) && (!isset($options['streamMode']) || !$options['streamMode'])) {
            PhpdocxLogger::logger('The' . $src . ' path seems not to be correct. Unable to obtain image file.', 'fatal');
        }

        if (isset($options['firstMatch'])) {
            $firstMatch = $options['firstMatch'];
        } else {
            $firstMatch = false;
        }

        $cx = 0;
        $cy = 0;
        
        // file image
        if (!isset($options['streamMode']) || !$options['streamMode']) {
            if (file_exists($src) == 'true') {
                //Get the name and extension of the replacement image
                $imageNameArray = explode('/', $src);
                if (count($imageNameArray) > 1) {
                    $imageName = array_pop($imageNameArray);
                } else {
                    $imageName = $src;
                }
                $imageExtensionArray = explode('.', $src);
                $extension = strtolower(array_pop($imageExtensionArray));
            } else {
                PhpdocxLogger::logger('Image does not exist.', 'fatal');
            }
        }

        // stream image
        if (isset($options['streamMode']) && $options['streamMode'] == true) {
            if (function_exists('getimagesizefromstring')) {
                $imageStream = file_get_contents($src);
                $attrImage = getimagesizefromstring($imageStream);
                $mimeType = $attrImage['mime'];

                switch ($mimeType) {
                    case 'image/gif':
                        $extension = 'gif';
                        break;
                    case 'image/jpg':
                        $extension = 'jpg';
                        break;
                    case 'image/jpeg':
                        $extension = 'jpeg';
                        break;
                    case 'image/png':
                        $extension = 'png';
                        break;
                    default:
                        break;
                }
            } else {
                if (!isset($options['mime'])) {
                    PhpdocxLogger::logger('getimagesizefromstring function is not available. Please set the mime option or use the file mode.', 'fatal');
                }
            }
        }

        if (isset($options['mime']) && !empty($options['mime'])) {
            $mimeType = $options['mime'];
        }

        $wordScaleFactor = 360000;
        if (isset($options['dpi'])) {
            $dpiX = $options['dpi'];
            $dpiY = $options['dpi'];
        } else {
            if ((isset($options['width']) && $options['width'] == 'auto') ||
                    (isset($options['height']) && $options['height'] == 'auto')) {
                if ($extension == 'jpg' || $extension == 'jpeg') {
                    list($dpiX, $dpiY) = $this->getDpiJpg($src);
                } else if ($extension == 'png') {
                    list($dpiX, $dpiY) = $this->getDpiPng($src);
                } else {
                    $dpiX = 96;
                    $dpiY = 96;
                }
            }
        }

        //Check if a width and height have been set
        $width = 0;
        $height = 0;
        if (isset($options['width']) && $options['width'] != 'auto') {
            $cx = (int) round($options['width'] * $wordScaleFactor);
        }
        if (isset($options['height']) && $options['height'] != 'auto') {
            $cy = (int) round($options['height'] * $wordScaleFactor);
        }
        //Proceed to compute the sizes if the width or height are set to auto
        if ((isset($options['width']) && $options['width'] == 'auto') ||
                (isset($options['height']) && $options['height'] == 'auto')) {
            if (!isset($options['streamMode']) || !$options['streamMode']) {
                // file mode
                $realSize = getimagesize($src);
            } else {
                // stream mode
                if (function_exists('getimagesizefromstring')) {
                    $imageStream = file_get_contents($src);
                    $realSize = getimagesizefromstring($imageStream);
                } else {
                    if (!isset($data['width']) || !isset($data['height'])) {
                        PhpdocxLogger::logger('getimagesizefromstring function is not available. Please set width and height options or use the file mode.', 'fatal');

                        $realSize = array($options['width'], $options['height']);
                    }
                }
            }
        }
        if (isset($options['width']) && $options['width'] == 'auto') {
            $cx = (int) round($realSize[0] * 2.54 / $dpiX * $wordScaleFactor);
        }
        if (isset($options['height']) && $options['height'] == 'auto') {
            $cy = (int) round($realSize[1] * 2.54 / $dpiY * $wordScaleFactor);
        }
        $docDOM = new \DOMDocument();
        $optionEntityLoader = libxml_disable_entity_loader(true);
        $docDOM->loadXML($loadContent);
        libxml_disable_entity_loader($optionEntityLoader);
        $domImages = $docDOM->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/' .
                'wordprocessingDrawing', 'docPr');

        $imageCounter = 0;
        //create a new Id
        $id = uniqid(rand(99,9999999), true);
        $ind = 'rId' . $id;
        $relsCounter = 0;
        for ($i = 0; $i < $domImages->length; $i++) {
            if ($domImages->item($i)->getAttribute('descr') ==
                    self::$_templateSymbol . $variable . self::$_templateSymbol &&
                    $imageCounter == 0) {
                
                //generate new relationship
                $relString = '<Relationship Id="' . $ind . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/img' . $id . '.' . $extension . '" />';
                if ($rels == 'document') {
                    if ($relsCounter == 0) {
                        $this->generateRELATIONSHIP($ind, 'image', 'media/img' . $id . '.' . $extension);
                        $relsCounter++;
                    }
                } else if ($rels == 'footnote' || $rels == 'endnote' || $rels == 'comment') {
                    self::$_relsNotesImage[$rels][] = array('rId' => 'rId' . $id, 'name' => $id, 'extension' => $extension);
                } else {
                    $relsXML = $this->getFromZip('word/_rels/' . $rels . '.xml.rels');
                    if (empty($relsXML)){
                      $relsXML = $this->_modifiedRels['word/_rels/' . $rels . '.xml.rels'];  
                    }
                    $relationship = '<Relationship Target="media/img' . $id . '.' . $extension . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Id="rId' . $id . '"/>';
                    $relsXML = str_replace('</Relationships>', $relationship . '</Relationships>', $relsXML);
                    $this->_modifiedRels['word/_rels/' . $rels . '.xml.rels'] = $relsXML;
                    $this->saveToZip($relsXML, 'word/_rels/' . $rels . '.xml.rels');
                }
                //generate content type if it does not exist yet
                $this->generateDEFAULT($extension, 'image/' . $extension);
                //modify the image data to modify the r:embed attribute
                $domImages->item($i)->parentNode
                        ->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'blip')
                        ->item(0)->setAttribute('r:embed', $ind);
                if ($cx != 0) {
                    $domImages->item($i)->parentNode
                            ->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing', 'extent')
                            ->item(0)->setAttribute('cx', $cx);
                    $xfrmNode = $domImages->item($i)->parentNode
                                    ->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'xfrm')->item(0);
                    $xfrmNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'ext')
                            ->item(0)->setAttribute('cx', $cx);
                }
                if ($cy != 0) {
                    $domImages->item($i)->parentNode
                            ->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing', 'extent')
                            ->item(0)->setAttribute('cy', $cy);
                    $xfrmNode = $domImages->item($i)->parentNode
                                    ->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'xfrm')->item(0);
                    $xfrmNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'ext')
                            ->item(0)->setAttribute('cy', $cy);
                }
                if ($options['firstMatch']) {
                    $imageCounter++;
                    $domImages->item($i)->setAttribute('descr', '');
                }
            }
        }
        // copy the image in the (base) template with the new name
        $this->_zipDocx->addFile('word/media/img' . $id . '.' . $extension, $src);

        return $docDOM;
    }
    
    /**
     * Removes a template variable with its container paragraph
     *
     * @access private
     * @param string $variableName
     * @param string $loadContent
     */
    private function removeVariableBlock($variableName, $loadContent)
    {
        if (!self::$_preprocessed) {
            $loadContent = $this->repairVariables(array($variableName => ''), $loadContent);
        }
        $domDocument = new \DomDocument();
        $optionEntityLoader = libxml_disable_entity_loader(true);
        $domDocument->loadXML($loadContent);
        libxml_disable_entity_loader($optionEntityLoader);
        //Use XPath to find all paragraphs that include the variable name
        $name = self::$_templateSymbol . $variableName . self::$_templateSymbol;
        $xpath = new \DOMXPath($domDocument);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $query = '//w:p[w:r/w:t[text()[contains(.,"' . $variableName . '")]]]';
        $affectedNodes = $xpath->query($query);
        foreach ($affectedNodes as $node) {
            $paragraphContents = $node->ownerDocument->saveXML($node);
            $paragraphText = strip_tags($paragraphContents);
            if (($pos = strpos($paragraphText, $name, 0)) !== false) {
                //If we remove a paragraph inside a table cell we need to take special care
                if ($node->parentNode->nodeName == 'w:tc') {
                    $tcChilds = $node->parentNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'p');
                    if ($tcChilds->length > 1) {
                        $node->parentNode->removeChild($node);
                    } else {
                        $emptyP = $domDocument->createElement("w:p");
                        $node->parentNode->appendChild($emptyP);
                        $node->parentNode->removeChild($node);
                    }
                } else {
                    $node->parentNode->removeChild($node);
                }
            }
        }
        $stringDoc = $domDocument->saveXML();
        return $stringDoc;
    }

    /**
     * Prepares a single PHPDocX variable for substitution
     *
     * @access private
     * @param array $variables
     * @param string $content
     * @return string
     */
    private function repairVariables($variables, $content)
    {
        $documentSymbol = explode(self::$_templateSymbol, $content);
        foreach ($variables as $var => $value) {
            foreach ($documentSymbol as $documentSymbolValue) {
                $tempSearch = trim(strip_tags($documentSymbolValue));
                if ($tempSearch == $var) {
                    $pos = strpos($content, $documentSymbolValue);
                    if ($pos !== false) {
                        $content = substr_replace($content, $var, $pos, strlen($documentSymbolValue));
                    }
                }
                if (strpos($documentSymbolValue, 'xml:space="preserve"')) {
                    $preserve = true;
                }
            }
            if (isset($preserve) && $preserve) {
                $query = '//w:t[text()[contains(., "' . self::$_templateSymbol . $var . self::$_templateSymbol . '")]]';
                $docDOM = new \DOMDocument();
                $optionEntityLoader = libxml_disable_entity_loader(true);
                $docDOM->loadXML($content);
                libxml_disable_entity_loader($optionEntityLoader);
                $docXPath = new \DOMXPath($docDOM);
                $docXPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                $affectedNodes = $docXPath->query($query);
                foreach ($affectedNodes as $node) {
                    $space = $node->getAttribute('xml:space');
                    if (isset($space) && $space == 'preserve') {
                        //Do nothing 
                    } else {
                        $str = $node->nodeValue;
                        $firstChar = $str[0];
                        if ($firstChar == ' ') {
                            $node->nodeValue = substr($str, 1);
                        }
                        $node->setAttribute('xml:space', 'preserve');
                    }
                }
                $content = $docDOM->saveXML($docDOM->documentElement);
                //$content = html_entity_decode($content, ENT_NOQUOTES, 'UTF-8');
            }
        }
        return $content;
    }

    /**
     * Replaces a single variable by a WordFragment
     *
     * @access private
     * @param string $var
     * @param WordFragment $val
     * @param string $type
     * @param string $loadContent
     * @param bool $firstMatch
     */
    private function singleVariable4WordFragment($var, $val, $type, $loadContent, $firstMatch)
    {
        $docDOM = new \DOMDocument();
        $optionEntityLoader = libxml_disable_entity_loader(true);
        $docDOM->loadXML($loadContent);
        libxml_disable_entity_loader($optionEntityLoader);
        $docXpath = new \DOMXPath($docDOM);

        
        if ($val instanceof WordFragment) {
            PhpdocxLogger::logger('Replacing a variable by a WordML fragment', 'info');
        } else if ($val instanceof \DOCXPathResult) {
            PhpdocxLogger::logger('Replacing a variable by a DOCXPath result', 'info');
        } else {
            PhpdocxLogger::logger('This methods requires that the variable value is a WordML fragment', 'fatal');
        }
        $wordML = (string) $val;
        if ($type == 'inline') {
            $wordML = $this->cleanWordMLBlockElements($wordML);
        }
        $searchString = self::$_templateSymbol;
        $searchVariable = self::$_templateSymbol . $var . self::$_templateSymbol;
        $query = '//w:p[w:r/w:t[text()[contains(., "' . $searchVariable . '")]]]';
        if (isset($firstMatch) && $firstMatch) {
            $query = '(' . $query . ')[1]';
        }

        $docNodes = $docXpath->query($query);
        foreach ($docNodes as $node) {
            $nodeText = $node->ownerDocument->saveXML($node);
            $cleanNodeText = strip_tags($nodeText);
            if (strpos($cleanNodeText, $searchVariable) !== false) {
                if ($type == 'block') {
                    $cursorNode = $docDOM->createElement('cursorWordML');
                    //We should take care of the case that is empty and inside a table cell (fix word bug empty cells)
                    if ($wordML == '' && $node->parentNode->nodeName == 'w:tc') {
                        $wordML = '<w:p />';
                    }
                    $node->parentNode->insertBefore($cursorNode, $node);
                    $node->parentNode->removeChild($node);
                } else if ($type == 'inline') {
                    $textNode = $node->ownerDocument->saveXML($node);
                    $textChunks = explode($searchString, $textNode);
                    $limit = count($textChunks);
                    for ($j = 0; $j < $limit; $j++) {
                        $cleanValue = strip_tags($textChunks[$j]);
                        if ($cleanValue == $var) {
                            $textChunks[$j] = '</w:t></w:r><cursorWordML/><w:r><w:t xml:space="preserve">';
                        }
                    }
                    $newNodeText = implode($searchString, $textChunks);
                    $newNodeText = str_replace(self::$_templateSymbol . '</w:t></w:r><cursorWordML/><w:r><w:t xml:space="preserve">', '</w:t></w:r><cursorWordML/><w:r><w:t xml:space="preserve">', $newNodeText);
                    $newNodeText = str_replace('</w:t></w:r><cursorWordML/><w:r><w:t xml:space="preserve">' . self::$_templateSymbol, '</w:t></w:r><cursorWordML/><w:r><w:t xml:space="preserve">', $newNodeText);
                    $tempDoc = new \DOMDocument();
                    $optionEntityLoader = libxml_disable_entity_loader(true);
                    $tempDoc->loadXML('<w:root xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" 
                                               xmlns:mo="http://schemas.microsoft.com/office/mac/office/2008/main" 
                                               xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" 
                                               xmlns:mv="urn:schemas-microsoft-com:mac:vml" 
                                               xmlns:o="urn:schemas-microsoft-com:office:office" 
                                               xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" 
                                               xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" 
                                               xmlns:v="urn:schemas-microsoft-com:vml" 
                                               xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" 
                                               xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" 
                                               xmlns:w10="urn:schemas-microsoft-com:office:word" 
                                               xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" 
                                               xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" 
                                               xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup" 
                                               xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk" 
                                               xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml" 
                                               xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape" 
                                               mc:Ignorable="w14 wp14">' . $newNodeText . '</w:root>');
                    libxml_disable_entity_loader($optionEntityLoader);
                    $newCursorNode = $tempDoc->documentElement->firstChild;
                    $cursorNode = $docDOM->importNode($newCursorNode, true);
                    $node->parentNode->insertBefore($cursorNode, $node);
                    $node->parentNode->removeChild($node);
                }
            }
        }

        if (self::$trackingEnabled == true) {
            $tracking = new Tracking();
            $wordML = $tracking->addTrackingInsFirstR($wordML);
        }
        
        $stringDoc = $docDOM->saveXML();
        $stringDoc = str_replace('<cursorWordML/>', $wordML, $stringDoc);

        return $stringDoc;
    }

    /**
     * Replaces an array of variables by their values
     *
     * @access public 
     * @param array $variables
     *  keys: variable names
     *  values: text we want to insert
     * @param string $loadContent
     * @param array $options
     * @return SimpleXML Object
     */
    private function variable2Text($variables, $loadContent, $options)
    {
        if (isset($options['firstMatch'])) {
            $firstMatch = $options['firstMatch'];
        } else {
            $firstMatch = false;
        }
        if (!self::$_preprocessed) {
            $loadContent = $this->repairVariables($variables, $loadContent);
        }
        $optionEntityLoader = libxml_disable_entity_loader(true);
        $dom = simplexml_load_string($loadContent);
        libxml_disable_entity_loader($optionEntityLoader);
        $dom->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        foreach ($variables as $var => $val) {
            $search = self::$_templateSymbol . $var . self::$_templateSymbol;
            $query = '//w:t[text()[contains(., "' . $search . '")]]';
            if ($firstMatch) {
                $query = '(' . $query . ')[1]';
            }
            $foundNodes = $dom->xpath($query);
            foreach ($foundNodes as $node) {
                $strNode = (string) $node;
                if (isset($options['parseLineBreaks']) && $options['parseLineBreaks']) {
                    $domNode = dom_import_simplexml($node);
                    //parse $val for \n\r, \r\n, \n or \r and carriage returns
                    $val = str_replace(array('\n\r', '\r\n', '\n', '\r', "\n\r", "\r\n", "\n", "\r"), '<linebreak/>', $val);
                    $strNode = str_replace($search, $val, $strNode);
                    $runs = explode('<linebreak/>', $strNode);
                    $preserveWS = false;
                    $preserveWhiteSpace = $domNode->getAttribute('xml:space');
                    if ($preserveWhiteSpace == 'preserve') {
                        $preserveWS = true;
                    }
                    $numberOfRuns = count($runs);
                    $counter = 0;
                    foreach ($runs as $run) {
                        $counter++;
                        $newT = $domNode->ownerDocument->createElementNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 't', htmlspecialchars($run));
                        if ($preserveWS) {
                            $newT->setAttribute('xml:space', 'preserve');
                        }
                        $domNode->parentNode->insertBefore($newT, $domNode);
                        if ($counter < $numberOfRuns) {
                            $br = $domNode->ownerDocument->createElementNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'br');
                            $domNode->parentNode->insertBefore($br, $domNode);
                        }
                    }
                    $domNode->parentNode->removeChild($domNode);
                } else {
                    $strNode = str_replace($search, $val, $strNode);
                    $node[0] = $strNode;
                }
            }
        }

        return $dom;
    }

    /**
     * Does the actual parsing of the content for the replacement by a Word Fragment
     *
     * @access private
     * @param array $variables
     * @param string $type
     * @param string $loadContent
     * @param bool $firstMatch
     */
    private function variable4WordFragment($variables, $type, $loadContent, $firstMatch)
    {
        if (!self::$_preprocessed) {
            $loadContent = $this->repairVariables($variables, $loadContent);
        }

        foreach ($variables as $var => $val) {
            $loadContent = $this->singleVariable4WordFragment($var, $val, $type, $loadContent, $firstMatch);
        }

        return $loadContent;
    }

    /**
     * Does the actual parsing of the content for the replacement by a Word Fragment in headers and footers
     *
     * @access private
     * @param array $variables
     * @param string $type
     * @param string $headerFooterName
     * @param string $headerFooterContent
     * @param bool $firstMatch
     */
    private function variableHeaderFooterByWordFragments($variables, $type, $headerFooterName , $headerFooterContent, $firstMatch)
    {
        if (!self::$_preprocessed) {
            $headerFooterContent = $this->repairVariables($variables, $headerFooterContent);
        }

        foreach ($variables as $var => $wf) {
            $domHeaderFooter = $this->singleVariable4WordFragment($var, $wf, $type, $headerFooterContent, $firstMatch);

            $nodes = $wf->_wordRelsDocumentRelsT->getElementsBytagName('Relationship');
            $relName = 'word/_rels/' . $headerFooterName . '.rels';
            $stringRels = $this->getFromZip($relName);
            if (!$stringRels) {
                $stringRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>';
            }
            foreach ($nodes as $node) {
                $nodeType = $node->getAttribute('Type'); 
                if ($nodeType == 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image' || $nodeType == 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink' || $nodeType == 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/chart') {
                    // only add the relation if the rId doesn't exist
                   if (!strstr($stringRels, $wf->_wordRelsDocumentRelsT->saveXML($node))) {
                       $xmlRels = new \DomDocument();
                       $optionEntityLoader = libxml_disable_entity_loader(true);
                       $xmlRels->loadXML($stringRels);
                       libxml_disable_entity_loader($optionEntityLoader);
                       $newRelNode = $xmlRels->createElement('relationship');
                       $xmlRels->getElementsBytagName('Relationships')->item(0)->appendChild($newRelNode);
                       $stringRels = $xmlRels->saveXML();
                       $stringRels = str_replace('<relationship/>', $wf->_wordRelsDocumentRelsT->saveXML($node), $stringRels);
                   }
                }
            }
            $this->saveToZip($stringRels, 'word/_rels/' . $headerFooterName . '.rels');
        }

        return $domHeaderFooter;
    }

}
