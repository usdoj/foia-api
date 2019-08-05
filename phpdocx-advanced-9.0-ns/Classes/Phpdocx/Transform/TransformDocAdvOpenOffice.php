<?php
namespace Phpdocx\Transform;

use Phpdocx\Clean\CleanTemp;
use Phpdocx\Create\CreateDocx;
use Phpdocx\Logger\PhpdocxLogger;
use Phpdocx\Parse\RepairPDF;
use Phpdocx\Transform\TransformDocAdv;
use Phpdocx\Utilities\PhpdocxUtilities;


/**
 * Transform DOCX to PDF, ODT, SXW, RTF, DOC, TXT, HTML or WIKI using OpenOffice
 *
 * @category   Phpdocx
 * @package    trasform
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @version    2019.01.31
 * @link       https://www.phpdocx.com
 */

require_once dirname(__FILE__) . '/TransformDocAdv.php';

class TransformDocAdvOpenOffice extends TransformDocAdv
{
    /**
     * Prepare docx before pdf transformation
     *
     * @access public
     * @param $source
     * @param $tempDir
     * @param $options
     * @return string
     */
    public function prepareDocx($source, $tempDir = null, $options)
    {
        if ($tempDir === null) {
            $tempDir = $this->getTempDirPath();
            $tempName = $tempDir . '/tempDocX_' . uniqid() . '.docx';
        } else {
            $tempName = $tempDir . '/tempDocX_' . uniqid() . '.docx';
        }
        copy($source, $tempName);

        return $tempName;
    }

    /**
     * Replace charts as images
     *
     * @access public
     * @param $source
     */
    public function replaceChartsWithImages($source){
        $sourceDocx = new \ZipArchive();
        $sourceDocx->open($source);

        // if jpgraph exists use it, instead use ezComponents
        if (file_exists(dirname(__FILE__) . '/../lib/jpgraph/')) {
            $image = new CreateChartImageJpgraph();
        } else {
            $image = new CreateChartImageEzComponents();
        }

        // get the images
        $image->getChartsDocx($source);
        $image->parseCharts();
        $listChartImages = $image->getListImages();
        if (!is_array($listChartImages)) {
            $listChartImages = array();
        }

        // parse de docx and add the images
        $contentTypesXML = $sourceDocx->getFromName('[Content_Types].xml');

        // get the document.xml.rels file from the DOCX
        $documentRelsXML = $sourceDocx->getFromName('word/_rels/document.xml.rels');
        $documentRelsDOM = new \SimpleXMLElement($documentRelsXML);
        $documentRelsDOM->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/package/2006/relationships');

        // get the document.xml file from the DOCX
        $documentXML = $sourceDocx->getFromName('word/document.xml');
        $documentDOM = new \SimpleXMLElement($documentXML);

        // get the chart elements of the DOM
        $contentTypesDOM = new \SimpleXMLElement($contentTypesXML);
        $contentTypesDOM->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/package/2006/content-types');
        $elementsCharts = $contentTypesDOM->xpath('ns:Override[@ContentType="application/vnd.openxmlformats-officedocument.drawingml.chart+xml"]');

        // as some nodes are removed, iterate the charts in reverse order
        //$elementsCharts = array_reverse($elementsCharts);

        // index of the image to be added to the ZIP
        $indexImage = 0;
        foreach ($elementsCharts as $value) {
            // get the attributes of the element
            $attributes = $value->attributes();

            // get the width and height and add them to the charts array
            // get the rId of the chart from the documentRels
            $relationshipChart = $documentRelsDOM->xpath('ns:Relationship[@Target="'.substr($attributes['PartName'], 6).'"]');
            $documentDOM->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
            $documentDOM->registerXPathNamespace('c', 'http://schemas.openxmlformats.org/drawingml/2006/chart');

            // get the a:graphicData element of the chart
            $elementAGraphicData = $documentDOM->xpath('//a:graphicData[c:chart[@r:id="'. $relationshipChart[0]->attributes()->Id . '"]]');
            $elementAGraphicData[0]['uri'] = 'http://schemas.openxmlformats.org/drawingml/2006/picture';

            //get and remove the c:chart child
            $elementAGraphicData[0]->registerXPathNamespace('c', 'http://schemas.openxmlformats.org/drawingml/2006/chart');
            $elementCChart = $elementAGraphicData[0]->xpath('//c:chart');
            //unset($elementCChart[0][0]);

            // remove the chart content keeping w:drawing tag
            $domElementAGraphicData = dom_import_simplexml($elementAGraphicData[0]);
            $picture = $this->getTemplateImage(uniqid(), $relationshipChart[0]->attributes()->Id);
            $pictureFragment = $domElementAGraphicData->ownerDocument->createDocumentFragment();
            $pictureFragment->appendXML($picture);
            $domElementAGraphicData->appendChild($pictureFragment);
            $sourceDocx->addFile($listChartImages[$indexImage], 'word/media/' . $listChartImages[$indexImage]);
             
            //Modify the Type attribute of document.xml.rels to http://schemas.openxmlformats.org/officeDocument/2006/relationships/image
            //and the Target to media/'.$listChartImages[$indexImage]
            $relsImage = $documentRelsDOM->xpath('//ns:Relationship[@Id="'. $relationshipChart[0]->attributes()->Id.'"]');
            $relsImage[0]['Type'] = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image';
            $relsImage[0]['Target'] = 'media/' . $listChartImages[$indexImage];
            
            $indexImage++; 
        }

        // save the modified document.xml.rels file
        $docXML = $documentDOM->asXML();
        $docXML = str_replace('<pic:pic xmlns:r="http://schemas.openxmlformats.org/package/2006/relationships" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">', '<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">', $docXML);
        $docXML = str_replace('<pic:pic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">', '<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">', $docXML);
        $docXML = str_replace('<pic:pic xmlns:r="http://schemas.openxmlformats.org/package/2006/relationships" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"', '<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">', $docXML);
        $sourceDocx->addFromString('word/document.xml', $docXML);
        
        // save the modified document.xml.rels file
        $relsDoc = $documentRelsDOM->asXML();
        $sourceDocx->addFromString('word/_rels/document.xml.rels', $relsDoc);
               
        // make sure that there is the associated content type "png"
        $position = strpos('Extension="png"', $contentTypesXML);
        if($position === false){
            $contentTypesXML = str_replace('</Types>',  '<Default Extension="png" ContentType="image/png"/></Types>', $contentTypesXML);
            $sourceDocx->addFromString('[Content_Types].xml', $contentTypesXML);
        }
        // close the zip
        $sourceDocx->close();

        // remove the generated images
        foreach ($listChartImages as $listChartImage) {
            unlink($listChartImage);
        }
    }

