<?php
namespace Phpdocx\Utilities;
/**
 * This class offers some utilities to work with PDF documents
 * 
 * @category   Phpdocx
 * @package    utilities
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @version    2018.08.20
 * @link       https://www.phpdocx.com
 */
require_once dirname(__FILE__) . '/../Create/CreateDocx.php';
require_once dirname(__FILE__) . '/../Libs/TCPDF_lib.php';

class PdfUtilities
{
    /**
     * Removes pages in a PDF document
     * 
     * @access public
     * @param string $source Path to the PDF
     * @param string $target Path to the resulting PDF (a new file will be created per page)
     * @param array $options
     *        array 'pages' pages to be removed, none by default
     * @return void
     */
    public function removePagesPdf($source, $target, $options = array())
    {
        if (!file_exists($source)) {
            throw new \Exception('File does not exist');
        }

        $targetInfo = pathinfo($target);

        $pdf = new \Phpdocx\Libs\TCPDI();
        $pageCount = $pdf->setSourceFile($source);
        for ($i = 1; $i <= $pageCount; $i++) {
            // avoid pages if requested
            if (isset($options['pages']) && in_array($i, $options['pages'])) {
                continue;
            }
            $tpl = $pdf->importPage($i);
            $pdf->setPrintHeader(false);
            $pdf->addPage();
            $pdf->useTemplate($tpl, null, null, 0, 0, TRUE);
        }

        if (file_exists(dirname(__FILE__) . '/ZipStream.php') && \Phpdocx\Create\CreateDocx::$streamMode === true) {
            $pdf->Output($target, 'I');
        } else {
            $pdf->Output($target, 'F');
        }
    }

    /**
     * Splits a PDF document
     * 
     * @access public
     * @param string $source Path to the PDF
     * @param string $target Path to the resulting PDF (a new file will be created per page)
     * @param array $options
     *        array 'pages' pages to be splitted, all by default
     * @return void
     */
    public function splitPdf($source, $target, $options = array())
    {
        if (!file_exists($source)) {
            throw new \Exception('File does not exist');
        }

        $targetInfo = pathinfo($target);

        $pdf = new \Phpdocx\Libs\TCPDI();
        $pageCount = $pdf->setSourceFile($source);
        for ($i = 1; $i <= $pageCount; $i++) {
            // avoid pages if requested
            if (isset($options['pages']) && !in_array($i, $options['pages'])) {
                continue;
            }
            $pdfNewDocument = new \Phpdocx\Libs\TCPDI();
            $pdfNewDocument->setSourceFile($source);
            $tpl = $pdfNewDocument->importPage($i);
            $pdfNewDocument->setPrintHeader(false);
            $pdfNewDocument->addPage();
            $pdfNewDocument->useTemplate($tpl, null, null, 0, 0, TRUE);
            
            $pdfNewDocument->Output($targetInfo['filename'] . $i . '.' . $targetInfo['extension'], 'F');
        }
    }

