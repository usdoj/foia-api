<?php
namespace Phpdocx\Transform;

use Phpdocx\Utilities\DOCXStructure;

/**
 * Transform DOCX to HTML using native PHP classes
 *
 * @category   Phpdocx
 * @package    trasform
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @version    2019.01.31
 * @link       https://www.phpdocx.com
 */

require_once dirname(__FILE__) . '/../Create/CreateDocx.php';

class TransformDocAdvHTML
{
    /**
     *
     * @access protected
     * @var string
     */
    protected $commentsContent = null;

    /**
     *
     * @access protected
     * @var array
     */
    protected $commentsIndex = array();

    /**
     *
     * @access protected
     * @var string
     */
    protected $complexField = null;

    /**
     *
     * @access protected
     * @var array
     */
    protected $css;

    /**
     *
     * @access protected
     * @var string
     */
    protected $currentSectionClassName;

    /**
     *
     * @access protected
     * @var DOMDocument
     */
    protected $documentXmlRelsDOM;

    /**
     *
     * @access protected
     * @var DOCXStructure
     */
    protected $docxStructure;

    /**
     *
     * @access protected
     * @var string
     */
    protected $endnotesContent = null;

    /**
     *
     * @access protected
     * @var array
     */
    protected $endnotesIndex = array();

    /**
     *
     * @access protected
     * @var array
     */
    protected $footersContent = array();

    /**
     *
     * @access protected
     * @var string
     */
    protected $footnotesContent = null;

    /**
     *
     * @access protected
     * @var array
     */
    protected $footnotesIndex = array();

    /**
     *
     * @access protected
     * @var string
     */
    protected $headersContent = array();

    /**
     *
     * @access protected
     * @var string
     */
    protected $html;

    /**
     *
     * @access protected
     * @var TransformDocAdvHTMLPlugin
     */
    protected $htmlPlugin;

    /**
     *
     * @access protected
     * @var string
     */
    protected $javascript;

    /**
     *
     * @access protected
     * @var array
     */
    protected $listStartValues = array();

    /**
     *
     * @access protected
     * @var array
     */
    protected $sectionsStructure = array();

    /**
     *
     * @access protected
     * @var DOMDocument
     */
    protected $stylesDocxDOM;

    /**
     *
     * @access protected
     * @var string
     */
    protected $target = 'document';

    /**
     *
     * @access protected
     * @var string
     */
    protected $targetExtra = null;

    /**
     * Constructor
     *
     * @access public
     * @param mixed (DOCXStructure or file path). DOCX to be transformed
     */
    public function __construct($docxDocument)
    {
        if ($docxDocument instanceof DOCXStructure) {
            $this->docxStructure = $docxDocument;
        } else {
            $this->docxStructure = new DOCXStructure();
            $this->docxStructure->parseDocx($docxDocument);
        }
    }

    /**
     * Transform the DOCX content
     *
     * @param TransformDocAdvHTMLPlugin $htmlPlugin Plugin to be used to transform the contents
     * @param array $options
     *  Values:
     *    'javaScriptAtTop' => default as false. If true add JS in the head tag.
     *    'returnHTMLStructure' => default as false. If true return each element of the HTML using an array: comments, document, footnotes, endnotes, headers, footers, metas.
     * @return string
     */
    public function transform(TransformDocAdvHTMLPlugin $htmlPlugin, $options = array())
    {
        $this->htmlPlugin = $htmlPlugin;

        $stylesDocxFile = $this->docxStructure->getContent('word/styles.xml');
        $this->stylesDocxDOM = new \DOMDocument();
        $optionEntityLoader = libxml_disable_entity_loader(true);
        $this->stylesDocxDOM->loadXML($stylesDocxFile);
        libxml_disable_entity_loader($optionEntityLoader);

        $headersContents = $this->docxStructure->getContentByType('headers');
        $this->target = 'headers';
        foreach ($headersContents as $headerContent) {
            $this->targetExtra = str_replace('word/', '', $headerContent['name']);
            $xmlHeader = new \DOMDocument();
            $optionEntityLoader = libxml_disable_entity_loader(true);
            $xmlHeader->loadXML($headerContent['content']);
            libxml_disable_entity_loader($optionEntityLoader);
            $this->transformXml($xmlHeader);

            $this->headersContent[$headerContent['name']] = '<' . $this->htmlPlugin->getTag('header') . '>' . $this->html . '</' . $this->htmlPlugin->getTag('header') . '>';
            $this->html = '';
        }
        $this->html = '';
        

        $footersContents = $this->docxStructure->getContentByType('footers');
        $this->target = 'footers';
        foreach ($footersContents as $footerContent) {
            $this->targetExtra = str_replace('word/', '', $footerContent['name']);
            $xmlFooter = new \DOMDocument();
            $optionEntityLoader = libxml_disable_entity_loader(true);
            $xmlFooter->loadXML($footerContent['content']);
            libxml_disable_entity_loader($optionEntityLoader);
            $this->transformXml($xmlFooter);

            $this->footersContent[$footerContent['name']] = '<' . $this->htmlPlugin->getTag('footer') . '>' . $this->html . '</' . $this->htmlPlugin->getTag('footer') . '>';
            $this->html = '';
        }
        $this->html = '';

        //$cssContent = 'body {white-space: pre;}';
        $cssContent = '';

        // add default styles
        $cssContent .= $this->addDefaultStyles();

        // styles.xml
        $this->addStyles();

        $bodyContent = $this->docxStructure->getContent('word/document.xml');
        $xmlBody = new \DOMDocument();
        $optionEntityLoader = libxml_disable_entity_loader(true);
        $xmlBody->loadXML($bodyContent);
        libxml_disable_entity_loader($optionEntityLoader);

        $endnotesContent = $this->docxStructure->getContent('word/endnotes.xml');
        if ($endnotesContent) {
            $xmlEndnotes = new \DOMDocument();
            $optionEntityLoader = libxml_disable_entity_loader(true);
            $xmlEndnotes->loadXML($endnotesContent);
            libxml_disable_entity_loader($optionEntityLoader);
        }

        $footnotesContent = $this->docxStructure->getContent('word/footnotes.xml');
        if ($footnotesContent) {
            $xmlFootnotes = new \DOMDocument();
            $optionEntityLoader = libxml_disable_entity_loader(true);
            $xmlFootnotes->loadXML($footnotesContent);
            libxml_disable_entity_loader($optionEntityLoader);
        }

        $commentsContent = $this->docxStructure->getContent('word/comments.xml');
        if ($commentsContent) {
            $xmlCommentsnotes = new \DOMDocument();
            $optionEntityLoader = libxml_disable_entity_loader(true);
            $xmlCommentsnotes->loadXML($commentsContent);
            libxml_disable_entity_loader($optionEntityLoader);
        }

        $documentXmlRelsDocxFile = $this->docxStructure->getContent('word/_rels/document.xml.rels');
        $this->documentXmlRelsDOM = new \DOMDocument();
        $optionEntityLoader = libxml_disable_entity_loader(true);
        $this->documentXmlRelsDOM->loadXML($documentXmlRelsDocxFile);
        libxml_disable_entity_loader($optionEntityLoader);

        $metaValues = $this->getMetaValues();

        // there's always a section at least.
        // Keep the section class name to be used when a section tag is found when parsing the document.
        // Add a placeholder string to add header contents if exist
        $this->currentSectionClassName = $this->htmlPlugin->generateClassName();
        if ($this->htmlPlugin->getGenerateSectionTags()) {
            $this->html = '<' . $this->htmlPlugin->getTag('section') . ' class="' . $this->currentSectionClassName . ' ' . ($this->htmlPlugin->getExtraClass('section')==null?'':$this->htmlPlugin->getExtraClass('section')) . '">__HEADERCONTENTSECTION__';
        }

        $this->target = 'document';
        $this->transformXml($xmlBody);
        $documentContent = $this->html;
        $this->html = '';

        if ($endnotesContent) {
            $this->target = 'endnotes';
            $this->transformXml($xmlEndnotes);
            $endnotesContent = $this->html;
            $this->html = '';
        }

        if ($footnotesContent) {
            $this->target = 'footnotes';
            $this->transformXml($xmlFootnotes);
            $footnotesContent = $this->html;
            $this->html = '';
        }

        if ($commentsContent) {
            $this->target = 'comments';
            $this->transformXml($xmlCommentsnotes);
            $commentsContent = $this->html;
            $this->html = '';
        }

        // body background color
        $cssContent .= $this->addBodyStyles($xmlBody);

        foreach ($this->css as $key => $value) {
            if ($key == 'body') {
                $cssContent .= $key . '{' . $value . '}';
            } else {
                // avoid adding empty CSS
                if (!empty($value)) {
                    $cssContent .= '.' . $key . '{' . $value . '}';
                }
            }
        }

        // clean CSS
        $cssContent = str_replace('##', '#', $cssContent);

        // clean document content
        $documentContent = str_replace('</span>&nbsp</p>', '</span></p>', $documentContent);

        if (isset($options['returnHTMLStructure']) && $options['returnHTMLStructure'] == true) {
            $output = array(
                'comments' => $commentsContent,
                'css' => $cssContent,
                'document' => $documentContent,
                'endnotes' => $endnotesContent,
                'footnotes' => $footnotesContent,
                'headers' => $this->headersContent,
                'footers' => $this->footersContent,
                'javascript' => $this->javascript,
                'metas' => $metaValues,
            );
        } else {
            if (isset($options['javaScriptAtTop']) && $options['javaScriptAtTop'] == true) {
                $output = $this->htmlPlugin->getBaseHTML() . '<head>' . $this->htmlPlugin->getBaseMeta() . $metaValues . $this->htmlPlugin->getBaseCSS() . $this->htmlPlugin->getBaseJavaScript() . $this->javascript . '<style>' . $cssContent . '</style></head><body>' . $documentContent . $endnotesContent . $footnotesContent . $commentsContent . '</body></html>';
            } else {
                $output = $this->htmlPlugin->getBaseHTML() . '<head>' . $this->htmlPlugin->getBaseMeta() . $metaValues . $this->htmlPlugin->getBaseCSS() . '<style>' . $cssContent . '</style></head><body>' . $documentContent . $endnotesContent . $footnotesContent . $commentsContent . $this->htmlPlugin->getBaseJavaScript() . $this->javascript . '</body></html>';
            }
        }

        return $output;
    }

    /**
     * Iterate the contents and transform them
     *
     * @param 
     * @param array $options
     *  Values:
     *    'javaScriptAtTop' => default as false. If true add JS in the head tag.
     *    'returnHTMLStructure' => default as false. If true return each element of the HTML using an array: comments, document, footnotes, endnotes, headers, footers, metas.
     * @return string
     */
    public function transformXml($xml)
    {
        foreach ($xml->childNodes as $childNode) {
            $nodeClass = $this->htmlPlugin->generateClassName();
            $this->css[$nodeClass] = '';

            // open tag
            switch ($childNode->nodeName) {
                // block elements
                case 'w:p':
                    $this->transformW_P($childNode, $nodeClass);
                    break;
                case 'w:sectPr':
                    if ($this->htmlPlugin->getGenerateSectionTags()) {
                        $this->transformW_SECTPR($childNode, $nodeClass);
                    }
                    break;
                case 'w:sdt':
                    $this->transformW_SDT($childNode, $nodeClass);
                    break;
                case 'w:tbl':
                    $this->transformW_TBL($childNode, $nodeClass);
                    break;
                // inline elements
                case 'w:altChunk':
                    $this->transformW_ALTCHUNK($childNode, $nodeClass);
                    break;
                case 'w:drawing':
                    $this->transformW_DRAWING($childNode, $nodeClass);
                    break;
                case 'w:hyperlink':
                    $this->transformW_HYPERLINK($childNode, $nodeClass);
                    break;
                case 'm:oMath':
                    $this->transformM_OMATH($childNode, $nodeClass);
                    break;
                case 'w:r':
                    $this->transformW_R($childNode, $nodeClass);
                    break;
                case 'w:t':
                    $this->transformW_T($childNode, $nodeClass);
                    break;

                // complex fields
                case 'w:fldChar':
                    $this->transformW_FLDCHAR($childNode, $nodeClass);
                    break;
                case 'w:instrText':
                    $this->transformW_INSTRTEXT($childNode, $nodeClass);
                    break;

                // other elements
                case 'w:br':
                    $this->transformW_BR($childNode, $nodeClass);
                    break;
                case 'w:bookmarkStart':
                    $this->transformW_BOOKMARKSTART($childNode, $nodeClass);
                    break;
                case 'w:comment':
                    $this->transformW_COMMENT($childNode, $nodeClass);
                    break;
                case 'w:commentReference':
                    $this->transformW_COMMENTREFERENCE($childNode, $nodeClass);
                    break;
                case 'w:endnote':
                    $this->transformW_ENDNOTE($childNode, $nodeClass);
                    break;
                case 'w:endnoteReference':
                    $this->transformW_ENDNOTEREFERENCE($childNode, $nodeClass);
                    break;
                case 'w:footnote':
                    $this->transformW_FOOTNOTE($childNode, $nodeClass);
                    break;
                case 'w:footnoteReference':
                    $this->transformW_FOOTNOTEREFERENCE($childNode, $nodeClass);
                    break;
                case 'v:textbox':
                    $this->transformV_TEXTBOX($childNode, $nodeClass);
                    break;
                default:
                    $this->transformDEFAULT_TAG($childNode, $nodeClass);
                    break;
            }
        }
    }

    /**
     * Body styles
     *
     * @param DOMElement $node
     * @return string Styles
     */
    protected function addBodyStyles($node)
    {
        $styles = '';
        $backgroundColor = $node->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'background');
        if ($backgroundColor->length > 0) {
            // background color
            $styles .= 'body {background-color: #' . $this->htmlPlugin->transformColors($backgroundColor->item(0)->getAttribute('w:color')) . ';}';

            // background image
            $backgroundImageTag = $node->getElementsByTagNameNS('urn:schemas-microsoft-com:vml', 'background');
            if ($backgroundImageTag->length > 0) {
                $backgroundImage = $backgroundImageTag->item(0)->getElementsByTagNameNS('urn:schemas-microsoft-com:vml', 'fill');
                if ($backgroundImage->length > 0) {
                    $target = $this->getRelationshipContent($backgroundImage->item(0)->getAttribute('r:id'));
                    $imageString = $this->docxStructure->getContent('word/' . $target);

                    $fileInfo = pathinfo($target);
                    file_put_contents($this->htmlPlugin->getOutputFilesPath(). $fileInfo['basename'], $imageString);
                    $styles .= 'body {background-image: url("' . $this->htmlPlugin->getOutputFilesPath(). $fileInfo['basename'] . '");}';
                }
            }
        }