    /**
     * To add support of sys_get_temp_dir for PHP versions under 5.2.1
     *
     * @access protected
     * @return string
     */
    protected function getTempDirPath()
    {
        if ($this->phpdocxconfig['settings']['temp_path']) {
            return $this->phpdocxconfig['settings']['temp_path'];
        }
        if (!function_exists('sys_get_temp_dir')) {

            function sys_get_temp_dir()
            {
                if ($temp = getenv('TMP')) {
                    return $temp;
                }
                if ($temp = getenv('TEMP')) {
                    return $temp;
                }
                if ($temp = getenv('TMPDIR')) {
                    return $temp;
                }
                $temp = tempnam(__FILE__, '');
                if (file_exists($temp)) {
                    unlink($temp);
                    return dirname($temp);
                }
                return null;
            }

        } else {
            return sys_get_temp_dir();
        }
    }

    /**
     * 
     */
    protected function getTemplateImage($name, $id)
    {
        $templateImage = '<pic:pic xmlns:r="http://schemas.openxmlformats.org/package/2006/relationships" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">
                    <pic:nvPicPr>
                        <pic:cNvPr id="0" name="' . $name .'"/>
                        <pic:cNvPicPr/>
                    </pic:nvPicPr>
                    <pic:blipFill>
                        <a:blip r:embed="' . $id . '"/>
                        <a:stretch>
                            <a:fillRect/>
                        </a:stretch>
                    </pic:blipFill>
                    <pic:spPr>
                        <a:xfrm>
                            <a:off x="0" y="0"/>
                            <a:ext cx="4876800" cy="3657600"/>
                        </a:xfrm>
                        <a:prstGeom prst="rect">
                            <a:avLst/>
                        </a:prstGeom>
                    </pic:spPr>
                </pic:pic>';
        return $templateImage;
    }

