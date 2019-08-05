<?php

namespace Phpdocx\Utilities;

/**
 * Return information of a DOCX file
 *
 * @category   Phpdocx
 * @package    utilities
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @version    2019.01.31
 * @link       https://www.phpdocx.com
 */
class Indexer
{
    /**
     * @var array Stores the file internal structure like body, header and footer
     */
    private $documentStructure;

    /**
     * @var DOCXStructure
     */
    private $documentZip;

    /**
     * Class constructor
     *
     * @param mixed $source File path or DOCXStructure
     */
    public function __construct($source)
    {
        if (file_exists(dirname(__FILE__) . '/DOCXStructureTemplate.php') && $source instanceof DOCXStructure) {
            $this->documentZip = $source;
        } else {
            $this->documentZip = new DOCXStructure();
            $this->documentZip->parseDocx($source);
        }

        // init the document structure array as empty
        $this->documentStructure = array(
            'body' => array(
                'charts' => array(),
                'images' => array(),
                'links' => array(),
                'text' => array(),
            ),
            'comments' => array(
                'images' => array(),
                'links' => array(),
                'text' => array(),
            ),
            'endnotes' => array(
                'images' => array(),
                'links' => array(),
                'text' => array(),
            ),
            'fonts' => array(),
            'footers' => array(
                'images' => array(),
                'links' => array(),
                'text' => array(),
            ),
            'footnotes' => array(
                'images' => array(),
                'links' => array(),
                'text' => array(),
            ),
            'headers' => array(
                'images' => array(),
                'links' => array(),
                'text' => array(),
            ),
            'people' => array(),
            'properties' => array(
                'core' => array(),
                'custom' => array(),
            ),
            'sections' => array(),
            'styles' => array(
                'docDefaults' => array(),
                'style' => array(),
                'numbering' => array(),
            ),
        );

        // parse the document
        $this->parse($source);
    }

    /**
     * Return a file as array or JSON
     *
     * @param string $type Output type: 'array' (default), 'json'
     * @return mixed $output
     * @throws Exception If the output type format not supported
     */
    public function getOutput($output = 'array')
    {
        // if the chosen output type is not supported throw an exception
        if (!in_array($output, array('array', 'json'))) {
            throw new \Exception('The output "' . $output . '" is not supported');
        }

        // output the document after index
        return $this->output($output);
    }

    /**
     * Extract chart contents from a XML string
     *
     * @param string $xml XML string
     * @param string $target Content target
     */
    protected function extractCharts($xml, $target)
    {
        // check if the XML is not empty
        if (!empty($xml)) {
            // load XML content
            $contentDOM = new \DOMDocument();
            $contentDOM->loadXML($xml);

            // do a xpath query getting only image tags
            $contentXpath = new \DOMXPath($contentDOM);
            $contentXpath->registerNamespace('rel', 'http://schemas.openxmlformats.org/package/2006/relationships');
            $chartEntries = $contentXpath->query('//rel:Relationship[@Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/chart"]');

            // iterate charts
            foreach ($chartEntries as $chartEntry) {
                $chartContent = $this->documentZip->getContent('word/' . $chartEntry->getAttribute('Target'));

                // load chart XML content
                $chartDOM = new \DOMDocument();
                $chartDOM->loadXML($chartContent);

                $contentChartXpath = new \DOMXPath($chartDOM);
                $contentChartXpath->registerNamespace('c', 'http://schemas.openxmlformats.org/drawingml/2006/chart');

                // get chart title
                $chartTitleEntries = $contentChartXpath->query('//c:chart/c:title');
                $chartTitle = '';
                foreach ($chartTitleEntries as $chartTitleEntry) {
                    $chartTitle = $chartTitleEntry->nodeValue;
                }

                // get chart sers
                $chartSerEntries = $contentChartXpath->query('//c:chart//c:ser');
                $data = array();
                foreach ($chartSerEntries as $chartSerEntry) {
                    // get chart ser texts
                    $chartTextSerEntries = $contentChartXpath->query('./c:tx//c:v', $chartSerEntry);

                    $chartTextSer = '';
                    if ($chartTextSerEntries->length > 0) {
                        $chartTextSer = $chartTextSerEntries->item(0)->nodeValue;
                    }

                    // get chart cats
                    $chartCats = array();
                    $chartCatsSerEntries = $contentChartXpath->query('./c:cat//c:v', $chartSerEntry);
                    if ($chartCatsSerEntries->length > 0) {
                        foreach ($chartCatsSerEntries as $chartCatsSerEntry) {
                            $chartCats[] = $chartCatsSerEntry->nodeValue;
                        }
                    }

                    // get chart vals
                    $chartVals = array();
                    $chartValsSerEntries = $contentChartXpath->query('./c:val//c:v', $chartSerEntry);
                    if ($chartValsSerEntries->length > 0) {
                        foreach ($chartValsSerEntries as $chartValsSerEntry) {
                            $chartVals[] = $chartValsSerEntry->nodeValue;
                        }
                    }
                    
                    $data[] = array(
                        'text' => $chartTextSer,
                        'cats' => $chartCats,
                        'vals' => $chartVals,
                    );
                }

                $this->documentStructure[$target]['charts'][] = array(
                    'title' => $chartTitle,
                    'data' => $data,
                );
            }
        }
    }