    /**
     * Adds a watermark to an existing PDF document
     * 
     * @access public
     * @param string $source Path to the PDF
     * @param string $target Path to the resulting watermarked PDF
     * @param string $type
     * Values: text, image
     * @param array $options
     * Values if type equals image:
     *     string 'image' path to the watermark image
     *     int 'positionX' X-asis position (page center as default)
     *     int 'positionY' Y-asis position (page center as default)
     *     float 'opacity' decimal number between 0 and 1(optional), if not set defaults to 0.5
     * Values if type equals text
     *     string 'text' text used for the watermark
     *     int 'positionX' X-asis position (page center as default)
     *     int 'positionY' Y-asis position (page center as default)
     *     string 'font' font-family, it must be installed in the OS
     *     int 'size' font size
     *     int 'rotation' watermark width in pixels
     *     array 'color' RGB: array(r, g, b) (array(255, 255, 255))
     *     float 'opacity' decimal number between 0 and 1(optional), if not set defaults to 0.5
     * @return boolean
     */
    public function watermarkPdf($source, $target, $type, $options = array())
    {
        if (!file_exists($source)) {
            throw new \Exception('File does not exist');
        }

        // default values
        if (!isset($options['opacity'])) {
            $options['opacity'] = 0.5;
        }
        if (!isset($options['font'])) {
            $options['font'] = '';
        }
        if (!isset($options['size'])) {
            $options['size'] = 20;
        }
        if (!isset($options['rotation'])) {
            $options['rotation'] = 45;
        }
        if (!isset($options['color'])) {
            $options['color'] = array(0, 0, 0);
        }

        if ($type != 'image' && $type != 'text') {
            throw new \Exception('Allowed types: image, text');
        }

        if ($type == 'image') {
            if (!isset($options['image']) || !file_exists($options['image'])) {
                throw new \Exception('Image does not exist');
            }

            $imageInfo = pathinfo($options['image']);

            // image width
            $imageSize = getimagesize($options['image']);
            $centerScale = round($imageSize[0] / 2, 0) / 7.2;

            $pdf = new \Phpdocx\Libs\TCPDI();
            $pageCount = $pdf->setSourceFile($source);
            for ($i = 1; $i <= $pageCount; $i++) {
                $tpl = $pdf->importPage($i);
                $pdf->setPrintHeader(false);
                $pdf->addPage();
                $pdf->useTemplate($tpl, null, null, 0, 0, TRUE);
                $pdf->SetAlpha($options['opacity']);

                if (!isset($options['positionX'])) {
                    // center of the PDF
                    $options['positionX'] = ($pdf->getPageWidth() / 2) - $centerScale*2;

                    if (!isset($options['positionY'])) {
                        $options['positionY'] = ($pdf->getPageHeight() / 2) - $centerScale*2;
                    }

                    $pdf->Image($options['image'], $options['positionX'], $options['positionY'], 0, 0, $imageInfo['extension'], '', 'T', false, 300, 'C', false, false, 0, false, false, false);
                } else {
                    // positionX and positionY have values
                    if (!isset($options['positionY'])) {
                        $options['positionY'] = ($pdf->getPageHeight() / 2) - $centerScale*2;
                    }

                    $pdf->Image($options['image'], $options['positionX'], $options['positionY'], 0, 0, $imageInfo['extension']);
                }
                $pdf->SetAlpha(1);
            }

            $pdf->Output($target, 'F');
        } elseif ($type == 'text') {
            if (!isset($options['text'])) {
                throw new \Exception('Text value is missing');
            }

            $pdf = new \Phpdocx\Libs\TCPDI();
            $pageCount = $pdf->setSourceFile($source);

            // text width
            $widthText = $pdf->GetStringWidth($options['text'], $options['font'], '', $options['size']);
            $centerScale = round(($widthText * sin(deg2rad($options['rotation']))) / 2, 0);

            for ($i = 1; $i <= $pageCount; $i++) {
                $tpl = $pdf->importPage($i);
                $pdf->setPrintHeader(false);
                $pdf->addPage();
                $pdf->useTemplate($tpl, null, null, 0, 0, TRUE);
                $pdf->SetAlpha($options['opacity']);

                // center of the PDF
                if (!isset($options['positionX'])) {
                    $options['positionX'] = ($pdf->getPageWidth()) / 2 - $centerScale*2;
                }
                if (!isset($options['positionY'])) {
                    $options['positionY'] = ($pdf->getPageHeight()) / 2 -$centerScale*2;
                }

                $pdf->StartTransform();
                $pdf->Rotate($options['rotation'], $options['positionX'], $options['positionY']);
                $pdf->SetFont($options['font'], '', $options['size']);
                $pdf->SetTextColor($options['color'][0], $options['color'][1], $options['color'][2]);
                $pdf->Text($options['positionX'], $options['positionY'], $options['text']);
                $pdf->StopTransform();

                $pdf->SetAlpha(1);
            }

            $pdf->Output($target, 'F');
        }
    }
}