        return $styles;
    }

    /**
     * pPr styles
     *
     * @param DOMElement $node
     * @return string Styles
     */
    protected function addPprStyles($node)
    {
        if ($node) {
            $styles = '';
            $pprStyles = $node->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'pPr');
            if ($pprStyles->length > 0 && $pprStyles->item(0)->hasChildNodes()) {
                foreach ($pprStyles->item(0)->childNodes as $pprStyle) {
                    switch ($pprStyle->tagName) {
                        case 'w:ind':
                            if ($pprStyle->hasAttribute('w:left')) {
                                $styles .= 'margin-left: ' . $this->htmlPlugin->transformSizes($pprStyle->getAttribute('w:left'), 'twips') . ';';
                            }
                            if ($pprStyle->hasAttribute('w:start')) {
                                $styles .= 'margin-left: ' . $this->htmlPlugin->transformSizes($pprStyle->getAttribute('w:start'), 'twips') . ';';
                            }
                            if ($pprStyle->hasAttribute('w:right')) {
                                $styles .= 'margin-right: ' . $this->htmlPlugin->transformSizes($pprStyle->getAttribute('w:right'), 'twips') . ';';
                            }
                            if ($pprStyle->hasAttribute('w:end')) {
                                $styles .= 'margin-right: ' . $this->htmlPlugin->transformSizes($pprStyle->getAttribute('w:end'), 'twips') . ';';
                            }
                            if ($pprStyle->hasAttribute('w:hanging')) {
                                $styles .= 'padding-left: ' . $this->htmlPlugin->transformSizes($pprStyle->getAttribute('w:hanging'), 'twips') . '; text-indent: -'. $this->htmlPlugin->transformSizes($pprStyle->getAttribute('w:hanging'), 'twips') . ';';
                            }
                            if ($pprStyle->hasAttribute('w:firstLine')) {
                                 $styles .= 'text-indent: ' . $this->htmlPlugin->transformSizes($pprStyle->getAttribute('w:firstLine'), 'twips') . ';';
                            }
                            break;
                        case 'w:jc':
                            if ($pprStyle->hasAttribute('w:val')) {
                                switch ($pprStyle->getAttribute('w:val')) {
                                    case 'left':
                                    case 'start':
                                        $styles .= 'text-align: left;';
                                        break;
                                    case 'both':
                                    case 'distribute':
                                        $styles .= 'text-align: justify;';
                                        break;
                                    case 'center':
                                        $styles .= 'text-align: center;';
                                        break;
                                    case 'right':
                                    case 'end':
                                        $styles .= 'text-align: right;';
                                        break;
                                    default:
                                        break;
                                }
                            }
                            break;
                        case 'w:pageBreakBefore':
                            if ($pprStyle->getAttribute('w:val') == 'on'  || !$pprStyle->hasAttribute('w:val')) {
                                $styles .= 'page-break-before: always;';
                            }
                            break;
                        case 'w:pBdr':
                            foreach ($pprStyle->childNodes as $pbdrStyle) {
                                // iterate each border
                                $borderPosition = explode(':', $pbdrStyle->nodeName);
                                if (isset($borderPosition[1])) {
                                    // add outline as option
                                    if ($pbdrStyle->hasAttribute('w:color')) {
                                        $styles .= 'border-' . $borderPosition[1] . '-color: #' . $this->htmlPlugin->transformColors($pbdrStyle->getAttribute('w:color')) . ';';
                                    }
                                    if ($pbdrStyle->hasAttribute('w:space')) {
                                        if (is_numeric($pbdrStyle->getAttribute('w:val'))) {
                                            $styles .= 'padding-' . $borderPosition[1] . ': ' . $this->htmlPlugin->transformSizes($pbdrStyle->getAttribute('w:val'), 'pts') . ';';
                                        }
                                    }
                                    if ($pbdrStyle->hasAttribute('w:sz')) {
                                        $styles .= 'border-' . $borderPosition[1] . '-width: ' . $this->htmlPlugin->transformSizes($pbdrStyle->getAttribute('w:sz'), 'eights') . ';';
                                    }
                                    if ($pbdrStyle->hasAttribute('w:val')) {
                                        $borderStyle = $this->getBorderStyle($pbdrStyle->getAttribute('w:val'));
                                        $styles .= 'border-' . $borderPosition[1] . '-style: ' . $borderStyle . ';';
                                    }
                                }
                            }
                            break;
                        case 'w:shd':
                            if ($pprStyle->hasAttribute('w:fill')) {
                                $styles .= 'background-color: #' . $pprStyle->getAttribute('w:fill') . ';';
                            }
                            break;
                        case 'w:spacing':
                            if ($pprStyle->hasAttribute('w:after')) {
                                $styles .= 'margin-bottom: ' . $this->htmlPlugin->transformSizes($pprStyle->getAttribute('w:after'), 'twips') . ';';
                            } else {
                                $styles .= 'margin-bottom: ' . $this->htmlPlugin->transformSizes(20, 'twips') . ';';
                            }
                            if ($pprStyle->hasAttribute('w:before')) {
                                $styles .= 'margin-top: ' . $this->htmlPlugin->transformSizes($pprStyle->getAttribute('w:before'), 'twips') . ';';
                            } else {
                                $styles .= 'margin-top: ' . $this->htmlPlugin->transformSizes(20, 'twips') . ';';
                            }
                            if ($pprStyle->hasAttribute('w:line')) {
                                $styles .= 'line-height: ' . $this->htmlPlugin->transformSizes($pprStyle->getAttribute('w:line'), 'twips') . ';';
                            }
                            break;
                        case 'w:wordWrap':
                            if ($pprStyle->getAttribute('w:val') == 'on'  || !$pprStyle->hasAttribute('w:val')) {
                                $styles .= 'word-wrap: break-word;';
                            }
                            break;
                        default:
                            break;
                    }
                }
            }

            return $styles;
        }
    }

    /**
     * rPr styles
     *
     * @param DOMElement $node
     * @return string Styles
     */
    protected function addRprStyles($node)
    {
        if ($node) {
            $styles = '';
            $rprStyles = $node->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'rPr');
            if ($rprStyles->length > 0 && $rprStyles->item(0)->hasChildNodes()) {
                foreach ($rprStyles->item(0)->childNodes as $rprStyle) {
                    switch ($rprStyle->tagName) {
                        case 'w:b':
                            if ($rprStyle->getAttribute('w:val') == 'on' || !$rprStyle->hasAttribute('w:val')) {
                                $styles .= 'font-weight: bold;';
                            }
                            break;
                        case 'w:caps':
                            if ($rprStyle->getAttribute('w:val') == 'on'  || !$rprStyle->hasAttribute('w:val')) {
                                $styles .= 'text-transform: uppercase;';
                            }
                            break;
                        case 'w:color':
                            $styles .= 'color: #' . $this->htmlPlugin->transformColors($rprStyle->getAttribute('w:val')) . ';';
                            break;
                        case 'w:dstrike':
                            if ($rprStyle->getAttribute('w:val') == 'on'  || !$rprStyle->hasAttribute('w:val')) {
                                if (strstr($styles, 'text-decoration: ')) {
                                    $styles .= str_replace('text-decoration: ', 'text-decoration: line-through ', $styles);
                                } else {
                                    $styles .= 'text-decoration: line-through;';
                                }
                                $styles .= 'text-decoration-style: double;';
                            }
                            break;
                        case 'w:highlight':
                            $styles .= 'background-color: ' . $rprStyle->getAttribute('w:val') . ';';
                            break;
                        case 'w:i':
                            if ($rprStyle->getAttribute('w:val') == 'on'  || !$rprStyle->hasAttribute('w:val')) {
                                $styles .= 'font-style: italic;';
                            }
                            break;
                        case 'w:rFonts':
                            $fontFamily = '';
                            if ($rprStyle->hasAttribute('w:ascii')) {
                                $fontFamily = $rprStyle->getAttribute('w:ascii');
                            } else if ($rprStyle->hasAttribute('w:cs')) {
                                $fontFamily = $rprStyle->getAttribute('w:cs');
                            }
                            
                            $styles .= 'font-family: "' . $fontFamily. '";';
                            break;
                        case 'w:shd':
                            if ($rprStyle->hasAttribute('w:fill')) {
                                $styles .= 'background-color: #' . $rprStyle->getAttribute('w:fill') . ';';
                            }
                            break;
                        case 'w:smallCaps':
                            if ($rprStyle->getAttribute('w:val') == 'on'  || !$rprStyle->hasAttribute('w:val')) {
                                $styles .= 'text-transform: uppercase;font-size: small;';
                            }
                            break;
                        case 'w:strike':
                            if ($rprStyle->getAttribute('w:val') == 'on'  || !$rprStyle->hasAttribute('w:val')) {
                                if (strstr($styles, 'text-decoration: ')) {
                                    $styles = str_replace('text-decoration: ', 'text-decoration: line-through ', $styles);
                                } else {
                                    $styles .= 'text-decoration: line-through;';
                                }
                            }
                            break;
                        case 'w:sz':
                            // if it's a super or sub text
                            if ($rprStyle->parentNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'vertAlign')->length > 0) {
                                $styles .= 'font-size: ' . $this->htmlPlugin->transformSizes((int)$rprStyle->getAttribute('w:val') / 1.7, 'half-points') . ';';
                            } else {
                                $styles .= 'font-size: ' . $this->htmlPlugin->transformSizes($rprStyle->getAttribute('w:val'), 'half-points') . ';';
                            }
                            break;
                        case 'w:u':
                            // default value
                            $textDecorationValue = 'underline';

                            // if none, change text decoration value
                            if ($rprStyle->hasAttribute('w:val') && $rprStyle->getAttribute('w:val') == 'none') {
                                $textDecorationValue = 'none';
                            }

                            // concat other text-decoration styles such as w:strike and w:dstrike
                            if (strstr($styles, 'text-decoration: ')) {
                                $styles = str_replace('text-decoration: ', 'text-decoration: ' . $textDecorationValue . ' ', $styles);
                            } else {
                                $styles .= 'text-decoration: ' . $textDecorationValue . ';';
                            }

                            // handle text decoration style
                            if ($rprStyle->hasAttribute('w:val')) {
                                switch ($rprStyle->getAttribute('w:val')) {
                                    case 'dash':
                                        $styles .= 'text-decoration-style: dashed;';
                                        break;
                                    case 'dotted':
                                        $styles .= 'text-decoration-style: dotted;';
                                        break;
                                    case 'double':
                                        $styles .= 'text-decoration-style: double;';
                                        break;
                                    case 'single':
                                        $styles .= 'text-decoration-style: solid;';
                                        break;
                                    case 'wave':
                                        $styles .= 'text-decoration-style: wavy;';
                                        break;
                                    case 'none':
                                        // avoid adding a text-decoration-style property
                                        break;
                                    default:
                                        $styles .= 'text-decoration-style: solid;';
                                        break;
                                }
                            }
                            break;
                        case 'w:vertAlign':
                            if ($rprStyle->hasAttribute('w:val')) {
                                switch ($rprStyle->getAttribute('w:val')) {
                                    case 'subscript':
                                        $styles .= 'vertical-align: sub;';
                                        break;
                                    case 'superscript':
                                        $styles .= 'vertical-align: super;';
                                        break;
                                }
                            }
                            break;
                        default:
                            break;
                    }
                }
            }

            return $styles;
        }
    }

    /**
     * Default styles
     *
     * @return string Styles
     */
    protected function addDefaultStyles()
    {
        $xpathStyles = new \DOMXPath($this->stylesDocxDOM);
        $xpathStyles->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        // docDefaults styles
        $docDefaultsStylesPpr = $xpathStyles->query('//w:docDefaults/w:pPrDefault')->item(0);
        $docDefaultsStylesRpr = $xpathStyles->query('//w:docDefaults/w:rPrDefault')->item(0);

        //addPprStyles
        if ($docDefaultsStylesPpr) {
            $css .= 'p, h1, h2, h3, h4, h5, h6, ul, ol {' . $this->addPprStyles($docDefaultsStylesPpr) . '}';
        }
        
        //addRprStyles
        if ($docDefaultsStylesRpr) {
            $css .= 'span {' . $this->addRprStyles($docDefaultsStylesRpr) . '}';
        }

        // default styles query by w:default="1"
        $docDefaultsStyles = $xpathStyles->query('//w:style[@w:default="1"]');
        foreach ($docDefaultsStyles as $docDefaultsStyle) {
            switch ($docDefaultsStyle->getAttribute('w:type')) {
                case 'paragraph':
                    $css .= 'p, h1, h2, h3, h4, h5, h6, ul, ol {' . $this->addPprStyles($docDefaultsStyle) . '}';
                    $css .= 'span {' . $this->addRprStyles($docDefaultsStyle) . '}';
                    break;
                case 'table':
                    $stylesTable = $this->getTableStyles($docDefaultsStyle);
                    $css .= 'table {' . $stylesTable['tableStyles'] . $stylesTable['borderStylesTable'] . $stylesTable['borderInsideStylesTable'] . $stylesTable['cellPadding'] . '}';
                    break;
                default:
                    break;
            }
        }

        return $css;
    }

    /**
     * Styles file
     *
     * @return string Styles
     */
    protected function addStyles()
    {
        $xpathStyles = new \DOMXPath($this->stylesDocxDOM);
        $xpathStyles->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        // tag styles
        $styles = $xpathStyles->query('//w:style');
        if ($styles->length > 0) {
            foreach ($styles as $style) {
                $nodeClass = $style->getAttribute('w:type') . '_' . $style->getAttribute('w:styleId');
                $this->css[$nodeClass] = '';

                // open tag
                switch ($style->getAttribute('w:type')) {
                    case 'character':
                        $this->css[$nodeClass] .= $this->addRprStyles($style);
                        break;
                    case 'paragraph':
                        $this->css[$nodeClass] .= $this->addPprStyles($style);
                        $this->css[$nodeClass] .= $this->addRprStyles($style);
                        break;
                    default:
                        break;
                }
            }
        }

        return $this->css;
    }

    /**
     * Normalize border styles
     *
     * @param String $style Border style
     * @return string Styles
     */
    protected function getBorderStyle($style)
    {
        $borderStyle = 'solid';
        switch ($style) {
            case 'dashed':
                $borderStyle ='dashed';
                break;
            case 'dotted':
                $borderStyle ='dotted';
                break;
            case 'double':
                $borderStyle ='double';
                break;
            case 'nil':
            case 'none':
                $borderStyle = 'none';
                break;
            case 'single':
                $borderStyle = 'solid';
                break;
            default:
                $borderStyle = 'solid';
                break;
        }

        return $borderStyle;
    }

    /**
     * Cell styles
     *
     * @param String styles
     * @return string Styles
     */
    protected function getCellStyles($styles)
    {
        $cellStyles = '';
        $borderStylesCell = '';
        // cell style properties
        $elementWTcPr = $styles->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tcPr');
        if ($elementWTcPr->length > 0) {
            // cell borders
            $elementWTcBorders = $elementWTcPr->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tcBorders');
            if ($elementWTcBorders->length > 0) {
                // top
                $elementWTcBordersTop = $elementWTcBorders->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'top');
                if ($elementWTcBordersTop->length > 0) {
                    if ($elementWTcBordersTop->item(0)->getAttribute('w:val') == 'nil') {
                        $cellStyles .= 'border-top: hidden;';
                        $borderStylesCell .= 'border-top: hidden;';
                    } else {
                        $borderStyle = $this->getBorderStyle($elementWTcBordersTop->item(0)->getAttribute('w:val'));
                        $cellStyles .= 'border-top: ' . $this->htmlPlugin->transformSizes($elementWTcBordersTop->item(0)->getAttribute('w:sz'), 'eights') . ' ' . $borderStyle . ' #' . $this->htmlPlugin->transformColors($elementWTcBordersTop->item(0)->getAttribute('w:color')) . ';';
                        $borderStylesCell .= 'border-top: ' . $this->htmlPlugin->transformSizes($elementWTcBordersTop->item(0)->getAttribute('w:sz'), 'eights') . ' ' . $borderStyle . ' #' . $this->htmlPlugin->transformColors($elementWTcBordersTop->item(0)->getAttribute('w:color')) . ';';
                    }
                } else {
                    $cellStyles .= 'border-top: hidden;';
                    $borderStylesCell .= 'border-top: none;';
                }

                // right
                $elementWTcBordersRight = $elementWTcBorders->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'right');
                if ($elementWTcBordersRight->length > 0) {
                    if ($elementWTcBordersRight->item(0)->getAttribute('w:val') == 'nil') {
                        $cellStyles .= 'border-right: hidden;';
                        $borderStylesCell .= 'border-right: hidden;';
                    } else {
                        $borderStyle = $this->getBorderStyle($elementWTcBordersRight->item(0)->getAttribute('w:val'));
                        $cellStyles .= 'border-right: ' . $this->htmlPlugin->transformSizes($elementWTcBordersRight->item(0)->getAttribute('w:sz'), 'eights') . ' ' . $borderStyle . ' #' . $this->htmlPlugin->transformColors($elementWTcBordersRight->item(0)->getAttribute('w:color')) . ';';
                        $borderStylesCell .= 'border-right: ' . $this->htmlPlugin->transformSizes($elementWTcBordersRight->item(0)->getAttribute('w:sz'), 'eights') . ' ' . $borderStyle . ' #' . $this->htmlPlugin->transformColors($elementWTcBordersRight->item(0)->getAttribute('w:color')) . ';';
                    }
                } else {
                    $cellStyles .= 'border-right: none;';
                    $borderStylesCell .= 'border-right: none;';
                }

                // bottom
                $elementWTcBordersBottom = $elementWTcBorders->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'bottom');
                if ($elementWTcBordersBottom->length > 0) {
                    if ($elementWTcBordersBottom->item(0)->getAttribute('w:val') == 'nil') {
                        $cellStyles .= 'border-bottom: hidden;';
                        $borderStylesCell .= 'border-bottom: hidden;';
                    } else {
                        $borderStyle = $this->getBorderStyle($elementWTcBordersBottom->item(0)->getAttribute('w:val'));
                        $cellStyles.= 'border-bottom: ' . $this->htmlPlugin->transformSizes($elementWTcBordersBottom->item(0)->getAttribute('w:sz'), 'eights') . ' ' . $borderStyle . ' #' . $this->htmlPlugin->transformColors($elementWTcBordersBottom->item(0)->getAttribute('w:color')) . ';';
                        $borderStylesCell .= 'border-bottom: ' . $this->htmlPlugin->transformSizes($elementWTcBordersBottom->item(0)->getAttribute('w:sz'), 'eights') . ' ' . $borderStyle . ' #' . $this->htmlPlugin->transformColors($elementWTcBordersBottom->item(0)->getAttribute('w:color')) . ';';
                    }
                } else {
                    $cellStyles .= 'border-bottom: none;';
                    $borderStylesCell .= 'border-bottom: none;';
                }
                
                // left
                $elementWTcBordersLeft = $elementWTcBorders->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'left');
                if ($elementWTcBordersLeft->length > 0) {
                    if ($elementWTcBordersLeft->item(0)->getAttribute('w:val') == 'nil') {
                        $cellStyles .= 'border-left: hidden;';
                        $borderStylesCell .= 'border-left: hidden;';
                    } else {
                        $borderStyle = $this->getBorderStyle($elementWTcBordersLeft->item(0)->getAttribute('w:val'));
                        $cellStyles .= 'border-left: ' . $this->htmlPlugin->transformSizes($elementWTcBordersLeft->item(0)->getAttribute('w:sz'), 'eights') . ' ' . $borderStyle . ' #' . $this->htmlPlugin->transformColors($elementWTcBordersLeft->item(0)->getAttribute('w:color')) . ';';
                        $borderStylesCell .= 'border-left: ' . $this->htmlPlugin->transformSizes($elementWTcBordersLeft->item(0)->getAttribute('w:sz'), 'eights') . ' ' . $borderStyle . ' #' . $this->htmlPlugin->transformColors($elementWTcBordersLeft->item(0)->getAttribute('w:color')) . ';';
                    }
                } else {
                    $cellStyles .= 'border-left: none;';
                    $borderStylesCell .= 'border-left: none;';
                }
            }
        }

        return array('cellStyles' => $cellStyles, 'borderStylesCell' => $borderStylesCell);
    }

    /**
     * Meta values
     *
     * @return string metas
     */
    protected function getMetaValues()
    {
        $documentCoreContent = $this->docxStructure->getContent('docProps/core.xml');

        $tags = '';

        if ($documentCoreContent) {
            $xmlCoreContent = new \DOMDocument();
            $optionEntityLoader = libxml_disable_entity_loader(true);
            $xmlCoreContent->loadXML($documentCoreContent);
            libxml_disable_entity_loader($optionEntityLoader);
            foreach ($xmlCoreContent->childNodes->item(0)->childNodes as $prop) {
                switch ($prop->tagName) {
                    case 'dc:title':
                        $tags .= '<title>' . $prop->nodeValue . '</title>';
                        break;
                    case 'dc:creator':
                        $tags .= '<meta name="author" content="' . $prop->nodeValue . '">';
                        break;
                    case 'cp:keywords':
                        $tags .= '<meta name="keywords" content="' . $prop->nodeValue . '">';
                        break;
                    case 'dc:description':
                        $tags .= '<meta name="description" content="' . $prop->nodeValue . '">';
                        break;
                    default:
                        break;
                }
            }
        }

        return $tags;
    }

    /**
     * Numbering type
     *
     * @param string $id
     * @param string $level
     * @return string start value
     */
    protected function getNumberingStart($id, $level)
    {
        $documentNumbering = $this->docxStructure->getContent('word/numbering.xml');

        $xmlNumbering = new \DOMDocument();
        $optionEntityLoader = libxml_disable_entity_loader(true);
        $xmlNumbering->loadXML($documentNumbering);
        libxml_disable_entity_loader($optionEntityLoader);

        // get w:num by Id
        $xpathNumbering = new \DOMXPath($xmlNumbering);
        $xpathNumbering->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $elementNum = $xpathNumbering->query('//w:num[@w:numId="' . $id . '"]')->item(0);

        if ($elementNum != '') {
            // get w:abstractNumId used to set the numbering styles
            $abstractNumId = $elementNum->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'abstractNumId')->item(0)->getAttribute('w:val');
            
            // get the style of the w:abstractNum related to w:abstractNumId
            $elementAbstractNumStart = $xpathNumbering->query(
                '//w:abstractNum[@w:abstractNumId="' . $abstractNumId . '"]' .
                '/w:lvl[@w:ilvl="' . $level . '"]' .
                '/w:start'
            )->item(0);

            return $elementAbstractNumStart->getAttribute('w:val');
        }
        
        // style not found, return 1 as default value
        return '1';
    }

    /**
     * Numbering styles
     *
     * @param string $id
     * @param string $level
     * @return string Styles or null
     */
    protected function getNumberingStyles($id, $level)
    {
        $documentNumbering = $this->docxStructure->getContent('word/numbering.xml');

        $xmlNumbering = new \DOMDocument();
        $optionEntityLoader = libxml_disable_entity_loader(true);
        $xmlNumbering->loadXML($documentNumbering);
        libxml_disable_entity_loader($optionEntityLoader);

        // get w:num by Id
        $xpathNumbering = new \DOMXPath($xmlNumbering);
        $xpathNumbering->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $elementNum = $xpathNumbering->query('//w:num[@w:numId="' . $id . '"]')->item(0);

        if ($elementNum != '') {
            // get w:abstractNumId used to set the numbering styles
            $abstractNumId = $elementNum->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'abstractNumId')->item(0)->getAttribute('w:val');

            // get the level content of the w:abstractNum related to w:abstractNumId
            $elementAbstractNumLvl = $xpathNumbering->query(
                '//w:abstractNum[@w:abstractNumId="' . $abstractNumId . '"]' .
                '/w:lvl[@w:ilvl="' . $level . '"]'
            )->item(0);


            return $elementAbstractNumLvl;
        }
        
        // style not found
        return null;
    }

    /**
     * Numbering type
     *
     * @param string $id
     * @param string $level
     * @return string Styles or null
     */
    protected function getNumberingType($id, $level)
    {
        $documentNumbering = $this->docxStructure->getContent('word/numbering.xml');

        $xmlNumbering = new \DOMDocument();
        $optionEntityLoader = libxml_disable_entity_loader(true);
        $xmlNumbering->loadXML($documentNumbering);
        libxml_disable_entity_loader($optionEntityLoader);

        // get w:num by Id
        $xpathNumbering = new \DOMXPath($xmlNumbering);
        $xpathNumbering->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $elementNum = $xpathNumbering->query('//w:num[@w:numId="' . $id . '"]')->item(0);

        if ($elementNum != '') {
            // get w:abstractNumId used to set the numbering styles
            $abstractNumId = $elementNum->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'abstractNumId')->item(0)->getAttribute('w:val');
            
            // get the style of the w:abstractNum related to w:abstractNumId
            $elementAbstractNumFmt = $xpathNumbering->query(
                '//w:abstractNum[@w:abstractNumId="' . $abstractNumId . '"]' .
                '/w:lvl[@w:ilvl="' . $level . '"]' .
                '/w:numFmt'
            )->item(0);

            return $elementAbstractNumFmt->getAttribute('w:val');
        }
        
        // style not found
        return null;
    }

    /**
     * Table styles
     *
     * @param string $styles
     * @return array Styles
     */
    protected function getTableStyles($styles)
    {
        $tableStyles = '';
        $borderStylesTable = '';
        $borderInsideStylesTable = '';
        $cellPadding = '';
        $firstLastStyles = array();

        // table style properties
        $elementWTblPr = $styles->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tblPr');
        if ($elementWTblPr->length > 0) {
            // table width
            $elementWTblW = $styles->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tblPr')->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tblW');
            if ($elementWTblW->length > 0) {
                if ($elementWTblW->item(0)->getAttribute('w:type') == 'pct') {
                    // MS Word allows to set width pct using two formats: int (5000 is 100%) or %
                    if (strpos($elementWTblW->item(0)->getAttribute('w:w'), '%') !== false) {
                        // percent value
                        $tableStyles .= 'width: ' . $elementWTblW->item(0)->getAttribute('w:w') . ';';
                    } else {
                        // int value
                        $tableStyles .= 'width: ' . $this->htmlPlugin->transformSizes($elementWTblW->item(0)->getAttribute('w:w'), 'fifths-percent', '%') . ';';
                    }
                } elseif ($elementWTblW->item(0)->getAttribute('w:type') == 'dxa') {
                    $tableStyles .= 'width: ' . $this->htmlPlugin->transformSizes($elementWTblW->item(0)->getAttribute('w:w'), 'twips') . ';';
                }
            }

            // table align
            $elementWTblJc = $styles->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tblPr')->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'jc');
            if ($elementWTblJc->length > 0) {
                if ($elementWTblJc->item(0)->getAttribute('w:val') == 'center') {
                    $tableStyles .= 'margin-left: auto; margin-right: auto;';
                }

                if ($elementWTblJc->item(0)->getAttribute('w:val') == 'right') {
                    $tableStyles .= 'margin-right: 0px; margin-left: auto;';
                }
            }

            // table layout
            $elementWTblLayout = $styles->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tblPr')->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tblLayout');
            if ($elementWTblLayout->length > 0) {
                if ($elementWTblLayout->item(0)->getAttribute('w:type') == 'fixed') {
                    $tableStyles .= 'table-layout: auto;';
                }
            }

            // table indent
            $elementWTblInd = $styles->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tblPr')->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tblInd');
            if ($elementWTblInd->length > 0) {
                $tableStyles .= 'margin-left: ' . $this->htmlPlugin->transformSizes($elementWTblInd->item(0)->getAttribute('w:w'), 'twips') . ';';
            }

            // table padding
            $elementWTblCellMar = $styles->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tblPr')->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tblCellMar');
            if ($elementWTblCellMar->length > 0) {
                $elementWTblCellMarTop = $elementWTblCellMar->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'top');
                $elementWTblCellMarRight = $elementWTblCellMar->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'right');
                $elementWTblCellMarBottom = $elementWTblCellMar->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'bottom');
                $elementWTblCellMarLeft = $elementWTblCellMar->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'left');
                if ($elementWTblCellMarTop->item(0)) {
                    $cellPadding .= 'padding-top: ' . $this->htmlPlugin->transformSizes($elementWTblCellMarTop->item(0)->getAttribute('w:w'), 'twips') . ';';
                }
                if ($elementWTblCellMarRight->item(0)) {
                    $cellPadding .= 'padding-right: ' . $this->htmlPlugin->transformSizes($elementWTblCellMarRight->item(0)->getAttribute('w:w'), 'twips') . ';';
                }
                if ($elementWTblCellMarBottom->item(0)) {
                    $cellPadding .= 'padding-bottom: ' . $this->htmlPlugin->transformSizes($elementWTblCellMarBottom->item(0)->getAttribute('w:w'), 'twips') . ';';
                }
                if ($elementWTblCellMarLeft->item(0)) {
                    $cellPadding .= 'padding-left: ' . $this->htmlPlugin->transformSizes($elementWTblCellMarLeft->item(0)->getAttribute('w:w'), 'twips') . ';';
                }
            }

            // table borders
            $elementWTblBorders = $styles->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tblPr')->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tblBorders');
            if ($elementWTblBorders->length > 0) {
                // keep border styles to be used if tr or td doesn't overwrite them

                // top
                $elementWTblBordersTop = $elementWTblBorders->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'top');
                if ($elementWTblBordersTop->length > 0) {
                    $borderStyle = $this->getBorderStyle($elementWTblBordersTop->item(0)->getAttribute('w:val'));
                    $tableStyles .= 'border-top: ' . $this->htmlPlugin->transformSizes($elementWTblBordersTop->item(0)->getAttribute('w:sz'), 'eights') . ' ' . $borderStyle . ' #' . $this->htmlPlugin->transformColors($elementWTblBordersTop->item(0)->getAttribute('w:color')) . ';';
                    $borderStylesTable .= 'border-top: ' . $this->htmlPlugin->transformSizes($elementWTblBordersTop->item(0)->getAttribute('w:sz'), 'eights') . ' ' . $borderStyle . ' #' . $this->htmlPlugin->transformColors($elementWTblBordersTop->item(0)->getAttribute('w:color')) . ';';
                }

                // right
                $elementWTblBordersRight = $elementWTblBorders->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'right');
                if ($elementWTblBordersRight->length > 0) {
                    $borderStyle = $this->getBorderStyle($elementWTblBordersRight->item(0)->getAttribute('w:val'));
                    $tableStyles .= 'border-right: ' . $this->htmlPlugin->transformSizes($elementWTblBordersRight->item(0)->getAttribute('w:sz'), 'eights') . ' ' . $borderStyle . ' #' . $this->htmlPlugin->transformColors($elementWTblBordersRight->item(0)->getAttribute('w:color')) . ';';
                    $borderStylesTable .= 'border-right: ' . $this->htmlPlugin->transformSizes($elementWTblBordersRight->item(0)->getAttribute('w:sz'), 'eights') . ' ' . $borderStyle . ' #' . $this->htmlPlugin->transformColors($elementWTblBordersRight->item(0)->getAttribute('w:color')) . ';';
                }

                // bottom
                $elementWTblBordersBottom = $elementWTblBorders->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'bottom');
                if ($elementWTblBordersBottom->length > 0) {
                    $borderStyle = $this->getBorderStyle($elementWTblBordersBottom->item(0)->getAttribute('w:val'));
                    $tableStyles.= 'border-bottom: ' . $this->htmlPlugin->transformSizes($elementWTblBordersBottom->item(0)->getAttribute('w:sz'), 'eights') . ' ' . $borderStyle . ' #' . $this->htmlPlugin->transformColors($elementWTblBordersBottom->item(0)->getAttribute('w:color')) . ';';
                    $borderStylesTable .= 'border-bottom: ' . $this->htmlPlugin->transformSizes($elementWTblBordersBottom->item(0)->getAttribute('w:sz'), 'eights') . ' ' . $borderStyle . ' #' . $this->htmlPlugin->transformColors($elementWTblBordersBottom->item(0)->getAttribute('w:color')) . ';';
                }
                
                // left
                $elementWTblBordersLeft = $elementWTblBorders->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'left');
                if ($elementWTblBordersLeft->length > 0) {
                    $borderStyle = $this->getBorderStyle($elementWTblBordersLeft->item(0)->getAttribute('w:val'));
                    $tableStyles .= 'border-left: ' . $this->htmlPlugin->transformSizes($elementWTblBordersLeft->item(0)->getAttribute('w:sz'), 'eights') . ' ' . $borderStyle . ' #' . $this->htmlPlugin->transformColors($elementWTblBordersLeft->item(0)->getAttribute('w:color')) . ';';
                    $borderStylesTable .= 'border-left: ' . $this->htmlPlugin->transformSizes($elementWTblBordersLeft->item(0)->getAttribute('w:sz'), 'eights') . ' ' . $borderStyle . ' #' . $this->htmlPlugin->transformColors($elementWTblBordersLeft->item(0)->getAttribute('w:color')) . ';';
                }

                // insideH
                $elementWTblBordersInsideH = $elementWTblBorders->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'insideH');
                if ($elementWTblBordersInsideH->length > 0) {
                    $borderStyle = $this->getBorderStyle($elementWTblBordersInsideH->item(0)->getAttribute('w:val'));
                    //$tableStyles .= 'border-left: ' . $this->htmlPlugin->transformSizes($elementWTblBordersInsideH->item(0)->getAttribute('w:sz'), 'eights') . ' ' . $borderStyle . ' #' . $this->htmlPlugin->transformColors($elementWTblBordersInsideH->item(0)->getAttribute('w:color')) . ';';
                    //$borderStylesTable .= 'border-left: ' . $this->htmlPlugin->transformSizes($elementWTblBordersInsideH->item(0)->getAttribute('w:sz'), 'eights') . ' ' . $borderStyle . ' #' . $this->htmlPlugin->transformColors($elementWTblBordersInsideH->item(0)->getAttribute('w:color')) . ';';

                    $borderInsideStylesTable .= 'border-bottom: ' . $this->htmlPlugin->transformSizes($elementWTblBordersInsideH->item(0)->getAttribute('w:sz'), 'eights') . ' ' . $borderStyle . ' #' . $this->htmlPlugin->transformColors($elementWTblBordersInsideH->item(0)->getAttribute('w:color')) . ';';
                    $borderInsideStylesTable .= 'border-top: ' . $this->htmlPlugin->transformSizes($elementWTblBordersInsideH->item(0)->getAttribute('w:sz'), 'eights') . ' ' . $borderStyle . ' #' . $this->htmlPlugin->transformColors($elementWTblBordersInsideH->item(0)->getAttribute('w:color')) . ';';
                }

                // insideV
                $elementWTblBordersInsideV = $elementWTblBorders->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'insideV');
                if ($elementWTblBordersInsideV->length > 0) {
                    $borderStyle = $this->getBorderStyle($elementWTblBordersInsideV->item(0)->getAttribute('w:val'));
                    //$tableStyles .= 'border-right: ' . $this->htmlPlugin->transformSizes($elementWTblBordersInsideV->item(0)->getAttribute('w:sz'), 'eights') . ' ' . $borderStyle . ' #' . $this->htmlPlugin->transformColors($elementWTblBordersInsideV->item(0)->getAttribute('w:color')) . ';';
                    //$borderStylesTable .= 'border-right: ' . $this->htmlPlugin->transformSizes($elementWTblBordersInsideV->item(0)->getAttribute('w:sz'), 'eights') . ' ' . $borderStyle . ' #' . $this->htmlPlugin->transformColors($elementWTblBordersInsideV->item(0)->getAttribute('w:color')) . ';';

                    $borderInsideStylesTable .= 'border-left: ' . $this->htmlPlugin->transformSizes($elementWTblBordersInsideV->item(0)->getAttribute('w:sz'), 'eights') . ' ' . $borderStyle . ' #' . $this->htmlPlugin->transformColors($elementWTblBordersInsideV->item(0)->getAttribute('w:color')) . ';';
                    $borderInsideStylesTable .= 'border-right: ' . $this->htmlPlugin->transformSizes($elementWTblBordersInsideV->item(0)->getAttribute('w:sz'), 'eights') . ' ' . $borderStyle . ' #' . $this->htmlPlugin->transformColors($elementWTblBordersInsideV->item(0)->getAttribute('w:color')) . ';';
                }
            }

            // floating
            $elementWTblTblpPr = $styles->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tblPr')->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tblpPr');
            if ($elementWTblTblpPr->length > 0) {
                if ($elementWTblTblpPr->item(0)->hasAttribute('w:bottomFromText')) {
                    $tableStyles .= 'margin-bottom: ' . $this->htmlPlugin->transformSizes($elementWTblTblpPr->item(0)->getAttribute('w:bottomFromText'), 'twips') . ';';
                }
                if ($elementWTblTblpPr->item(0)->hasAttribute('w:topFromText')) {
                    $tableStyles .= 'margin-top: ' . $this->htmlPlugin->transformSizes($elementWTblTblpPr->item(0)->getAttribute('w:topFromText'), 'twips') . ';';
                }
            }
        }

        $elementWTblStylePrs = $styles->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tblStylePr');
        if ($elementWTblStylePrs->length > 0) {
            // keep the right HTML order. Save the CSS styles to be added in the correct order
            $firstLastStylesValues = array('band1Horz', 'band2Horz', 'firstCol', 'firstRow', 'lastCol', 'lastRow');
            foreach ($elementWTblStylePrs as $elementWTblStylePr) {
                $selectorCell = '';
                switch ($elementWTblStylePr->getAttribute('w:type')) {
                    case 'band1Horz':
                        $firstLastStylesValues['band1Horz']['__CLASSNAMETABLE__ tr:nth-child(even)'] .= $this->addPprStyles($elementWTblStylePr);

                        // rPr styles
                        $firstLastStylesValues['band1Horz']['__CLASSNAMETABLE__ tr:nth-child(even) td'] .= $this->addRprStyles($elementWTblStylePr);
                        $firstLastStylesValues['band1Horz']['__CLASSNAMETABLE__ tr:nth-child(even) td'] .= $this->getTcPrStyles($elementWTblStylePr);

                        break;
                    case 'band2Horz':
                        // pPr styles
                        $firstLastStylesValues['band2Horz']['__CLASSNAMETABLE__ tr:nth-child(odd)'] .= $this->addPprStyles($elementWTblStylePr);

                        // rPr styles
                        $firstLastStylesValues['band2Horz']['__CLASSNAMETABLE__ tr:nth-child(odd) td'] .= $this->addRprStyles($elementWTblStylePr);
                        $firstLastStylesValues['band2Horz']['__CLASSNAMETABLE__ tr:nth-child(odd) td'] .= $this->getTcPrStyles($elementWTblStylePr);

                        break;
                    case 'firstCol':
                        // rPr styles
                        $firstLastStylesValues['firstCol']['__CLASSNAMETABLE__ tr td:first-child'] .= $this->addRprStyles($elementWTblStylePr);
                        $firstLastStylesValues['firstCol']['__CLASSNAMETABLE__ tr td:first-child'] .= $this->getTcPrStyles($elementWTblStylePr);
                        break;
                    case 'firstRow':
                        // pPr styles
                        $firstLastStylesValues['firstRow']['__CLASSNAMETABLE__ tr:first-child'] .= $this->addPprStyles($elementWTblStylePr);

                        // rPr styles
                        $firstLastStylesValues['firstRow']['__CLASSNAMETABLE__ tr:first-child td'] .= $this->addRprStyles($elementWTblStylePr);
                        $firstLastStylesValues['firstRow']['__CLASSNAMETABLE__ tr:first-child td'] .= $this->getTcPrStyles($elementWTblStylePr);
                        break;
                    case 'lastCol':
                        // rPr styles
                        $firstLastStylesValues['lastCol']['__CLASSNAMETABLE__ tr td:last-child'] .= $this->addRprStyles($elementWTblStylePr);
                        $firstLastStylesValues['lastCol']['__CLASSNAMETABLE__ tr td:last-child'] .= $this->getTcPrStyles($elementWTblStylePr);
                        break;
                    case 'lastRow':
                        // pPr styles
                        $firstLastStylesValues['lastRow']['__CLASSNAMETABLE__ tr:last-child'] .= $this->addPprStyles($elementWTblStylePr);

                        // rPr styles
                        $firstLastStylesValues['lastRow']['__CLASSNAMETABLE__ tr:last-child td'] .= $this->addRprStyles($elementWTblStylePr);
                        $firstLastStylesValues['lastRow']['__CLASSNAMETABLE__ tr:last-child td'] .= $this->getTcPrStyles($elementWTblStylePr);
                        break;
                    default:
                        break;
                }
            }
            // get the correct order for $firstLastStyles
            if (isset($firstLastStylesValues['band1Horz']) && count($firstLastStylesValues['band1Horz']) > 0) {
                foreach ($firstLastStylesValues['band1Horz'] as $key => $value) {
                    $firstLastStyles[$key] = $value;
                }
            }
            if (isset($firstLastStylesValues['band2Horz']) && count($firstLastStylesValues['band2Horz']) > 0) {
                foreach ($firstLastStylesValues['band2Horz'] as $key => $value) {
                    $firstLastStyles[$key] = $value;
                }
            }
            if (isset($firstLastStylesValues['firstCol']) && count($firstLastStylesValues['firstCol']) > 0) {
                foreach ($firstLastStylesValues['firstCol'] as $key => $value) {
                    $firstLastStyles[$key] = $value;
                }
            }
            if (isset($firstLastStylesValues['lastCol']) && count($firstLastStylesValues['lastCol']) > 0) {
                foreach ($firstLastStylesValues['lastCol'] as $key => $value) {
                    $firstLastStyles[$key] = $value;
                }
            }
            if (isset($firstLastStylesValues['firstRow']) && count($firstLastStylesValues['firstRow']) > 0) {
                foreach ($firstLastStylesValues['firstRow'] as $key => $value) {
                    $firstLastStyles[$key] = $value;
                }
            }
            if (isset($firstLastStylesValues['lastRow']) && count($firstLastStylesValues['lastRow']) > 0) {
                foreach ($firstLastStylesValues['lastRow'] as $key => $value) {
                    $firstLastStyles[$key] = $value;
                }
            }
        }


        return array('tableStyles' => $tableStyles, 'borderStylesTable' => $borderStylesTable, 'borderInsideStylesTable' => $borderInsideStylesTable, 'cellPadding' => $cellPadding, 'firstLastStyles' => $firstLastStyles);
    }

    /**
     * TcPr styles
     *
     * @param string $styles
     * @return string Styles
     */
    protected function getTcPrStyles($styles)
    {
        $stylesTcPr = '';

        // cell properties
        $elementWTblTrTcTcpr = $styles->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tcPr');
        if ($elementWTblTrTcTcpr->length > 0) {
            // width
            $elementWTblTrTcTcprTcW = $elementWTblTrTcTcpr->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tcW');
            if ($elementWTblTrTcTcprTcW->length > 0) {
                $stylesTcPr .= 'width: ' . $this->htmlPlugin->transformSizes($elementWTblTrTcTcprTcW->item(0)->getAttribute('w:w'), 'twips') . ';';
            }

            // borders
            $borderCells = $this->getCellStyles($styles);
            $stylesTcPr .= $borderCells['borderStylesCell'];

            // background
            $elementWTblTrTcTcprShd = $elementWTblTrTcTcpr->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'shd');
            if ($elementWTblTrTcTcprShd->length > 0) {
                if ($elementWTblTrTcTcprShd->item(0)->hasAttribute('w:fill')) {
                    $stylesTcPr .= 'background-color: #' . $elementWTblTrTcTcprShd->item(0)->getAttribute('w:fill') . ';';
                }
            }

            // paddings
            $elementWTblTrTcTcprTcMar = $elementWTblTrTcTcpr->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tcMar');
            if ($elementWTblTrTcTcprTcMar->length > 0) {
                // top
                $elementWTblTrTcTcprTcMarTop = $elementWTblTrTcTcprTcMar->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'top');
                if ($elementWTblTrTcTcprTcMarTop->length > 0) {
                    $stylesTcPr .= 'padding-top: ' . $this->htmlPlugin->transformSizes($elementWTblTrTcTcprTcMarTop->item(0)->getAttribute('w:w'), 'twips') . ';';
                }
                // right
                $elementWTblTrTcTcprTcMarRight = $elementWTblTrTcTcprTcMar->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'right');
                if ($elementWTblTrTcTcprTcMarRight->length > 0) {
                    $stylesTcPr .= 'padding-right: ' . $this->htmlPlugin->transformSizes($elementWTblTrTcTcprTcMarRight->item(0)->getAttribute('w:w'), 'twips') . ';';
                }
                // bottom
                $elementWTblTrTcTcprTcMarBottom = $elementWTblTrTcTcprTcMar->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'bottom');
                if ($elementWTblTrTcTcprTcMarBottom->length > 0) {
                    $stylesTcPr .= 'padding-bottom: ' . $this->htmlPlugin->transformSizes($elementWTblTrTcTcprTcMarBottom->item(0)->getAttribute('w:w'), 'twips') . ';';
                }
                // left
                $elementWTblTrTcTcprTcMarLeft = $elementWTblTrTcTcprTcMar->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'left');
                if ($elementWTblTrTcTcprTcMarLeft->length > 0) {
                    $stylesTcPr .= 'padding-left: ' . $this->htmlPlugin->transformSizes($elementWTblTrTcTcprTcMarLeft->item(0)->getAttribute('w:w'), 'twips') . ';';
                }
            }

            // vertical align
            $elementWTblTrTcTcprVAlign = $elementWTblTrTcTcpr->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'vAlign');
            if ($elementWTblTrTcTcprVAlign->length > 0) {
                $vAlign = 'middle';
                switch ($elementWTblTrTcTcprVAlign->item(0)->getAttribute('w:val')) {
                    case 'top':
                        $vAlign = 'top';
                        break;
                    case 'bottom':
                        $vAlign = 'bottom';
                        break;
                    case 'both':
                    case 'center':
                        $vAlign = 'middle';
                        break;
                    default:
                        $vAlign = 'top';
                        break;
                }

                $stylesTcPr .= 'vertical-align: ' . $vAlign . ';';
            }
        }

        return $stylesTcPr;
    }

    /**
     * Get target value of a relationship
     *
     * @param string $id
     * @return string target or null
     */
    protected function getRelationshipContent($id)
    {
        if ($this->target == 'comments') {
            $relsContent = $this->docxStructure->getContent('word/_rels/comments.xml.rels');
        } else if ($this->target == 'endnotes') {
            $relsContent = $this->docxStructure->getContent('word/_rels/endnotes.xml.rels');
        } else if ($this->target == 'footnotes') {
            $relsContent = $this->docxStructure->getContent('word/_rels/footnotes.xml.rels');
        } else if ($this->target == 'headers') {
            $relsContent = $this->docxStructure->getContent('word/_rels/'.$this->targetExtra.'.rels');
        } else if ($this->target == 'footers') {
            $relsContent = $this->docxStructure->getContent('word/_rels/'.$this->targetExtra.'.rels');
        } else {
            $relsContent = $this->docxStructure->getContent('word/_rels/document.xml.rels');
        }
        

        $xmlDocumentRels = new \DOMDocument();
        $optionEntityLoader = libxml_disable_entity_loader(true);
        $xmlDocumentRels->loadXML($relsContent);
        libxml_disable_entity_loader($optionEntityLoader);
        $xpath = new \DOMXPath($xmlDocumentRels);
        $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');

        $elementId = $xpath->query('//r:Relationships/r:Relationship[@Id="'.$id.'"]')->item(0);


        if (!$elementId || !$elementId->hasAttribute('Target')) {
            return null;
        }
        
        return $elementId->getAttribute('Target');
    }

    /**
     * Transform default tag (not supported tag)
     *
     * @param DOMElement $childNode
     * @param String $nodeClass
     */
    protected function transformDEFAULT_TAG($childNode, $nodeClass)
    {
        // handle child elements
        if ($childNode->hasChildNodes()) {
            $this->transformXml($childNode);
        }
    }

    /**
     * Transform m:omath tag
     *
     * @param DOMElement $childNode
     */
    protected function transformM_OMATH($childNode)
    {
        $rscXML = new \DOMDocument();
        $optionEntityLoader = libxml_disable_entity_loader(false);
        $mathMLXML = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math">' . $childNode->ownerDocument->saveXML($childNode). '</w:document>';
        $rscXML->loadXML($mathMLXML);
        libxml_disable_entity_loader($optionEntityLoader);
        $objXSLTProc = new \XSLTProcessor();
        $objXSL = new \DOMDocument();
        $objXSL->load(dirname(__FILE__) . '/../xsl/OMML2MML.XSL');
        $objXSLTProc->importStylesheet($objXSL);

        $mathML = $objXSLTProc->transformToXML($rscXML);

        $this->html .= str_replace(array('mml:', 'xmlns:'), '', $mathML);
    }

    /**
     * Transform v:textbox tag
     *
     * @param DOMElement $childNode
     */
    protected function transformV_TEXTBOX($childNode, $nodeClass)
    {
        // set textbox as div
        $this->html .= '<div class="'.$nodeClass.'">';

        // get and add styles
        $textboxStyles = $childNode->parentNode->getAttribute('style');
        $textboxStylesElements = explode(';', $textboxStyles);
        foreach ($textboxStylesElements as $textboxStylesElement) {
            // get the style and its value
            $textboxStylesElementProperty = explode(':', $textboxStylesElement);
            switch ($textboxStylesElementProperty[0]) {
                case 'height':
                    $this->css[$nodeClass] .= 'min-height: ' . $this->htmlPlugin->transformSizes($textboxStylesElementProperty[1], 'pts') . ';';
                    break;
                case 'mso-position-horizontal':
                    $this->css[$nodeClass] .= 'float: ' . $textboxStylesElementProperty[1] . ';';
                    break;
                case 'width':
                    $this->css[$nodeClass] .= 'width: ' . $this->htmlPlugin->transformSizes($textboxStylesElementProperty[1], 'pts') . ';';
                    break;
                default:
                    break;
            }
        }
        if ($childNode->parentNode->hasAttribute('fillcolor')) {
            $this->css[$nodeClass] .= 'background-color:' . $childNode->parentNode->getAttribute('fillcolor') . ';';
        }
        if ($childNode->parentNode->hasAttribute('strokecolor')) {
            $this->css[$nodeClass] .= 'border-color:' . $childNode->parentNode->getAttribute('strokecolor') . ';';
            $this->css[$nodeClass] .= 'border-style: solid;';

            if ($childNode->parentNode->hasAttribute('strokeweight')) {
                $this->css[$nodeClass] .= 'border-width:' . $this->htmlPlugin->transformSizes($childNode->parentNode->getAttribute('strokeweight'), 'pts') . ';';
            } else {
                $this->css[$nodeClass] .= 'border-width:' . $this->htmlPlugin->transformSizes(1, 'pts') . ';';
            }
        }

        $this->css[$nodeClass] .= 'padding: 5px;';

        // handle child elements
        if ($childNode->hasChildNodes()) {
            $this->transformXml($childNode);
        }

        $this->html .= '</div>';
    }

    /**
     * Transform w:altchunk tag
     *
     * @param DOMElement $childNode
     * @param String $nodeClass
     */
    protected function transformW_ALTCHUNK($childNode, $nodeClass)
    {
        // get file content
        if ($childNode->hasAttribute('r:id')) {
            $target = $this->getRelationshipContent($childNode->getAttribute('r:id'));
            $this->html .= '<a href="' . $target . '" class="'.$nodeClass.' ' . ($this->htmlPlugin->getExtraClass('a')==null?'':$this->htmlPlugin->getExtraClass('a')) . '">Download ' . $target . '</a>';
        }
    }

    /**
     * Transform w:bookmarkstart tag
     *
     * @param DOMElement $childNode
     * @param String $nodeClass
     */
    protected function transformW_BOOKMARKSTART($childNode, $nodeClass)
    {
        if ($childNode->hasAttribute('w:name')) {
            $this->html .= '<a class="'.$nodeClass.'" name="'.$childNode->getAttribute('w:name').'"></a>';
        }
    }

    /**
     * Transform w:br tag
     *
     * @param DOMElement $childNode
     * @param String $nodeClass
     */
    protected function transformW_BR($childNode, $nodeClass)
    {
        $this->html .= '<' . $this->htmlPlugin->getTag('br') . '>';
    }

    /**
     * Transform w:comment tag
     *
     * @param DOMElement $childNode
     * @param String $nodeClass
     */
    protected function transformW_COMMENT($childNode, $nodeClass)
    {
        if (trim($childNode->nodeValue) != '') {
            $this->commentsContent = '<span id="comment-' . $childNode->getAttribute('w:id') . '">' . $this->commentsIndex['PHPDOCX_COMMENTREFERENCE_' . $childNode->getAttribute('w:id')] . '</span> ';

            // handle child elements
            if ($childNode->hasChildNodes()) {
                $this->transformXml($childNode);
            }
        }
    }

    /**
     * Transform w:commentreference tag
     *
     * @param DOMElement $childNode
     * @param String $nodeClass
     */
    protected function transformW_COMMENTREFERENCE($childNode, $nodeClass)
    {
        // if the reference already has a custom mark do not add the placeholder
        if (!$childNode->hasAttribute('w:customMarkFollows')) {
            $this->commentsIndex['PHPDOCX_COMMENTREFERENCE_' . $childNode->getAttribute('w:id')] = '[COMMENT ' . (count($this->commentsIndex) + 1) . ']';

            $this->html .= '<a href="#comment-' . $childNode->getAttribute('w:id') . '">' . '[COMMENT ' . count($this->commentsIndex) . ']' . '</a> ';
        }
    }

    /**
     * Transform w:drawing tag
     *
     * @param DOMElement $childNode
     * @param String $nodeClass
     */
    protected function transformW_DRAWING($childNode, $nodeClass)
    {
        $elementABlip = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'blip')->item(0);
        $elementCChart = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/chart', 'chart')->item(0);

        if ($elementABlip) {
            // image drawing
            $target = $this->getRelationshipContent($elementABlip->getAttribute('r:embed'));
            $imageString = $this->docxStructure->getContent('word/' . $target);

            if (!$target) {
                // external images
                $src = $this->getRelationshipContent($elementABlip->getAttribute('r:link'));
            } else {
                // embedded images
                $fileInfo = pathinfo($target);
                if (!empty($fileInfo['basename'])) {
                    $fileInfo = pathinfo($target);
                    if ($this->htmlPlugin->getImagesAsBase64()) {
                        $src = 'data:image/' . $fileInfo['extension'] . ';base64,' . base64_encode($imageString);;
                    } else {
                        $src = $this->htmlPlugin->getOutputFilesPath() . $fileInfo['basename'];
                        file_put_contents($src, $imageString);
                    }
                }
            }

            // width and height
            $elementWPExtent = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing', 'extent')->item(0);
            $width = round((float)$elementWPExtent->getAttribute('cx') / 9525);
            $height = round((float)$elementWPExtent->getAttribute('cy') / 9525);

            // spacing
            $elementWPEffectExtent = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing', 'effectExtent')->item(0);
            if ($elementWPEffectExtent) {
                $this->css[$nodeClass] .= 'margin-top: ' . round((float)$elementWPEffectExtent->getAttribute('t') / 9525) . 'px;';
                $this->css[$nodeClass] .= 'margin-right: ' . round((float)$elementWPEffectExtent->getAttribute('r') / 9525) . 'px;';
                $this->css[$nodeClass] .= 'margin-bottom: ' . round((float)$elementWPEffectExtent->getAttribute('b') / 9525) . 'px;';
                $this->css[$nodeClass] .= 'margin-left: ' . round((float)$elementWPEffectExtent->getAttribute('l') / 9525) . 'px;';
            }

            // used for float and text wrapping. If true, don't use text wrapping
            $alignMode = false;

            // float and horizontal position
            $elementWPPositionH = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing', 'positionH');
            if ($elementWPPositionH->length > 0) {
                $elementWPAlign = $elementWPPositionH->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing', 'align');
                if ($elementWPAlign->length > 0) {
                    $alignMode = true;
                    if ($elementWPAlign->item(0)->nodeValue == 'right') {
                        $this->css[$nodeClass] .= 'float: right;';
                    } elseif ($elementWPAlign->item(0)->nodeValue == 'left') {
                        $this->css[$nodeClass] .= 'float: left;';
                    } elseif ($elementWPAlign->item(0)->nodeValue == 'center') {
                        $this->css[$nodeClass] .= 'display:block; margin-left: auto; margin-right: auto;';
                    }
                }

                $elementWPPosOffset = $elementWPPositionH->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing', 'posOffset');
                if ($elementWPPosOffset->length > 0) {
                    $this->css[$nodeClass] .= 'margin-left: ' . round((float)$elementWPPosOffset->item(0)->nodeValue / 9525) . 'px;';
                }
            }

            // vertical position
            $elementWPPositionV = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing', 'positionV');
            if ($elementWPPositionV->length > 0) {
                $elementWPPosOffset = $elementWPPositionV->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing', 'posOffset');
                if ($elementWPPosOffset->length > 0) {
                    $this->css[$nodeClass] .= 'margin-top: ' . round((float)$elementWPPosOffset->item(0)->nodeValue / 9525) . 'px;';
                }
            }

            // border
            $elementALn = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'ln');
            if ($elementALn->length > 0) {
                // if no fill avoid adding the border
                $elementALnNoFill = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'noFill');
                if ($elementALnNoFill->length <= 0) {
                    // color
                    $borderColor = '#000000';
                    $elementSrgbClr = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'srgbClr');
                    if ($elementSrgbClr->length > 0) {
                        $borderColor = '#' . $elementSrgbClr->item(0)->getAttribute('val');
                    }
                    // style
                    $borderStyle = 'solid';
                    $elementPrstDash = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'prstDash');
                    if ($elementPrstDash->length > 0) {
                        switch ($elementPrstDash->item(0)->getAttribute('val')) {
                            case 'dash':
                            case 'dashDot':
                            case 'lgDash':
                            case 'lgDashDot':
                            case 'lgDashDotDot':
                            case 'sysDash':
                            case 'sysDashDot':
                            case 'sysDashDotDot':
                                $borderStyle ='dashed';
                                break;
                            case 'dot':
                            case 'sysDot':
                                $borderStyle ='dotted';
                                break;
                            case 'solid':
                                $borderStyle = 'solid';
                                break;
                            default:
                                $borderStyle = 'solid';
                                break;
                        }
                    }
                    // width
                    $borderWidth = 1;
                    if ($elementALn->item(0)->hasAttribute('w')) {
                        $borderWidth = round((float)$elementALn->item(0)->getAttribute('w') / 9525);
                    }

                    $this->css[$nodeClass] .= 'border: ' . $borderWidth . 'px ' . $borderStyle . ' ' . $borderColor . ';';
                }
            }

            // text wrap
            if ($childNode->childNodes->item(0)->tagName == 'wp:inline') {
                // inline tag
                $this->css[$nodeClass] .= 'display: inline;';
            } else {
                // anchor tag
                
                // wrapSquare
                $elementWrapSquare = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing', 'wrapSquare');
                if ($elementWrapSquare->length > 0) {
                    if ($alignMode === false) {
                        $this->css[$nodeClass] .= 'float: left;';
                    }
                }

                // wrapNone
                $elementWrapNone = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing', 'wrapNone');
                if ($elementWrapNone->length > 0) {
                    if ($alignMode === false) {
                        if ($childNode->childNodes->item(0)->hasAttribute('behindDoc')) {
                            $this->css[$nodeClass] .= 'position: absolute;';
                            // image is set as back
                            if ($childNode->childNodes->item(0)->getAttribute('behindDoc') == '1') {
                                $this->css[$nodeClass] .= 'z-index: -1;';
                            }
                        }
                    }
                }
            }

            // link
            $linkTag = false;
            $elementAHlinkClick = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'hlinkClick');
            if ($elementAHlinkClick->length > 0) {
                $targetLink = $this->getRelationshipContent($elementAHlinkClick->item(0)->getAttribute('r:id'));
                $this->html .= '<a href="' . $targetLink . '" target="_blank">';

                $linkTag = true;
            }

            $this->html .= '<' . $this->htmlPlugin->getTag('image') . ' class="' . $nodeClass . ' ' . ($this->htmlPlugin->getExtraClass('img')==null?'':$this->htmlPlugin->getExtraClass('img')) . '" src="' . $src . '" width="' . $width . '" height="' . $height . '">';

            if ($linkTag === true) {
                $this->html .= '</a>';
            }
        }

        /*
        if ($elementCChart) {
            // chart drawing
            $xpathDocumentXmlRels = new DOMXPath($this->documentXmlRelsDOM);
            $xpathDocumentXmlRels->registerNamespace('xmlns', 'http://schemas.openxmlformats.org/package/2006/relationships');
            $headerRelationship = $xpathDocumentXmlRels->query('//xmlns:Relationship[@Id="' . $elementCChart->getAttribute('r:id') . '"]');
            $chartContent = $this->docxStructure->getContent('word/' . $headerRelationship->item(0)->getAttribute('Target'));

            $xmlChart = new DOMDocument();
            $xmlChart->loadXML($chartContent);

            // add a div for the new chart
            $this->html .= '<div id="chart_' . $elementCChart->getAttribute('r:id') . '"></div>';

            // get chart type
            $xpathChart = new DOMXPath($xmlChart);
            $xpathChart->registerNamespace('c', 'http://schemas.openxmlformats.org/drawingml/2006/chart');
            $pieChartElement = $xpathChart->query('//c:pieChart');

            if ($pieChartElement->length > 0) {
                // get labels
                $labelsElements = $xpathChart->query('//c:cat//c:v');
                $labelsValues = array();
                if ($labelsElements->length > 0) {
                    foreach ($labelsElements as $labelsElement) {
                        $labelsValues[] = '"' . $labelsElement->textContent . '"';
                    }
                }

                // get values
                $valuesElements = $xpathChart->query('//c:val//c:v');
                $valuesValues = array();
                if ($valuesElements->length > 0) {
                    foreach ($valuesElements as $valuesElement) {
                        $valuesValues[] = $valuesElement->textContent;
                    }
                }

                $this->javascript .= '
                    <script>
                        chartDiv = document.getElementById("chart_'.$elementCChart->getAttribute('r:id').'");
                        Plotly.plot(
                            chartDiv, 
                            [{
                                values: ['.implode(',', $valuesValues).'],
                                labels: ['.implode(',', $labelsValues).'],
                                type: "pie"
                            }],
                            {
                                margin: { t: 0 },
                            },
                            {
                                displayModeBar: false
                            }
                        );
                    </script>
                ';
            }
        }*/
    }

    /**
     * Transform w:endnote tag
     *
     * @param DOMElement $childNode
     * @param String $nodeClass
     */
    protected function transformW_ENDNOTE($childNode, $nodeClass)
    {
        if (trim($childNode->nodeValue) != '') {
            $this->endnotesContent = '<span id="endnote-' . $childNode->getAttribute('w:id') . '">' . $this->endnotesIndex['PHPDOCX_ENDNOTEREFERENCE_' . $childNode->getAttribute('w:id')] . '</span> ';

            // handle child elements
            if ($childNode->hasChildNodes()) {
                $this->transformXml($childNode);
            }
        }
    }

    /**
     * Transform w:endnotereference tag
     *
     * @param DOMElement $childNode
     * @param String $nodeClass
     */
    protected function transformW_ENDNOTEREFERENCE($childNode, $nodeClass)
    {
        // if the reference already has a custom mark do not add the placeholder
        if (!$childNode->hasAttribute('w:customMarkFollows')) {
            $table = array('m' => 1000, 'cm' => 900, 'd' => 500, 'cd' => 400, 'c' => 100, 'xc' => 90, 'l' => 50, 'xl' => 40, 'x' => 10, 'ix' => 9, 'v' => 5, 'iv' => 4, 'i' => 1);
            $return = '';

            $endnotesIndex = count($this->endnotesIndex) + 1;
            while ($endnotesIndex > 0)  { 
                foreach ($table as $rom => $arb) {
                    if ($endnotesIndex >= $arb) { 
                        $endnotesIndex -= $arb; 
                        $return .= $rom; 
                        break; 
                    }
                }
            }

            $this->endnotesIndex['PHPDOCX_ENDNOTEREFERENCE_' . $childNode->getAttribute('w:id')] = $return;

            $this->html .= '<a href="#endnote-' . $childNode->getAttribute('w:id') . '">' . strtolower($return) . '</a> ';
        }
    }

    /**
     * Transform w:fldchar tag
     *
     * @param DOMElement $childNode
     * @param String $nodeClass
     */
    protected function transformW_FLDCHAR($childNode, $nodeClass)
    {
        if ($childNode->hasAttribute('w:fldCharType') && $childNode->getAttribute('w:fldCharType') == 'end') {
            if ($this->complexField['type'] == 'FORMCHECKBOX') {
                $this->complexField = null;
            }

            if ($this->complexField['type'] == 'FORMDROPDOWN') {
                $this->complexField = null;
            }

            if ($this->complexField['type'] == 'FORMTEXT') {
                $this->complexField = null;
            }

            if ($this->complexField['type'] == 'HYPERLINK') {
                // end complex field
                $this->html .= '</a>';

                // clear pending CLASS_COMPLEX_FIELD placeholders
                $this->html = str_replace('{{ CLASS_COMPLEX_FIELD }}', '', $this->html);

                $this->complexField = null;
            }

            if ($this->complexField['type'] == 'PAGEREF') {
                // end complex field
                $this->html .= '</a>';

                $this->complexField = null;
            }

            if ($this->complexField['type'] == 'TIME') {
                $this->complexField = null;
            }
        }
    }

    /**
     * Transform w:footnote tag
     *
     * @param DOMElement $childNode
     * @param String $nodeClass
     */
    protected function transformW_FOOTNOTE($childNode, $nodeClass)
    {
        if (trim($childNode->nodeValue) != '') {
            $this->footnotesContent = '<span id="footnote-' . $childNode->getAttribute('w:id') . '">' . $this->footnotesIndex['PHPDOCX_FOOTNOTEREFERENCE_' . $childNode->getAttribute('w:id')] . '</span> ';

            // handle child elements
            if ($childNode->hasChildNodes()) {
                $this->transformXml($childNode);
            }
        }
    }

    /**
     * Transform w:footnotereference tag
     *
     * @param DOMElement $childNode
     * @param String $nodeClass
     */
    protected function transformW_FOOTNOTEREFERENCE($childNode, $nodeClass)
    {
        // if the reference already has a custom mark do not add the placeholder
        if (!$childNode->hasAttribute('w:customMarkFollows')) {
            $this->footnotesIndex['PHPDOCX_FOOTNOTEREFERENCE_' . $childNode->getAttribute('w:id')] = (count($this->footnotesIndex) + 1);

            $this->html .= '<a href="#footnote-' . $childNode->getAttribute('w:id') . '">' . count($this->footnotesIndex) . '</a> ';
        }
    }

    /**
     * Transform w:hyperlink tag
     *
     * @param DOMElement $childNode
     * @param String $nodeClass
     */
    protected function transformW_HYPERLINK($childNode, $nodeClass)
    {
        $target = $this->getRelationshipContent($childNode->getAttribute('r:id'));

        $this->html .= '<' . $this->htmlPlugin->getTag('hyperlink') . ' class="'.$nodeClass.' ' . ($this->htmlPlugin->getExtraClass('hyperlink')==null?'':$this->htmlPlugin->getExtraClass('hyperlink')) . '" href="'.$target.'" target="_blank">';

        // handle child elements
        if ($childNode->hasChildNodes()) {
            $this->transformXml($childNode);
        }

        $this->html .= '</' . $this->htmlPlugin->getTag('hyperlink') . '>';
    }

    /**
     * Transform w:instrtext tag
     *
     * @param DOMElement $childNode
     * @param String $nodeClass
     */
    protected function transformW_INSTRTEXT($childNode, $nodeClass)
    {
        // get element type
        $contentComplexField = explode(' ', ltrim($childNode->nodeValue));
        if (is_array($contentComplexField) && isset($contentComplexField[0])) {
            if ($contentComplexField[0] == 'FORMCHECKBOX') {
                // get the parent w:p to know if the checkbox is enabled or not
                $nodePInstrText = $childNode->parentNode->parentNode;
                $xpathPInstrText = new \DOMXPath($nodePInstrText->ownerDocument);
                $xpathPInstrText->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                $defaultNodes = $xpathPInstrText->query('//w:checkBox/w:default', $nodePInstrText);
                if ($defaultNodes->length > 0) {
                    if ($defaultNodes->item(0)->hasAttribute('w:val') && $defaultNodes->item(0)->getAttribute('w:val') == '1') {
                        $this->html .= '<input type="checkbox" checked>';
                    } else {
                        $this->html .= '<input type="checkbox">';
                    }
                } else {
                    $this->html .= '<input type="checkbox">';
                }

                $this->complexField = array('type' => 'FORMCHECKBOX');
            }

            if ($contentComplexField[0] == 'FORMDROPDOWN') {
                $this->html .= '<select class="{{ CLASS_COMPLEX_FIELD }}">';

                // get and add the select items
                $nodePInstrText = $childNode->parentNode->parentNode;
                $xpathPInstrText = new \DOMXPath($nodePInstrText->ownerDocument);
                $xpathPInstrText->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                $listEntryNodes = $xpathPInstrText->query('//w:ddList/w:listEntry', $nodePInstrText);

                foreach ($listEntryNodes as $listEntryNode) {
                    $this->html .= '<option value="'.$listEntryNode->getAttribute('w:val').'">'.$listEntryNode->getAttribute('w:val').'</option>';
                }

                $this->html .= '</select>';

                $this->complexField = array('type' => 'FORMDROPDOWN');
            }

            if ($contentComplexField[0] == 'FORMTEXT') {
                $this->html .= '<input class="{{ CLASS_COMPLEX_FIELD }}" type="text" value="{{ VALUE_COMPLEX_FIELD }}">';

                $this->complexField = array('type' => 'FORMTEXT');
            }

            if ($contentComplexField[0] == 'HYPERLINK') {
                $this->html .= '<a class="{{ CLASS_COMPLEX_FIELD }}" href="'.str_replace(array('&quot;', '"'), '', $contentComplexField[1]).'" target="_blank">';

                $this->complexField = array('type' => 'HYPERLINK');
            }

            if ($contentComplexField[0] == 'PAGEREF') {
                $this->html .= '<a class="{{ CLASS_COMPLEX_FIELD }}" href="#'.$contentComplexField[1].'">';

                $this->complexField = array('type' => 'PAGEREF');
            }

            if ($contentComplexField[0] == 'TIME') {
                // remove TIME \@ values and get the date
                array_shift($contentComplexField);
                array_shift($contentComplexField);

                // transform OOXML date format to PHP format
                // join the content of the complex field
                $date = join(' ', $contentComplexField);
                // split by symbol
                $dateElements = preg_split('/(\w+)/', $date, -1, PREG_SPLIT_DELIM_CAPTURE);
                // iterate each content to transform the date
                $dateTransformed = '';
                foreach ($dateElements as $dateElement) {
                    switch ($dateElement) {
                        case 'yyyy':
                            $dateTransformed .= date('Y');
                            break;
                        case 'yy':
                            $dateTransformed .= date('Y');
                            break;
                        case 'MMMM':
                            $dateTransformed .= date('F');
                            break;
                        case 'MM':
                            $dateTransformed .= date('m');
                            break;
                        case 'dd':
                            $dateTransformed .= date('d');
                            break;
                        case 'H':
                            $dateTransformed .= date('H');
                            break;
                        case 'mm':
                            $dateTransformed .= date('i');
                            break;
                        case 'ss':
                            $dateTransformed .= date('s');
                            break;
                        default:
                            // remove extra characters from the DATE
                            $dateElementTransformed = str_replace(array('"', '\''), '', $dateElement);
                            $dateTransformed .= $dateElementTransformed;
                    }
                };

                $this->html .= '<span>' . $dateTransformed . '</span>';

                $this->complexField = array('type' => 'TIME');
            }
        }
    }

    /**
     * Transform w:p tag
     *
     * @param DOMElement $childNode
     * @param String $nodeClass
     */
    protected function transformW_P($childNode, $nodeClass)
    {
        // if it's an internal section avoid adding the paragraph
        $sectPrTag = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'sectPr');
        if ($sectPrTag->length > 0) {
            $this->transformXml($childNode);
            return;
        }

        // handle tag
        
        // default element
        $elementTag = $this->htmlPlugin->getTag('paragraph');

        // heading tag
        $outlineLvlTag = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'outlineLvl');
        if ($outlineLvlTag->length > 0 && $outlineLvlTag->item(0)->hasAttribute('w:val')) {
            $elementTag = $this->htmlPlugin->getTag('heading') . ((int)$outlineLvlTag->item(0)->getAttribute('w:val') + 1);
        }

        // numbering tag
        $numPrTag = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'numPr');
        if ($numPrTag->length > 0) {
            // get w:numId to know the ID of the list
            $numIdTag = $numPrTag->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'numId');
            if ($numIdTag->length > 0 && $numIdTag->item(0)->hasAttribute('w:val') && $numIdTag->item(0)->getAttribute('w:val') != '') {
                // handle start list number
                if (!isset($this->listStartValues[$numIdTag->item(0)->getAttribute('w:val')])) {
                    $this->listStartValues[$numIdTag->item(0)->getAttribute('w:val')] = array();
                }
                $numberingLevel = (int)$numPrTag->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'ilvl')->item(0)->getAttribute('w:val');
                // handle start list number
                if (!isset($this->listStartValues[$numIdTag->item(0)->getAttribute('w:val')][$numberingLevel])) {
                    // check if there's a start value in the numbering style
                    $startValue = $this->getNumberingStart($numIdTag->item(0)->getAttribute('w:val'), $numberingLevel);
                    $this->listStartValues[$numIdTag->item(0)->getAttribute('w:val')][$numberingLevel] = $startValue;
                } else {
                    $this->listStartValues[$numIdTag->item(0)->getAttribute('w:val')][$numberingLevel] = $this->listStartValues[$numIdTag->item(0)->getAttribute('w:val')][$numberingLevel] + 1;
                }

                // get the numbering style based on the numbering ID and its level
                $numberingStyle = $this->getNumberingType($numIdTag->item(0)->getAttribute('w:val'), $numberingLevel);

                // check if the previous sibling is a numbering.
                // If there's no previous sibling or the ID or level aren't the same starts a new list
                $previousSiblingElement = $numPrTag->item(0)->parentNode->parentNode->previousSibling;
                $initNewList = false;
                if ($previousSiblingElement === null) {
                    $initNewList = true;
                }

                if ($previousSiblingElement !== null) {
                    $numPrPreviousSiblingElement = $previousSiblingElement->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'numPr');
                    if ($numPrPreviousSiblingElement->length > 0) {
                        // the previous element is a numbering
                        $numIdPreviousSiblingElementTag = $numPrPreviousSiblingElement->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'numId');
                        if ($numIdPreviousSiblingElementTag->length > 0 && $numIdPreviousSiblingElementTag->item(0)->hasAttribute('w:val') && $numIdPreviousSiblingElementTag->item(0)->getAttribute('w:val') != '') {
                            $numberingLevelPreviousSiblingElementTag = (int)$numPrPreviousSiblingElement->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'ilvl')->item(0)->getAttribute('w:val');
                            // get the numbering style based on the numbering ID and its level
                            $numberingStylePreviousSiblingElementTag = $this->getNumberingType($numIdPreviousSiblingElementTag->item(0)->getAttribute('w:val'), $numberingLevelPreviousSiblingElementTag);

                            if ($numIdPreviousSiblingElementTag->item(0)->getAttribute('w:val') != $numIdTag->item(0)->getAttribute('w:val')) {
                                $initNewList = true;
                            }

                            if ($numberingLevelPreviousSiblingElementTag < $numberingLevel) {
                                $initNewList = true;
                            }
                        }
                    } else {
                        // the previous element is not a numbering, then create a new list
                        $initNewList = true;
                    }
                }

                // create the new list
                if ($initNewList === true) {
                    if (in_array($numberingStyle, array('decimal', 'upperRoman', 'lowerRoman', 'upperLetter', 'lowerLetter'))) {
                        $tagTypeList = $this->htmlPlugin->getTag('orderedList');
                    } else {
                        $tagTypeList = $this->htmlPlugin->getTag('unorderedList');
                    }
                    switch ($numberingStyle) {
                        case 'bullet':
                            $this->css[$nodeClass] .= 'list-style-type: disc;';
                            break;
                        case 'decimal':
                            $this->css[$nodeClass] .= 'list-style-type: decimal;';
                            break;
                        case 'lowerLetter':
                            $this->css[$nodeClass] .= 'list-style-type: lower-alpha;';
                            break;
                        case 'lowerRoman':
                            $this->css[$nodeClass] .= 'list-style-type: lower-roman;';
                            break;
                        case 'upperLetter':
                            $this->css[$nodeClass] .= 'list-style-type: upper-alpha;';
                            break;
                        case 'upperRoman':
                            $this->css[$nodeClass] .= 'list-style-type: upper-roman;';
                            break;
                        default:
                            break;
                    }
                    $this->html .= '<'.$tagTypeList.' class="'.$nodeClass.' ' . ($this->htmlPlugin->getExtraClass('list')==null?'':$this->htmlPlugin->getExtraClass('list')) . '" ' . 'start="' . $this->listStartValues[$numIdTag->item(0)->getAttribute('w:val')][$numberingLevel] . '">';
                }

            }

            $elementTag = $this->htmlPlugin->getTag('itemList');
        }

        // paragraph style
        $pStyle = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'pStyle');
        if ($pStyle->length > 0) {
            $pStyleId = $pStyle->item(0)->getAttribute('w:val');
            if (!empty($pStyleId)) {
                $nodeClass .= ', paragraph_' . $pStyleId;
            }
        }

        // handle styles
        if ($childNode->hasChildNodes()) {
            if ($elementTag == $this->htmlPlugin->getTag('itemList')) {
                // numbering styles
                $numberingLevelTags = $this->getNumberingStyles($numIdTag->item(0)->getAttribute('w:val'), $numberingLevel);

                // pPr styles
                $this->css[$nodeClass] .= $this->addPprStyles($numberingLevelTags);

                $this->css[$nodeClass] .= 'text-indent: 0px; margin-left: 0px; margin-right: 0px;';

                // rPr styles
                $this->css[$nodeClass] .= $this->addRprStyles($numberingLevelTags);
            } else {
                $pPrTag = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'pPr');
                // check if there's a pPr tag
                if ($pPrTag->length > 0) {
                    // pPr styles
                    $this->css[$nodeClass] .= $this->addPprStyles($childNode);

                    // rPr styles
                    $this->css[$nodeClass] .= $this->addRprStyles($childNode);
                }
            }
        }

        // remove extra , before adding it to the HTML
        $nodeClassHTML = str_replace(',', '', $nodeClass);
        
        $this->html .= '<'.$elementTag.' class="'.$nodeClassHTML.' ' . ($this->htmlPlugin->getExtraClass('list')==null?'':$this->htmlPlugin->getExtraClass('paragraph')) . '">';

        // handle child elements
        if ($childNode->hasChildNodes()) {
            $this->transformXml($childNode);
        }

        // if there's no content add a blank space to avoid empty paragraphs
        if ($childNode->nodeValue === '') {
            $this->html .= '&nbsp';
            // remove styles if the paragraph is empty
            if ($elementTag == $this->htmlPlugin->getTag('paragraph')) {
                unset($this->css[$nodeClass]);
            }
        }

        $this->html .= '</'.$elementTag.'>';

        // numbering tag
        $numPrTag = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'numPr');
        if ($numPrTag->length > 0) {
            // check if the next sibling is a numbering.
            // If there's no next sibling or the ID isn't the same or level is lower close the list
            $nextSiblingElement = $numPrTag->item(0)->parentNode->parentNode->nextSibling;
            $closeNewList = false;
            if ($nextSiblingElement === null) {
                $closeNewList = true;
            }

            // sets how many list levels must be closed
            $iterationListClose = 1;

            if ($nextSiblingElement !== null) {
                $numPrNextSiblingElement = $nextSiblingElement->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'numPr');
                if ($numPrNextSiblingElement->length > 0) {
                    // the next element is a numbering
                    $numIdNextSiblingElementTag = $numPrNextSiblingElement->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'numId');
                    if ($numIdNextSiblingElementTag->length > 0 && $numIdNextSiblingElementTag->item(0)->hasAttribute('w:val') && $numIdNextSiblingElementTag->item(0)->getAttribute('w:val') != '') {
                        $numberingLevelNextSiblingElementTag = (int)$numPrNextSiblingElement->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'ilvl')->item(0)->getAttribute('w:val');
                        // get the numbering style based on the numbering ID and its level
                        $numberingStyleNextSiblingElementTag = $this->getNumberingType($numIdNextSiblingElementTag->item(0)->getAttribute('w:val'), $numberingLevelNextSiblingElementTag);

                        // handle close list levels
                        if ($numberingLevel > 0 && $numIdNextSiblingElementTag->item(0)->getAttribute('w:val') != $numIdTag->item(0)->getAttribute('w:val')) {
                            $closeNewList = true;
                            $iterationListClose += $numberingLevel;
                        }

                        if ($numIdNextSiblingElementTag->item(0)->getAttribute('w:val') != $numIdTag->item(0)->getAttribute('w:val')) {
                            $closeNewList = true;
                        }

                        if ($numberingLevelNextSiblingElementTag < $numberingLevel) {
                            $closeNewList = true;
                            if ($numberingLevel > 1) {
                                $iterationListClose = $numberingLevel - $numberingLevelNextSiblingElementTag;
                            }
                        }
                    }
                } else {
                    // the next element is not a numbering, then close the list
                    $closeNewList = true;

                    // handle close list levels
                    if ($numberingLevel > 0) {
                        $iterationListClose += $numberingLevel;
                    }
                }
            }

            // get the numbering style based on the numbering ID and its level
            $numberingStyle = $this->getNumberingType($numIdTag->item(0)->getAttribute('w:val'), $numberingLevel);
            if (in_array($numberingStyle, array('decimal', 'upperRoman', 'lowerRoman', 'upperLetter', 'lowerLetter'))) {
                        $tagTypeList = $this->htmlPlugin->getTag('orderedList');
            } else {
                $tagTypeList = $this->htmlPlugin->getTag('unorderedList');
            }
            if ($closeNewList === true) {
                unset($this->listStartValues[$numIdTag->item(0)->getAttribute('w:val')]);
                for ($iClose = 0; $iClose < $iterationListClose; $iClose++) { 
                    $this->html .= '</'.$tagTypeList.'>';
                }
            }
        }
    }

    /**
     * Transform w:r tag
     *
     * @param DOMElement $childNode
     * @param String $nodeClass
     */
    protected function transformW_R($childNode, $nodeClass)
    {
        // default element
        $elementTag = $this->htmlPlugin->getTag('span');

        // sup or sub element
        $vertAlignTag = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'vertAlign');
        if ($vertAlignTag->length > 0) {
            if ($vertAlignTag->item(0)->getAttribute('w:val') == 'superscript') {
                $elementTag = $this->htmlPlugin->getTag('superscript');
            } elseif ($vertAlignTag->item(0)->getAttribute('w:val') == 'subscript') {
                $elementTag = $this->htmlPlugin->getTag('subscript');
            }
        }

        // bidi element
        $bidiTag = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'bidi');
        if ($bidiTag->length > 0 && $bidiTag->item(0)->getAttribute('w:val') == 'on') {
            $elementTag = $this->htmlPlugin->getTag('bidi');
        }

        // character style
        $rStyle = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'rStyle');
        if ($rStyle->length > 0) {
            $rStyleId = $rStyle->item(0)->getAttribute('w:val');
            if (!empty($rStyleId)) {
                $nodeClass .= ', character_' . $rStyleId;
            }
        }

        // handle styles
        if ($childNode->hasChildNodes()) {
            // rPr styles
            $this->css[$nodeClass] .= $this->addRprStyles($childNode);
        }

        // if it's a text in a complex field, reuse the CSS class in the complex tag.
        // Use a CSS only if there's a w:t in it to avoid other complex field tags
        if ($this->complexField !== null) {
            $tTag = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 't');
            if ($tTag->length > 0) {
                $this->html = str_replace('{{ CLASS_COMPLEX_FIELD }}', $nodeClass, $this->html);
            }
        }

        // remove extra , before adding it to the HTML
        $nodeClassHTML = str_replace(',', '', $nodeClass);
        
        $this->html .= '<'.$elementTag.' class="'.$nodeClassHTML.' ' . ($this->htmlPlugin->getExtraClass('span')==null?'':$this->htmlPlugin->getExtraClass('span')) . '">';

        // get endnote contents if any exist. This avoid adding the endnote content out of the <p> tag
        if ($this->endnotesContent != null) {
            $this->html .= $this->endnotesContent;
            $this->endnotesContent = null;
        }

        // get footnote contents if any exist. This avoid adding the footnote content out of the <p> tag
        if ($this->footnotesContent != null) {
            $this->html .= $this->footnotesContent;
            $this->footnotesContent = null;
        }

        // get comment contents if any exist. This avoid adding the comment content out of the <p> tag
        if ($this->commentsContent != null) {
            $this->html .= $this->commentsContent;
            $this->commentsContent = null;
        }

        // handle child elements
        if ($childNode->hasChildNodes()) {
            $this->transformXml($childNode);
        }

        $this->html .= '</'.$elementTag.'>';
    }

    /**
     * Transform w:sectpr tag
     *
     * @param DOMElement $childNode
     * @param String $nodeClass
     */
    protected function transformW_SECTPR($childNode, $nodeClass)
    {
        // keep headers and footers to be added to the section
        $headerContentSection = '';
        $footerContentSection = '';

        // parse sectPr tags and add the CSS values to the current section CSS class
        $sectionCSS = '';
        foreach ($childNode->childNodes as $childNodesSection) {
            switch ($childNodesSection->nodeName) {
                case 'w:headerReference':
                    $target = $this->getRelationshipContent($childNodesSection->getAttribute('r:id'));

                    $headerContentSection = $this->headersContent['word/' . $target];
                    break;
                case 'w:footerReference':
                    $target = $this->getRelationshipContent($childNodesSection->getAttribute('r:id'));

                    $footerContentSection = $this->footersContent['word/' . $target];
                    break;
                case 'w:pgBorders':
                    foreach ($childNodesSection->childNodes as $childNodesSectionBorder) {
                        // iterate each border
                        $borderPosition = explode(':', $childNodesSectionBorder->nodeName);
                        if (isset($borderPosition[1])) {
                            // add outline as option
                            if ($childNodesSectionBorder->hasAttribute('w:color')) {
                                $this->css['body'] .= 'border-' . $borderPosition[1] . '-color: #' . $this->htmlPlugin->transformColors($childNodesSectionBorder->getAttribute('w:color')) . ';';
                            }
                            if ($childNodesSectionBorder->hasAttribute('w:space')) {
                                if (is_numeric($childNodesSectionBorder->getAttribute('w:val'))) {
                                    $this->css['body'] .= 'padding-' . $borderPosition[1] . ': ' . $this->htmlPlugin->transformSizes($childNodesSectionBorder->getAttribute('w:val'), 'pts') . ';';
                                }
                            }
                            if ($childNodesSectionBorder->hasAttribute('w:sz')) {
                                $this->css['body'] .= 'border-' . $borderPosition[1] . '-width: ' . $this->htmlPlugin->transformSizes($childNodesSectionBorder->getAttribute('w:sz'), 'eights') . ';';
                            }
                            if ($childNodesSectionBorder->hasAttribute('w:val')) {
                                switch ($childNodesSectionBorder->getAttribute('w:val')) {
                                    case 'dashed':
                                        $borderStyle ='dashed';
                                        break;
                                    case 'dotted':
                                        $borderStyle ='dotted';
                                        break;
                                    case 'double':
                                        $borderStyle ='double';
                                        break;
                                    case 'nil':
                                    case 'none':
                                        $borderStyle = 'none';
                                        break;
                                    case 'single':
                                        $borderStyle = 'solid';
                                        break;
                                    default:
                                        $borderStyle = 'solid';
                                        break;
                                }
                                $this->css['body'] .= 'border-style: ' . $borderStyle . ';';
                            }
                        }
                    }
                    break;
                case 'w:pgMar':
                    if ($childNodesSection->hasAttribute('w:bottom')) {
                        $wBottom = $this->htmlPlugin->transformSizes($childNodesSection->getAttribute('w:bottom'), 'twips');
                        if ($wBottom < 0) {
                            $wBottom = 0;
                        }
                        $sectionCSS .= 'margin-bottom: ' . $wBottom . ';';
                    }
                    if ($childNodesSection->hasAttribute('w:left')) {
                        $wLeft = $this->htmlPlugin->transformSizes($childNodesSection->getAttribute('w:left'), 'twips');
                        if ($wLeft < 0) {
                            $wLeft = 0;
                        }
                        $sectionCSS .= 'margin-left: ' . $wLeft . ';';
                    }
                    if ($childNodesSection->hasAttribute('w:right')) {
                        $wRight = $this->htmlPlugin->transformSizes($childNodesSection->getAttribute('w:right'), 'twips');
                        if ($wRight < 0) {
                            $wRight = 0;
                        }
                        $sectionCSS .= 'margin-right: ' . $wRight . ';';
                    }
                    if ($childNodesSection->hasAttribute('w:top')) {
                        $wTop = $this->htmlPlugin->transformSizes($childNodesSection->getAttribute('w:top'), 'twips');
                        if ($wTop < 0) {
                            $wTop = 0;
                        }
                        $sectionCSS .= 'margin-top: ' . $wTop . ';';
                    }
                    break;
                case 'w:pgSz':
                    if ($childNodesSection->hasAttribute('w:h')) {
                        //$sectionCSS .= 'max-height: ' . $this->htmlPlugin->transformSizes($childNodesSection->getAttribute('w:h'), 'twips') . ';';
                    }
                    if ($childNodesSection->hasAttribute('w:w')) {
                        $sectionCSS .= 'max-width: ' . $this->htmlPlugin->transformSizes($childNodesSection->getAttribute('w:w'), 'twips') . ';';
                        $this->css['body'] .= 'max-width: ' . $this->htmlPlugin->transformSizes($childNodesSection->getAttribute('w:w'), 'twips') . ';';
                    }
                    break;
                default:
                    break;
            }
        }
        $this->css[$this->currentSectionClassName] = $sectionCSS;

        // add headers and footers
        if (!empty($headerContentSection)) {
            // remove the headaer placeholder to add the header contents to the correct place
            $this->html = str_replace('__HEADERCONTENTSECTION__', $headerContentSection, $this->html);
        } else {
            // as there's no header contents, remove the placeholder
            $this->html = str_replace('__HEADERCONTENTSECTION__', '', $this->html);
        }
        if (!empty($footerContentSection)) {
            $this->html .= $footerContentSection;
        }

        // close current section
        $this->html .= '</' . $this->htmlPlugin->getTag('section') . '>';

        // if it's an internal section, create a new section tag
        if ($childNode->parentNode->parentNode->tagName == 'w:p') {
            // keep the section class name to be used when a section tag is found when parsing the document
            $this->currentSectionClassName = $this->htmlPlugin->generateClassName();
            $this->html .= '<' . $this->htmlPlugin->getTag('section') . ' class="' . $this->currentSectionClassName . ' ' . ($this->htmlPlugin->getExtraClass('section')==null?'':$this->htmlPlugin->getExtraClass('section')) . '">__HEADERCONTENTSECTION__';
        }
    }

    /**
     * Transform w:sdt tag
     *
     * @param DOMElement $childNode
     * @param String $nodeClass
     */
    protected function transformW_SDT($childNode, $nodeClass)
    {
        // get the structure type
        $lastChildSdtPr = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'sdtPr')->item(0)->lastChild;

        switch ($lastChildSdtPr->tagName) {
            case 'w:comboBox';
                $this->html .= '<p><' . $this->htmlPlugin->getTag('comboBox') . '>';

                // get first item value from w:stdContent tag
                $sdtContentNode = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'sdtContent')->item(0);
                if ($sdtContentNode) {
                    $this->html .= '<' . $this->htmlPlugin->getTag('comboBoxItem') . '>' . $sdtContentNode->textContent . '</' . $this->htmlPlugin->getTag('comboBoxItem') . '>';
                }

                foreach ($lastChildSdtPr->childNodes as $listItem) {
                    $this->html .= '<' . $this->htmlPlugin->getTag('comboBoxItem') . ' value="'.$listItem->getAttribute('w:value').'">'.$listItem->getAttribute('w:displayText').'</' . $this->htmlPlugin->getTag('comboBoxItem') . '>';
                }
                 $this->html .= '</' . $this->htmlPlugin->getTag('comboBox') . '></p>';
                break;
            case 'w:date';
                // get text value from w:stdContent tag
                $sdtContentNode = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'sdtContent')->item(0);
                
                $this->html .= '<p><input type="date" placeholder="'.$sdtContentNode->textContent.'"></p>';
                break;
            default:
                break;
        }
    }

    /**
     * Transform w:tbl tag
     *
     * @param DOMElement $childNode
     * @param String $nodeClass
     */
    protected function transformW_TBL($childNode, $nodeClass)
    {
        $borderStylesTable = '';
        $cellPadding = '';
        $borderInsideStylesTable = '';

        // table styles tblStyle
        $elementsWTblprTblStyle = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tblStyle');
        if ($elementsWTblprTblStyle->length > 0) {
            $tableStyleId = $elementsWTblprTblStyle->item(0)->getAttribute('w:val');
            if (!empty($tableStyleId)) {
                // get table styles
                $xpathStyles = new \DOMXPath($this->stylesDocxDOM);
                $xpathStyles->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                $stylesTbl = $xpathStyles->query('//w:style[@w:styleId="' . $tableStyleId . '"]');
                if ($stylesTbl->length > 0) {
                    $stylesTable = $this->getTableStyles($stylesTbl->item(0));
                    $this->css[$nodeClass] .= $stylesTable['tableStyles'];

                    // add extra properties replacing pending __CLASSNAMETABLE__ placeholders by the class name
                    if (isset($stylesTable['firstLastStyles']) && is_array($stylesTable['firstLastStyles'])) {
                        foreach ($stylesTable['firstLastStyles'] as $keyFirstLastStyles => $valueFirstLastStyles) {
                            $this->css[str_replace('__CLASSNAMETABLE__', $nodeClass, $keyFirstLastStyles)] .= $valueFirstLastStyles;
                        }
                    }

                    $borderStylesTable .= $stylesTable['borderStylesTable'];
                    $cellPadding .= $stylesTable['cellPadding'];
                    $borderInsideStylesTable .= $stylesTable['borderInsideStylesTable'];
                }
            }
        }

        // table properties
        $elementWTblPr = $childNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tblPr');
        if ($elementWTblPr->length > 0) {
            $stylesTable = $this->getTableStyles($childNode);
            $this->css[$nodeClass] .= $stylesTable['tableStyles'];

            // add extra properties replacing pending __CLASSNAMETABLE__ placeholders by the class name
            if (isset($stylesTable['firstLastStyles']) && is_array($stylesTable['firstLastStyles'])) {
                foreach ($stylesTable['firstLastStyles'] as $keyFirstLastStyles => $valueFirstLastStyles) {
                    $this->css[str_replace('__CLASSNAMETABLE__', $nodeClass, $keyFirstLastStyles)] .= $valueFirstLastStyles;
                }
            }

            $borderStylesTable .= $stylesTable['borderStylesTable'];
            $cellPadding .= $stylesTable['cellPadding'];
            $borderInsideStylesTable .= $stylesTable['borderInsideStylesTable'];
        }

        // default values
        $this->css[$nodeClass] .= 'border-spacing: 0; border-collapse: collapse;';

        $this->html .= '<' . $this->htmlPlugin->getTag('table') . ' class="' . $nodeClass . ' ' . ($this->htmlPlugin->getExtraClass('table')==null?'':$this->htmlPlugin->getExtraClass('table')) . '">';

        // rows
        $xpathDOMXPathWTblTr = new \DOMXPath($childNode->ownerDocument);
        $xpathDOMXPathWTblTr->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $elementsWTblTr = $xpathDOMXPathWTblTr->query('w:tr', $childNode);

        // needed to set tblStylePr by tr position
        $indexTr = 0;

        // keep rowspan values
        $rowspan = array();
        foreach ($elementsWTblTr as $elementWTblTr) {
            // row class
            $nodeTrClass = $this->htmlPlugin->generateClassName();
            $this->css[$nodeTrClass] = '';
            $this->html .= '<' . $this->htmlPlugin->getTag('tr') . ' class="' . $nodeTrClass . ' ' . ($this->htmlPlugin->getExtraClass('tr')==null?'':$this->htmlPlugin->getExtraClass('tr')) . '">';

            // row styles tblStylePr
            $elementWTblTrPr = $elementWTblTr->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'trPr');
            if ($elementWTblTrPr->length > 0) {
                // height
                $elementWTblTrHeight = $elementWTblTr->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'trHeight');
                if ($elementWTblTrHeight->length > 0) {
                    $this->css[$nodeTrClass] = 'height: ' . $this->htmlPlugin->transformSizes($elementWTblTrHeight->item(0)->getAttribute('w:val'), 'twips') . ';';
                }
            }

            // cells   
            $xpathDOMXPathWTblTrTc = new \DOMXPath($elementWTblTr->ownerDocument);
            $xpathDOMXPathWTblTrTc->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $elementsWTblTrTc = $xpathDOMXPathWTblTrTc->query('w:tc|w:sdt', $elementWTblTr);
            // needed to set tblStylePr by td position
            $indexTd = 0;
            // colspan
            $colspan = 1;
            foreach ($elementsWTblTrTc as $elementWTblTrTc) {
                // cell class
                $nodeTdClass = $this->htmlPlugin->generateClassName();
                $this->css[$nodeTdClass] = '';

                // avoid adding td if there're pending colspans
                if ($colspan > 1) {
                    $colspan--;
                }

                // colspan property
                $elementWTblTrTcTcprGridSpan = $elementWTblTrTc->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'gridSpan');
                if ($elementWTblTrTcTcprGridSpan->length > 0) {
                    $colspan = $elementWTblTrTcTcprGridSpan->item(0)->getAttribute('w:val');
                }

                // rowspan property
                $elementWTblTrTcTcprVMerge = $elementWTblTrTc->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'vMerge');
                $rowspanValue = null;
                if ($elementWTblTrTcTcprVMerge->length > 0) {
                    $rowspanValue = $elementWTblTrTcTcprVMerge->item(0)->getAttribute('w:val');

                    if ($rowspanValue == 'restart') {
                        $rowspan['__ROWSPANVALUE_'.$indexTd.'__'] = 1;
                    } elseif ($rowspanValue == 'continue') {
                        $rowspan['__ROWSPANVALUE_'.$indexTd.'__'] += 1;

                        // MS Word avoid adding tc tags when there're colspan-
                        // Sum the current indexTD to colspan and remove one value as it's added later
                        if ($colspan > 1) {
                            $indexTd = $indexTd + $colspan - 1;
                        }

                        $indexTd++;
                        continue;
                    }
                }

                // add td tag
                $this->html .= '<' . $this->htmlPlugin->getTag('tc') . ' class="' . $nodeTdClass . ' ' . ($this->htmlPlugin->getExtraClass('td')==null?'':$this->htmlPlugin->getExtraClass('td')) . '" ';

                // add colspan value
                if ($colspan > 1) {
                    $this->html .= 'colspan="' . $colspan . '" ';
                }

                // add rowspan value
                if ($rowspanValue !== null && $rowspanValue == 'restart') {
                    $this->html .= 'rowspan="__ROWSPANVALUE_' . $indexTd . '__"';
                }

                // add td end >
                $this->html .= '>';

                // default values
                $this->css[$nodeTdClass] .= 'vertical-align: top;';

                // tr styles
                if (!empty($this->css[$nodeTrClass])) {
                    $this->css[$nodeTdClass] .= $this->css[$nodeTrClass];
                }

                // table border styles
                if (!empty($borderStylesTable)) {
                    $this->css[$nodeTdClass] .= $borderStylesTable;
                }

                // inside border styles
                if (!empty($borderInsideStylesTable)) {
                    $this->css[$nodeTdClass] .= $borderInsideStylesTable;
                }

                //  table padding properties
                if (!empty($cellPadding)) {
                    $this->css[$nodeTdClass] .= $cellPadding;
                }
                
                // cell properties
                $elementWTblTrTcTcpr = $elementWTblTrTc->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tcPr');
                if ($elementWTblTrTcTcpr->length > 0) {
                    // width
                    $elementWTblTrTcTcprTcW = $elementWTblTrTcTcpr->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tcW');
                    if ($elementWTblTrTcTcprTcW->length > 0) {
                        $this->css[$nodeTdClass] .= 'width: ' . $this->htmlPlugin->transformSizes($elementWTblTrTcTcprTcW->item(0)->getAttribute('w:w'), 'twips') . ';';
                    }

                    // borders
                    $borderCells = $this->getCellStyles($elementWTblTrTc);
                    $this->css[$nodeTdClass] .= $borderCells['borderStylesCell'];

                    // background
                    $elementWTblTrTcTcprShd = $elementWTblTrTcTcpr->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'shd');
                    if ($elementWTblTrTcTcprShd->length > 0) {
                        if ($elementWTblTrTcTcprShd->item(0)->hasAttribute('w:fill')) {
                            $this->css[$nodeTdClass] .= 'background-color: #' . $elementWTblTrTcTcprShd->item(0)->getAttribute('w:fill') . ';';
                        }
                    }

                    // paddings
                    $elementWTblTrTcTcprTcMar = $elementWTblTrTcTcpr->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tcMar');
                    if ($elementWTblTrTcTcprTcMar->length > 0) {
                        // top
                        $elementWTblTrTcTcprTcMarTop = $elementWTblTrTcTcprTcMar->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'top');
                        if ($elementWTblTrTcTcprTcMarTop->length > 0) {
                            $this->css[$nodeTdClass] .= 'padding-top: ' . $this->htmlPlugin->transformSizes($elementWTblTrTcTcprTcMarTop->item(0)->getAttribute('w:w'), 'twips') . ';';
                        }
                        // right
                        $elementWTblTrTcTcprTcMarRight = $elementWTblTrTcTcprTcMar->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'right');
                        if ($elementWTblTrTcTcprTcMarRight->length > 0) {
                            $this->css[$nodeTdClass] .= 'padding-right: ' . $this->htmlPlugin->transformSizes($elementWTblTrTcTcprTcMarRight->item(0)->getAttribute('w:w'), 'twips') . ';';
                        }
                        // bottom
                        $elementWTblTrTcTcprTcMarBottom = $elementWTblTrTcTcprTcMar->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'bottom');
                        if ($elementWTblTrTcTcprTcMarBottom->length > 0) {
                            $this->css[$nodeTdClass] .= 'padding-bottom: ' . $this->htmlPlugin->transformSizes($elementWTblTrTcTcprTcMarBottom->item(0)->getAttribute('w:w'), 'twips') . ';';
                        }
                        // left
                        $elementWTblTrTcTcprTcMarLeft = $elementWTblTrTcTcprTcMar->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'left');
                        if ($elementWTblTrTcTcprTcMarLeft->length > 0) {
                            $this->css[$nodeTdClass] .= 'padding-left: ' . $this->htmlPlugin->transformSizes($elementWTblTrTcTcprTcMarLeft->item(0)->getAttribute('w:w'), 'twips') . ';';
                        }
                    }

                    // vertical align
                    $elementWTblTrTcTcprVAlign = $elementWTblTrTcTcpr->item(0)->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'vAlign');
                    if ($elementWTblTrTcTcprVAlign->length > 0) {
                        $vAlign = 'middle';
                        switch ($elementWTblTrTcTcprVAlign->item(0)->getAttribute('w:val')) {
                            case 'top':
                                $vAlign = 'top';
                                break;
                            case 'bottom':
                                $vAlign = 'bottom';
                                break;
                            case 'both':
                            case 'center':
                                $vAlign = 'middle';
                                break;
                            default:
                                $vAlign = 'top';
                                break;
                        }

                        $this->css[$nodeTdClass] .= 'vertical-align: ' . $vAlign . ';';
                    }
                }

                // handle contents
                if ($elementWTblTrTc->hasChildNodes()) {
                    $this->transformXml($elementWTblTrTc);
                }

                $this->html .= '</' . $this->htmlPlugin->getTag('tc') . '>';

                // MS Word avoid adding tc tags when there're colspan-
                // Sum the current indexTD to colspan and remove one value as it's added later
                if ($colspan > 1) {
                    $indexTd = $indexTd + $colspan - 1;
                }

                // increment td index
                $indexTd++;
            }

            $this->html .= '</' . $this->htmlPlugin->getTag('tr') . '>';

            // increment tr index
            $indexTr++;
        }

        // replace ROWSPAN_ placeholders by their values
        if (is_array($rowspan) && count($rowspan) > 0) {
            foreach ($rowspan as $keyRowspan => $valueRowspan) {
                $this->html = str_replace($keyRowspan, $valueRowspan, $this->html);
            }
        }

        $this->html .= '</' . $this->htmlPlugin->getTag('table') . '>';
    }

    /**
     * Transform w:t tag
     *
     * @param DOMElement $childNode
     * @param String $nodeClass
     */
    protected function transformW_T($childNode, $nodeClass)
    {
        // avoid adding complex field text contents such as date with complex field TIME
        if ($this->complexField !== null && $this->complexField['type'] == 'TIME') {
            return;
        }

        if ($this->complexField !== null && $this->complexField['type'] == 'FORMTEXT' && !empty(trim($childNode->nodeValue))) {
            $this->html = str_replace('{{ VALUE_COMPLEX_FIELD }}', $childNode->nodeValue, $this->html);
            return;
        }

        // fix < and > values
        $nodeValue = str_replace(array('<', '>'), array('&lt;', '&gt;'), $childNode->nodeValue);
        
        $this->html .= $nodeValue;
    }

}