    /**
     * Extract text contents from a XML string
     *
     * @param string $xml XML string
     * @return string Text content
     */
    protected function extractFonts($xml)
    {
        // load XML content
        $contentDOM = new \DOMDocument();
        $contentDOM->loadXML($xml);

        // do a global xpath query getting only font tags
        $contentXpath = new \DOMXPath($contentDOM);
        $contentXpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $fontEntries = $contentXpath->query('//w:font');

        // iterate fonts
        foreach ($fontEntries as $fontEntry) {
            $this->documentStructure['fonts'][] = $fontEntry->getAttribute('w:name');
        }
    }

    /**
     * Extract image contents from a XML string
     *
     * @param string $xml XML string
     * @param string $target Content target
     * @param string $content Content XML to get sizes
     */
    protected function extractImages($xml, $target, $contentTarget)
    {
        // check if the XML is not empty
        if (!empty($xml)) {
            // load XML content
            $contentDOM = new \DOMDocument();
            $contentDOM->loadXML($xml);

            // do a xpath query getting only image tags
            $contentXpath = new \DOMXPath($contentDOM);
            $contentXpath->registerNamespace('rel', 'http://schemas.openxmlformats.org/package/2006/relationships');
            $imageEntries = $contentXpath->query('//rel:Relationship[@Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image"]');

            // load XML content
            $contentTargetDOM = new \DOMDocument();
            $contentTargetDOM->loadXML($contentTarget);

            // do a xpath query getting only image tags
            $contentTargetXpath = new \DOMXPath($contentTargetDOM);
            $contentTargetXpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $contentTargetXpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
            $contentTargetXpath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $contentTargetXpath->registerNamespace('wp', 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing');

            // init size values
            $heightImage = '';
            $widthImage = '';

            // iterate images
            foreach ($imageEntries as $imageEntry) {
                $imageString = $this->documentZip->getContent('word/' . $imageEntry->getAttribute('Target'));

                // get the size of the image
                $drawingEntries = $contentTargetXpath->query('//w:drawing/*[.//@r:embed="'.$imageEntry->getAttribute('Id').'"]');
                $drawingChildNodes = $drawingEntries->item(0)->childNodes;
                foreach ($drawingChildNodes as $drawingChildNode) {
                    if ($drawingChildNode->tagName == 'wp:extent') {
                        if ($drawingChildNode->hasAttribute('cx')) {
                            $widthImage = $drawingChildNode->getAttribute('cx');
                        }
                        if ($drawingChildNode->hasAttribute('cy')) {
                            $heightImage = $drawingChildNode->getAttribute('cy');
                        }
                    }
                }

                $this->documentStructure[$target]['images'][] = array(
                    'content' => $imageString,
                    'path' => $imageEntry->getAttribute('Target'),
                    'height_word_emus' => $heightImage,
                    'width_word_emus' => $widthImage,
                    'height_word_inches' => $heightImage/914400,
                    'width_word_inches' => $widthImage/914400,
                    'height_word_cms' => $heightImage/360000,
                    'width_word_cms' => $widthImage/360000,
                );
            }
        }
    }

    /**
     * Extract link contents from a XML string
     *
     * @param string $xml XML string
     * @param string $target Content target
     */
    protected function extractLinks($xml, $target)
    {
        // load XML content
        $contentDOM = new \DOMDocument();
        $contentDOM->loadXML($xml);

        // do a global xpath query getting only text tags
        $contentXpath = new \DOMXPath($contentDOM);
        $contentXpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $linkEntries = $contentXpath->query('//w:instrText');

        // iterate link contents and extract the URL
        foreach ($linkEntries as $linkEntry) {
            // if empty text avoid adding the content
            if ($linkEntry->textContent == ' ') {
                continue;
            }

            // remove HYPERLINK and " strings
            $content = str_replace('HYPERLINK', '', $linkEntry->textContent);
            $content = str_replace(array('&quot;', '"'), '', $content);
            $this->documentStructure[$target]['links'][] = trim($content);
        }
    }

    /**
     * Extract numbering from a XML string
     *
     * @param string $xml XML string
     */
    protected function extractNumbering($xml)
    {
        // load XML content
        $contentDOM = new \DOMDocument();
        $contentDOM->loadXML($xml);

        // do a global xpath query getting num tags
        $contentXpath = new \DOMXPath($contentDOM);
        $contentXpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $numEntries = $contentXpath->query('//w:num');

        foreach ($numEntries as $numEntry) {
            // get the numbering styles for the w:num tag
            
            // number ID used as array key
            $numId = $numEntry->getAttribute('w:numId');

            // get abstractNumId
            $abstractNumId = $numEntry->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'abstractNumId')->item(0)->getAttribute('w:val');

            // get w:abstractNum from the ID
            $abstractNum = $contentXpath->query('//w:abstractNum[@w:abstractNumId="'.$abstractNumId.'"]');

            if ($abstractNum->length > 0) {
                $parserXML = xml_parser_create();
                xml_parser_set_option($parserXML, XML_OPTION_CASE_FOLDING, 0);
                xml_parse_into_struct($parserXML, $abstractNum->item(0)->ownerDocument->saveXML($abstractNum->item(0)), $values, $indexes);
                xml_parser_free($parserXML);
                    
                $this->documentStructure['styles']['numbering'][$abstractNumId] = $values;
            }
        }
    }