    /**
     * Transform all documents supported by OpenOffice
     *
     * @access public
     * @param $source
     * @param $target
     * @param array $options :
     *   'debug' (bool) : false (default) or true. It shows debug information about the plugin conversion
     *   'homeFolder' (string) : set a custom home folder to be used for the conversions
     *   'method' (string) : 'direct' (default), 'script' ; 'direct' method uses passthru and 'script' uses a external script. If you're using Apache and 'direct' doesn't work use 'script'
     *   'odfconverter' (bool) : true (default) or false. Use odf-converter to preproccess documents
     *   'tempDir' (string) : uses a custom temp folder
     *   'version' (string) : 32-bit or 64-bit architecture. 32, 64 or null (default). If null autodetect
     * @return void
     */
    public function transformDocument($source, $target, $options = array())
    {
        $allowedExtensionsSource = array('doc', 'docx', 'html', 'odt', 'rtf', 'txt', 'xhtml');
        $allowedExtensionsTarget = array('doc', 'docx', 'html', 'odt', 'pdf', 'rtf', 'txt', 'xhtml');

        $filesExtensions = $this->checkSupportedExtension($source, $target, $allowedExtensionsSource, $allowedExtensionsTarget);

        // get the file info
        $sourceFileInfo = pathinfo($source);
        $sourceExtension = $sourceFileInfo['extension'];
        
        if (!isset($options['method'])) {
            $options['method'] = 'direct';
        }
        if (!isset($options['odfconverter'])) {
            $options['odfconverter'] = true;
        }
        if (!isset($options['debug'])) {
            $options['debug'] = false;
        }

        $tempDir = null;
        if (isset($options['tempDir'])) {
            $tempDir = $options['tempDir'];
        }

        $version = '64';
        if (isset($options['version'])) {
            $version = $options['version'];
        }

        if (isset($options['homeFolder'])) {
            $currentHomeFolder = getenv("HOME");
            putenv("HOME=" . $options['homeFolder']);
        }

        if ($sourceExtension == 'docx') {
            // set path to OdfConverter: 32-bit or 64-bit
            $odfconverterPath = '';
            // set outputstring for debugging
            $outputDebug = '';
            if (PHP_OS == 'Linux') {
                if (!$options['debug']) {
                    $outputDebug = ' > /dev/null 2>&1';
                }
                if ($version == '32') {
                    $odfconverterPath = '/../../../lib/OdfConverter/32/OdfConverter';
                } elseif ($version == '64') {
                    $odfconverterPath = '/../../../lib/OdfConverter/64/OdfConverter';
                } else {
                    // detect if 32bit or 64bit
                    if (PHP_INT_SIZE * 8 == 64) {
                        $odfconverterPath = '/../../../lib/OdfConverter/64/OdfConverter';
                    } else {
                        $odfconverterPath = '/../../../lib/OdfConverter/32/OdfConverter';
                    }
                }
            } elseif (substr(PHP_OS, 0, 3) == 'Win' || substr(PHP_OS, 0, 3) == 'WIN') {
                if (!$options['debug']) {
                    $outputDebug = ' > nul 2>&1';
                }
                $odfconverterPath = '/../../../lib/OdfConverter/Windows/OdfConverter.exe';
            }

            $newDocx = $this->prepareDocx($source, $tempDir, $options);

            if (file_exists(dirname(__FILE__) . '/CreateChartImage.php') && (file_exists(dirname(__FILE__) . '/../../../lib/jpgraph/') || file_exists(dirname(__FILE__) . '/../../../lib/ezcomponents'))) {
                $this->replaceChartsWithImages($newDocx);
            }

            if ($tempDir === null) {
                $tempDir = $this->getTempDirPath();
                $tempDoc = $tempDir . '/tempOdt_' . uniqid() . '.odt';
            } else {
                $tempDoc = $tempDir . '/tempOdt_' . uniqid() . '.odt';
            }

            if ($options['method'] == 'script') {
                passthru('php ' . dirname(__FILE__) . '/../../../lib/convert.php -s ' . $newDocx . ' -t ' . $tempDoc . ' -d ' . $docDestination . ' -o ' . $options['odfconverter'] . ' -p ' . $odfconverterPath . $outputDebug);
            } else {
                if ($extension != 'rtf' && $options['odfconverter']) {
                    passthru(dirname(__FILE__) . $odfconverterPath . ' /I ' . $newDocx . ' /O ' . $tempDoc . $outputDebug);
                } else {
                    copy($source, $tempDoc);
                }
                // How to start OpenOffice in headless mode: lib/openoffice/openoffice.org3/program/soffice -headless -accept="socket,host=127.0.0.1,port=8100;urp;" -nofirststartwizard;
                passthru('java -jar ' . dirname(__FILE__) . '/../../../lib/openoffice/jodconverter-2.2.2/lib/jodconverter-cli-2.2.2.jar ' . $tempDoc . ' ' . $docDestination . $outputDebug);
            }

            CleanTemp::clean($tempDoc);
        } else {
            if ($options['method'] == 'script') {
                passthru('php ' . dirname(__FILE__) . '/../../../lib/convert.php -s ' . $source . ' -d ' . $docDestination . $outputDebug);
            } else {
                // how to start OpenOffice in headless mode: lib/openoffice/openoffice.org3/program/soffice -headless -accept="socket,host=127.0.0.1,port=8100;urp;" -nofirststartwizard;
                passthru('java -jar ' . dirname(__FILE__) . '/../../../lib/openoffice/jodconverter-2.2.2/lib/jodconverter-cli-2.2.2.jar ' . $source . ' ' . $docDestination . $outputDebug);
            }
        }

        // restore the previous HOME value
        if (isset($options['homeFolder'])) {
            putenv("HOME=" . $currentHomeFolder);
        }
    }

}