    /**
     * Extract people from a XML string
     *
     * @param string $xml XML string
     */
    protected function extractPeople($xml)
    {
        // load XML content
        $contentDOM = new \DOMDocument();
        $contentDOM->loadXML($xml);

        // do a global xpath query getting only people tags
        $contentXpath = new \DOMXPath($contentDOM);
        $contentXpath->registerNamespace('w15', 'http://schemas.microsoft.com/office/word/2012/wordml');
        $peopleEntries = $contentXpath->query('//w15:person');

        // iterate fonts
        foreach ($peopleEntries as $peopleEntry) {
            $author = $peopleEntry->getAttribute('w15:author');
            $presenceInfoTag = $peopleEntry->getElementsByTagNameNS('http://schemas.microsoft.com/office/word/2012/wordml', 'presenceInfo');

            $userId = '';
            $providerId = '';
            if ($presenceInfoTag->length > 0) {
                $userId = $presenceInfoTag->item(0)->getAttribute('w15:userId');
                $providerId = $presenceInfoTag->item(0)->getAttribute('w15:providerId');
            }

            $this->documentStructure['people'][] = array('author' => $author, 'userId' => $userId, 'providerId' => $providerId);
        }
    }

    /**
     * Extract document properties from a XML string
     *
     * @param string $xml XML string
     * @param string $target Properties target: core, custom
     */
    protected function extractProperties($xml, $target)
    {
        // load XML content
        $contentDOM = new \DOMDocument();
        $contentDOM->loadXML($xml);

        if ($target == 'core') {
            // do a global xpath query getting only text tags
            $contentXpath = new \DOMXPath($contentDOM);
            $contentXpath->registerNamespace('cp', 'http://schemas.openxmlformats.org/package/2006/metadata/core-properties');
            $propertiesEntries = $contentXpath->query('//cp:coreProperties');

            if ($propertiesEntries->item(0)->childNodes->length > 0) {
                foreach ($propertiesEntries->item(0)->childNodes as $propertyEntry) {
                    // if empty text avoid adding the content
                    if ($propertyEntry->textContent == '') {
                        continue;
                    }

                    // get the name of the property
                    $propertyEntryFullName = explode(':', $propertyEntry->tagName);
                    $nameProperty = $propertyEntryFullName[1];

                    $this->documentStructure['properties']['core'][$nameProperty] = trim($propertyEntry->textContent);
                }
            }
        } else if ($target == 'custom') {
            // do a global xpath query getting only property tags
            $contentXpath = new \DOMXPath($contentDOM);
            $contentXpath->registerNamespace('ns', 'http://schemas.openxmlformats.org/officeDocument/2006/custom-properties');
            $propertiesEntries = $contentXpath->query('//ns:Properties//ns:property');

            if ($propertiesEntries->length > 0) {
                foreach ($propertiesEntries as $propertyEntry) {
                    // if empty text avoid adding the content
                    if ($propertyEntry->textContent == '') {
                        continue;
                    }

                    // get the name of the property
                    $nameProperty = $propertyEntry->getAttribute('name');

                    $this->documentStructure['properties']['custom'][$nameProperty] = trim($propertyEntry->textContent);
                }
            }
        }
    }

    /**
     * Extract section contents from a XML string
     *
     * @param string $xml XML string
     * @param string $target Content target
     */
    protected function extractSections($xml, $target)
    {
        // load XML content
        $contentDOM = new \DOMDocument();
        $contentDOM->loadXML($xml);

        // do a global xpath query getting only section tags
        $contentXpath = new \DOMXPath($contentDOM);
        $contentXpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $sectionEntries = $contentXpath->query('//w:sectPr');

        // iterate section contents and extract the information
        foreach ($sectionEntries as $sectionEntry) {
            // w:cols columns
            $wCols = array();
            $wColsNodes = $sectionEntry->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'cols');
            if ($wColsNodes->length > 0) {
                if ($wColsNodes->item(0)->hasAttribute('w:num')) {
                    $wCols['number'] = $wColsNodes->item(0)->getAttribute('w:num');
                }
                if ($wColsNodes->item(0)->hasAttribute('w:space')) {
                    $wCols['space'] = $wColsNodes->item(0)->getAttribute('w:space');
                }

                // check if there're col tags
                $wColNodes = $wColsNodes->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'col');
                if ($wColNodes->length > 0) {
                    $wCols['cols'] = array();
                    foreach ($wColNodes as $wColNode) {
                        $colInfo = array();
                        if ($wColNode->hasAttribute('w:space')) {
                            $colInfo['space'] = $wColNode->getAttribute('w:space');
                        }
                        if ($wColNode->hasAttribute('w:w')) {
                            $colInfo['width'] = $wColNode->getAttribute('w:w');
                        }

                        $wCols['cols'][] = $colInfo;
                    }
                }
            }


            // w:pgMar margins
            $wPgMar = array();
            $wPgMarNodes = $sectionEntry->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'pgMar');
            if ($wPgMarNodes->length > 0) {
                if ($wPgMarNodes->item(0)->hasAttribute('w:bottom')) {
                    $wPgMar['bottom'] = $wPgMarNodes->item(0)->getAttribute('w:bottom');
                }
                if ($wPgMarNodes->item(0)->hasAttribute('w:footer')) {
                    $wPgMar['footer'] = $wPgMarNodes->item(0)->getAttribute('w:footer');
                }
                if ($wPgMarNodes->item(0)->hasAttribute('w:gutter')) {
                    $wPgMar['gutter'] = $wPgMarNodes->item(0)->getAttribute('w:gutter');
                }
                if ($wPgMarNodes->item(0)->hasAttribute('w:header')) {
                    $wPgMar['header'] = $wPgMarNodes->item(0)->getAttribute('w:header');
                }
                if ($wPgMarNodes->item(0)->hasAttribute('w:left')) {
                    $wPgMar['left'] = $wPgMarNodes->item(0)->getAttribute('w:left');
                }
                if ($wPgMarNodes->item(0)->hasAttribute('w:right')) {
                    $wPgMar['right'] = $wPgMarNodes->item(0)->getAttribute('w:right');
                }
                if ($wPgMarNodes->item(0)->hasAttribute('w:top')) {
                    $wPgMar['top'] = $wPgMarNodes->item(0)->getAttribute('w:top');
                }
            }

            // w:pgSz sizes and orientation
            $wPgSz = array();
            $wPgSzNodes = $sectionEntry->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'pgSz');
            if ($wPgSzNodes->length > 0) {
                if ($wPgSzNodes->item(0)->hasAttribute('w:h')) {
                    $wPgSz['height'] = $wPgSzNodes->item(0)->getAttribute('w:h');
                }
                if ($wPgSzNodes->item(0)->hasAttribute('w:orient')) {
                    $wPgSz['orient'] = $wPgSzNodes->item(0)->getAttribute('w:orient');
                }
                if ($wPgSzNodes->item(0)->hasAttribute('w:w')) {
                    $wPgSz['width'] = $wPgSzNodes->item(0)->getAttribute('w:w');
                }
            }

            // w:headerReference
            $wHeaderReferences = array();
            $wHeaderReferencesNodes = $sectionEntry->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'headerReference');
            if ($wHeaderReferencesNodes->length > 0) {
                foreach ($wHeaderReferencesNodes as $wHeaderReferencesNode) {
                    $wHeaderReferences[] = array(
                        'id' => $wHeaderReferencesNode->getAttribute('r:id'),
                        'type' => $wHeaderReferencesNode->getAttribute('w:type'),
                    );
                }
            }

            // w:footerReference
            $wFooterReferences = array();
            $wFooterReferencesNodes = $sectionEntry->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'footerReference');
            if ($wFooterReferencesNodes->length > 0) {
                foreach ($wFooterReferencesNodes as $wFooterReferencesNode) {
                    $wFooterReferences[] = array(
                        'id' => $wFooterReferencesNode->getAttribute('r:id'),
                        'type' => $wFooterReferencesNode->getAttribute('w:type'),
                    );
                }
            }
            
            $this->documentStructure['sections'][] = array(
                'columns' => $wCols,
                'headers' => $wHeaderReferences,
                'footers' => $wFooterReferences,
                'margins' => $wPgMar,
                'sizes' => $wPgSz,
            );
        }
    }

    /**
     * Extract styles from a XML string
     *
     * @param string $xml XML string
     */
    protected function extractStyles($xml)
    {
        // load XML content
        $contentDOM = new \DOMDocument();
        $contentDOM->loadXML($xml);

        // do a global xpath query getting only docDefaults tags
        $contentXpath = new \DOMXPath($contentDOM);
        $contentXpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $docDefaultEntry = $contentXpath->query('//w:docDefaults');

        if ($docDefaultEntry->length > 0) {
            $rPrDefaultTag = $docDefaultEntry->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'rPrDefault');

            if ($rPrDefaultTag->length > 0) {
                $parserXML = xml_parser_create();
                xml_parser_set_option($parserXML, XML_OPTION_CASE_FOLDING, 0);
                xml_parse_into_struct($parserXML, $rPrDefaultTag->item(0)->firstChild->ownerDocument->saveXML($rPrDefaultTag->item(0)->firstChild), $values, $indexes);
                xml_parser_free($parserXML);

                // remove open and last tag to avoid adding w:rPr as style tag
                array_shift($values);
                array_pop($values);

                $this->documentStructure['styles']['docDefaults']['rPrDefault'] = $values;
            }

            $pPrDefaultTag = $docDefaultEntry->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'pPrDefault');

            if ($pPrDefaultTag->length > 0) {
                $parserXML = xml_parser_create();
                xml_parser_set_option($parserXML, XML_OPTION_CASE_FOLDING, 0);
                xml_parse_into_struct($parserXML, $pPrDefaultTag->item(0)->firstChild->ownerDocument->saveXML($pPrDefaultTag->item(0)->firstChild), $values, $indexes);
                xml_parser_free($parserXML);

                // remove open and last tag to avoid adding w:pPr as style tag
                array_shift($values);
                array_pop($values);
                
                $this->documentStructure['styles']['docDefaults']['pPrDefault'] = $values;
            }
        }

        // do a global xpath query getting style tags
        $contentXpath = new \DOMXPath($contentDOM);
        $contentXpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $stylesEntries = $contentXpath->query('//w:style');

        foreach ($stylesEntries as $stylesEntry) {
            $parserXML = xml_parser_create();
            xml_parser_set_option($parserXML, XML_OPTION_CASE_FOLDING, 0);
            xml_parse_into_struct($parserXML, $stylesEntry->ownerDocument->saveXML($stylesEntry), $values, $indexes);
            xml_parser_free($parserXML);

            $this->documentStructure['styles']['style'][] = $values;
        }
    }

    /**
     * Extract text contents from a XML string
     *
     * @param string $xml XML string
     * @return string Text content
     */
    protected function extractTexts($xml)
    {
        // load XML content
        $contentDOM = new \DOMDocument();
        $contentDOM->loadXML($xml);

        // do a global xpath query getting only text tags
        $contentXpath = new \DOMXPath($contentDOM);
        $contentXpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $textEntries = $contentXpath->query('//w:t');

        // iterate text content and extract text strings. Add a blank space to separate each string
        $content = '';
        foreach ($textEntries as $textEntry) {
            // if empty text avoid adding the content
            if ($textEntry->textContent == ' ') {
                continue;
            }
            $content .= ' ' . $textEntry->textContent;
        }

        return trim($content);
    }

    /**
     * Return a file as array or JSON
     *
     * @param string $type Output type: 'array' (default), 'json'
     * @return mixed $output
     */
    protected function output($type = 'array')
    {
        // array as default
        $output = $this->documentStructure;

        // export as the choosen type
        if ($type == 'json') {
            $output = json_encode($output);
        }

        return $output;
    }

    /**
     * Parse a DOCX file
     *
     * @param DOCXStructure $source
     */
    private function parse($source)
    {
        // parse the Content_Types
        $contentTypesContent = $this->documentZip->getContent('[Content_Types].xml');
        $contentTypesXml = simplexml_load_string($contentTypesContent);

        // get the rels extension, rels as default
        $contentTypesDom = dom_import_simplexml($contentTypesXml);
        $contentTypesXpath = new \DOMXPath($contentTypesDom->ownerDocument);
        $contentTypesXpath->registerNamespace('rel', 'http://schemas.openxmlformats.org/package/2006/content-types');
        $relsEntries = $contentTypesXpath->query('//rel:Default[@ContentType="application/vnd.openxmlformats-package.relationships+xml"]');
        $relsExtension = 'rels';
        if (isset($relsEntries[0])) {
            $relsExtension = $relsEntries[0]->getAttribute('Extension');
        }

        // iterate over the Content_Types and add the header, footer and body contents
        foreach ($contentTypesXml->Override as $override) {
            foreach ($override->attributes() as $attribute => $value) {
                // get the file content
                $content = $this->documentZip->getContent(substr($override->attributes()->PartName, 1));

                // before adding a content remove the first character to get the right file path
                // removing the first slash of each path
                if ($value == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml') {
                    // body content

                    // extract the text content
                    $textContent = $this->extractTexts($content);
                    
                    $this->documentStructure['body']['text'][] .= $textContent;

                    // extract links
                    $this->extractLinks($content, 'body');

                    // extract images from the same file name plus rels extension
                    $relsPath = str_replace('word/', 'word/_rels/', substr($override->attributes()->PartName, 1)) . '.' . $relsExtension;
                    $contentRels = $this->documentZip->getContent($relsPath);

                    // extract images
                    $this->extractImages($contentRels, 'body', $content);

                    // extract chars
                    $this->extractCharts($contentRels, 'body');

                    // extract sections
                    $this->extractSections($content, 'body');
                } else if ($value == 'application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml') {
                    // headers content

                    // extract the text content
                    $textContent = $this->extractTexts($content);

                    $this->documentStructure['headers']['text'][] = $textContent;

                    // extract links
                    $this->extractLinks($content, 'headers');

                    // extract images from the same file name plus rels extension
                    $relsPath = str_replace('word/', 'word/_rels/', substr($override->attributes()->PartName, 1)) . '.' . $relsExtension;
                    $contentRels = $this->documentZip->getContent($relsPath);

                    // extract images
                    $this->extractImages($contentRels, 'headers', $content);
                } else if ($value == 'application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml') {
                    // footers content

                    // extract the text content
                    $textContent = $this->extractTexts($content);
                    
                    $this->documentStructure['footers']['text'][] .= $textContent;

                    // extract links
                    $this->extractLinks($content, 'footers');

                    // extract images from the same file name plus rels extension
                    $relsPath = str_replace('word/', 'word/_rels/', substr($override->attributes()->PartName, 1)) . '.' . $relsExtension;
                    $contentRels = $this->documentZip->getContent($relsPath);

                    // extract images
                    $this->extractImages($contentRels, 'footers', $content);
                } else if ($value == 'application/vnd.openxmlformats-officedocument.wordprocessingml.comments+xml') {
                    // comments content

                    // extract the text content
                    $textContent = $this->extractTexts($content);
                    
                    $this->documentStructure['comments']['text'][] .= $textContent;

                    // extract links
                    $this->extractLinks($content, 'comments');

                    // extract images from the same file name plus rels extension
                    $relsPath = str_replace('word/', 'word/_rels/', substr($override->attributes()->PartName, 1)) . '.' . $relsExtension;
                    $contentRels = $this->documentZip->getContent($relsPath);

                    // extract images
                    $this->extractImages($contentRels, 'comments', $content);
                } else if ($value == 'application/vnd.openxmlformats-officedocument.wordprocessingml.footnotes+xml') {
                    // footnotes content

                    // extract the text content
                    $textContent = $this->extractTexts($content);
                    
                    $this->documentStructure['footnotes']['text'][] .= $textContent;

                    // extract links
                    $this->extractLinks($content, 'footnotes');

                    // extract images from the same file name plus rels extension
                    $relsPath = str_replace('word/', 'word/_rels/', substr($override->attributes()->PartName, 1)) . '.' . $relsExtension;
                    $contentRels = $this->documentZip->getContent($relsPath);

                    // extract images
                    $this->extractImages($contentRels, 'footnotes', $content);
                } else if ($value == 'application/vnd.openxmlformats-officedocument.wordprocessingml.endnotes+xml') {
                    // endnotes content

                    // extract the text content
                    $textContent = $this->extractTexts($content);
                    
                    $this->documentStructure['endnotes']['text'][] .= $textContent;

                    // extract links
                    $this->extractLinks($content,'endnotes');

                    // extract images from the same file name plus rels extension
                    $relsPath = str_replace('word/', 'word/_rels/', substr($override->attributes()->PartName, 1)) . '.' . $relsExtension;
                    $contentRels = $this->documentZip->getContent($relsPath);

                    // extract images
                    $this->extractImages($contentRels, 'endnotes',$content);
                } else if ($value == 'application/vnd.openxmlformats-package.core-properties+xml') {
                    // core properties content

                    // extract the properties
                    $this->extractProperties($content, 'core');
                } else if ($value == 'application/vnd.openxmlformats-officedocument.custom-properties+xml') {
                    // custom properties content

                    // extract the properties
                    $this->extractProperties($content, 'custom');
                } else if ($value == 'application/vnd.openxmlformats-officedocument.wordprocessingml.fontTable+xml') {
                    // fonts content

                    // extract the properties
                    $this->extractFonts($content);
                } else if ($value == 'application/vnd.openxmlformats-officedocument.wordprocessingml.people+xml') {
                    // people content

                    // extract the people
                    $this->extractPeople($content);
                }  else if ($value == 'application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml') {
                    // styles content

                    // extract the styles
                    $this->extractStyles($content);
                } else if ($value == 'application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml') {
                    // numbering content

                    // extract the numbering
                    $this->extractNumbering($content);
                }
            }
        }
    }
}