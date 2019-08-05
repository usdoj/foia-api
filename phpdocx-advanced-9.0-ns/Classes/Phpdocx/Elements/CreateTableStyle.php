<?php
namespace Phpdocx\Elements;
/**
 * Create table styles
 *
 * @category   Phpdocx
 * @package    elements
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @version    2018.08.20
 * @link       https://www.phpdocx.com
 */
class CreateTableStyle
{

    /**
     * @access private
     * @var array
     */
    private $style;

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
     * Add a table style.
     *
     * @access public
     * @param string $name
     * @param array $styleOptions
     * @return array
     */
    public function addTableStyle($name, $styleOptions)
    {
        $tableStyle = '<w:style xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" ';
        $tableStyle .= 'w:type="table" w:customStyle="1" w:styleId="' . $name . '">';
        $tableStyle .= '<w:name w:val="' . $name . '"/>';

        // general table styles
        if (isset($styleOptions['tableStyle'])) {
            $tableStyle .= '<w:basedOn w:val="' . $styleOptions['tableStyle'] . '"/>';
        }

        if (isset($styleOptions['pPrStyles'])) {
            $stylepPrOptions = \Phpdocx\Create\CreateDocx::translateTextOptions2StandardFormat($styleOptions['pPrStyles']);
            $newStyle = new CreateParagraphStyle();
            $newStyle->addParagraphStyle(uniqid(mt_rand(999, 9999)), $stylepPrOptions);

            $tableStyle .= $newStyle->XMLPPr();
        }

        if (isset($styleOptions['rPrStyles'])) {
            $stylerPrOptions = \Phpdocx\Create\CreateDocx::translateTextOptions2StandardFormat($styleOptions['rPrStyles']);

            $newStyle = new CreateParagraphStyle();
            $newStyle->createCustomCharacterStyle(uniqid(mt_rand(999, 9999)), $stylerPrOptions);

            $tableStyle .= $newStyle->XMLrPr();
        }

        $tableStyle .= '<w:tblPr>';

        if (isset($styleOptions['tblStyleRowBandSize']) && $styleOptions['tblStyleRowBandSize']) {
            $tableStyle .= '<w:tblStyleRowBandSize w:val="1"/>';
        }
        if (isset($styleOptions['tblStyleColBandSize']) && $styleOptions['tblStyleColBandSize']) {
            $tableStyle .= '<w:tblStyleColBandSize w:val="1"/>';
        }
        if (isset($styleOptions['indent'])) {
            $tableStyle .= '<w:tblInd w:type="dxa" w:w="' . $styleOptions['indent'] . '"/>';
        }

        // borders
        $borders = array('Top', 'Left', 'Bottom', 'Right', 'InsideH', 'InsideV');
        $drawCellBorders = false;
        $border = array();

        // run over the general border properties
        if (isset($styleOptions['border'])) {
            $drawCellBorders = true;
            foreach ($borders as $valueBorder) {
                $border[$valueBorder]['type'] = $styleOptions['border'];
            }
        }
        if (isset($styleOptions['borderColor'])) {
            $drawCellBorders = true;
            foreach ($borders as $valueBorder) {
                $border[$valueBorder]['color'] = $styleOptions['borderColor'];
            }
        }
        if (isset($styleOptions['borderSpacing'])) {
            $drawCellBorders = true;
            foreach ($borders as $valueBorder) {
                $border[$valueBorder]['spacing'] = $styleOptions['borderSpacing'];
            }
        }
        if (isset($styleOptions['borderWidth'])) {
            $drawCellBorders = true;
            foreach ($borders as $valueBorder) {
                $border[$valueBorder]['width'] = $styleOptions['borderWidth'];
            }
        }
        // run over the border choices of each side
        foreach ($borders as $valueBorder) {
            if (isset($styleOptions['border' . $valueBorder])) {
                $drawCellBorders = true;
                $border[$valueBorder]['type'] = $styleOptions['border' . $valueBorder];
            }
            if (isset($styleOptions['border' . $valueBorder . 'Color'])) {
                $drawCellBorders = true;
                $border[$valueBorder]['color'] = $styleOptions['border' . $valueBorder . 'Color'];
            }
            if (isset($styleOptions['border' . $valueBorder . 'Spacing'])) {
                $drawCellBorders = true;
                $border[$valueBorder]['spacing'] = $styleOptions['border' . $valueBorder . 'Spacing'];
            }
            if (isset($styleOptions['border' . $valueBorder . 'Width'])) {
                $drawCellBorders = true;
                $border[$valueBorder]['width'] = $styleOptions['border' . $valueBorder . 'Width'];
            }
        }
        if ($drawCellBorders) {
            $tableStyle .= '<w:tblBorders>';
            foreach ($borders as $valueBorder) {
                if (isset($border[$valueBorder])) {
                    if (isset($border[$valueBorder]['type'])) {
                        $borderType = $border[$valueBorder]['type'];
                    } else {
                        $borderType = 'single';
                    }
                    if (isset($border[$valueBorder]['color'])) {
                        $borderColor = $border[$valueBorder]['color'];
                    } else {
                        $borderColor = '000000';
                    }
                    if (isset($border[$valueBorder]['width'])) {
                        $borderWidth = $border[$valueBorder]['width'];
                    } else {
                        $borderWidth = 6;
                    }
                    if (isset($border[$valueBorder]['spacing'])) {
                        $borderSpacing = $border[$valueBorder]['spacing'];
                    } else {
                        $borderSpacing = 0;
                    }
                    $valueBorder[0] = strtolower($valueBorder[0]);
                    $tableStyle .= '<w:' . $valueBorder . ' w:val="' . $borderType . '" w:color="' . $borderColor . '" w:sz="' . $borderWidth . '" w:space="' . $borderSpacing . '"/>';
                }
            }
            $tableStyle .= '</w:tblBorders>';
        }

        // cell margin
        if (isset($styleOptions['cellMargin'])) {
            $tableStyle .= '<w:tblCellMar>';
            if (is_array($styleOptions['cellMargin'])) {
                foreach ($styleOptions['cellMargin'] as $keyMargin => $valueMargin) {
                    $tableStyle .= '<w:' . $keyMargin . ' w:w="' . $valueMargin . '" w:type="dxa" />';
                }
            } else if (is_int($styleOptions['cellMargin'])) {
                $tableStyle .= '<w:top w:w="' . $styleOptions['cellMargin'] . '" w:type="dxa" />';
                $tableStyle .= '<w:left w:w="' . $styleOptions['cellMargin'] . '" w:type="dxa" />';
                $tableStyle .= '<w:bottom w:w="' . $styleOptions['cellMargin'] . '" w:type="dxa" />';
                $tableStyle .= '<w:right w:w="' . $styleOptions['cellMargin'] . '" w:type="dxa" />';
            }
            $tableStyle .= '</w:tblCellMar>';
        }

        $tableStyle .= '</w:tblPr>';

        if (isset($styleOptions['firstRowStyle']) && is_array($styleOptions['firstRowStyle']) && count($styleOptions['firstRowStyle']) > 0) {
            $tableStyle .= $this->generateTblStylePr('firstRow', $styleOptions['firstRowStyle']);
        }

        if (isset($styleOptions['firstColStyle']) && is_array($styleOptions['firstColStyle']) && count($styleOptions['firstColStyle']) > 0) {
            $tableStyle .= $this->generateTblStylePr('firstCol', $styleOptions['firstColStyle']);
        }

        if (isset($styleOptions['lastRowStyle']) && is_array($styleOptions['lastRowStyle']) && count($styleOptions['lastRowStyle']) > 0) {
            $tableStyle .= $this->generateTblStylePr('lastRow', $styleOptions['lastRowStyle']);
        }

        if (isset($styleOptions['lastColStyle']) && is_array($styleOptions['lastColStyle']) && count($styleOptions['lastColStyle']) > 0) {
            $tableStyle .= $this->generateTblStylePr('lastCol', $styleOptions['lastColStyle']);
        }

        if (isset($styleOptions['band1VertStyle']) && is_array($styleOptions['band1VertStyle']) && count($styleOptions['band1VertStyle']) > 0) {
            $tableStyle .= $this->generateTblStylePr('band1Vert', $styleOptions['band1VertStyle']);
        }

        if (isset($styleOptions['band1HorzStyle']) && is_array($styleOptions['band1HorzStyle']) && count($styleOptions['band1HorzStyle']) > 0) {
            $tableStyle .= $this->generateTblStylePr('band1Horz', $styleOptions['band1HorzStyle']);
        }

        if (isset($styleOptions['band2VertStyle']) && is_array($styleOptions['band2VertStyle']) && count($styleOptions['band2VertStyle']) > 0) {
            $tableStyle .= $this->generateTblStylePr('band2Vert', $styleOptions['band2VertStyle']);
        }

        if (isset($styleOptions['band2HorzStyle']) && is_array($styleOptions['band2HorzStyle']) && count($styleOptions['band2HorzStyle']) > 0) {
            $tableStyle .= $this->generateTblStylePr('band2Horz', $styleOptions['band2HorzStyle']);
        }

        if (isset($styleOptions['nwCellStyle']) && is_array($styleOptions['nwCellStyle']) && count($styleOptions['nwCellStyle']) > 0) {
            $tableStyle .= $this->generateTblStylePr('nwCell', $styleOptions['nwCellStyle']);
        }

        if (isset($styleOptions['neCellStyle']) && is_array($styleOptions['neCellStyle']) && count($styleOptions['neCellStyle']) > 0) {
            $tableStyle .= $this->generateTblStylePr('neCell', $styleOptions['neCellStyle']);
        }

        if (isset($styleOptions['swCellStyle']) && is_array($styleOptions['swCellStyle']) && count($styleOptions['swCellStyle']) > 0) {
            $tableStyle .= $this->generateTblStylePr('swCell', $styleOptions['swCellStyle']);
        }

        if (isset($styleOptions['seCellStyle']) && is_array($styleOptions['seCellStyle']) && count($styleOptions['seCellStyle']) > 0) {
            $tableStyle .= $this->generateTblStylePr('seCell', $styleOptions['seCellStyle']);
        }

        $tableStyle .= '</w:style>';

        return $tableStyle;
    }

    /**
     * Generate tblStylePr.
     * 
     * @param string $type
     * @param array $styleOptions
     * @return string
     */
    protected function generateTblStylePr($type, $styleOptions) {
        $xml = '<w:tblStylePr w:type="' . $type . '">';

        if (isset($styleOptions['pPrStyles']) && is_array($styleOptions['pPrStyles'])) {
            $stylepPrOptions = \Phpdocx\Create\CreateDocx::translateTextOptions2StandardFormat($styleOptions['pPrStyles']);
            $newStyle = new CreateParagraphStyle();
            $newStyle->addParagraphStyle(uniqid(mt_rand(999, 9999)), $stylepPrOptions);

            $xml .= $newStyle->XMLPPr();
        }

        if (isset($styleOptions['rPrStyles']) && is_array($styleOptions['rPrStyles'])) {
            $stylerPrOptions = \Phpdocx\Create\CreateDocx::translateTextOptions2StandardFormat($styleOptions['rPrStyles']);

            $newStyle = new CreateParagraphStyle();
            $newStyle->createCustomCharacterStyle(uniqid(mt_rand(999, 9999)), $stylerPrOptions);

            $xml .= $newStyle->XMLRPr();
        }

        $xml .= '<w:tblPr/>';

        $xml .= '<w:tcPr>';

        // borders
        $borders = array('Top', 'Left', 'Bottom', 'Right', 'InsideH', 'InsideV');
        $drawCellBorders = false;
        $border = array();

        // run over the general border properties
        if (isset($styleOptions['border'])) {
            $drawCellBorders = true;
            foreach ($borders as $valueBorder) {
                $border[$valueBorder]['type'] = $styleOptions['border'];
            }
        }
        if (isset($styleOptions['borderColor'])) {
            $drawCellBorders = true;
            foreach ($borders as $valueBorder) {
                $border[$valueBorder]['color'] = $styleOptions['borderColor'];
            }
        }
        if (isset($styleOptions['borderSpacing'])) {
            $drawCellBorders = true;
            foreach ($borders as $valueBorder) {
                $border[$valueBorder]['spacing'] = $styleOptions['borderSpacing'];
            }
        }
        if (isset($styleOptions['borderWidth'])) {
            $drawCellBorders = true;
            foreach ($borders as $valueBorder) {
                $border[$valueBorder]['width'] = $styleOptions['borderWidth'];
            }
        }
        // run over the border choices of each side
        foreach ($borders as $valueBorder) {
            if (isset($styleOptions['border' . $valueBorder])) {
                $drawCellBorders = true;
                $border[$valueBorder]['type'] = $styleOptions['border' . $valueBorder];
            }
            if (isset($styleOptions['border' . $valueBorder . 'Color'])) {
                $drawCellBorders = true;
                $border[$valueBorder]['color'] = $styleOptions['border' . $valueBorder . 'Color'];
            }
            if (isset($styleOptions['border' . $valueBorder . 'Spacing'])) {
                $drawCellBorders = true;
                $border[$valueBorder]['spacing'] = $styleOptions['border' . $valueBorder . 'Spacing'];
            }
            if (isset($styleOptions['border' . $valueBorder . 'Width'])) {
                $drawCellBorders = true;
                $border[$valueBorder]['width'] = $styleOptions['border' . $valueBorder . 'Width'];
            }
        }
        if ($drawCellBorders) {
            $xml .= '<w:tcBorders>';
            foreach ($borders as $valueBorder) {
                if (isset($border[$valueBorder])) {
                    if (isset($border[$valueBorder]['type'])) {
                        $borderType = $border[$valueBorder]['type'];
                    } else {
                        $borderType = 'single';
                    }
                    if (isset($border[$valueBorder]['color'])) {
                        $borderColor = $border[$valueBorder]['color'];
                    } else {
                        $borderColor = '000000';
                    }
                    if (isset($border[$valueBorder]['width'])) {
                        $borderWidth = $border[$valueBorder]['width'];
                    } else {
                        $borderWidth = 6;
                    }
                    if (isset($border[$valueBorder]['spacing'])) {
                        $borderSpacing = $border[$valueBorder]['spacing'];
                    } else {
                        $borderSpacing = 0;
                    }
                    $valueBorder[0] = strtolower($valueBorder[0]);
                    $xml .= '<w:' . $valueBorder . ' w:val="' . $borderType . '" w:color="' . $borderColor . '" w:sz="' . $borderWidth . '" w:space="' . $borderSpacing . '"/>';
                }
            }
            $xml .= '</w:tcBorders>';
        }

        if (isset($styleOptions['backgroundColor'])) {
            $xml .= '<w:shd w:color="auto" w:val="clear" w:fill="' . $styleOptions['backgroundColor'] . '"/>';
        }

        if (isset($styleOptions['vAlign'])) {
            $xml .= '<w:vAlign w:val="' . $styleOptions['vAlign'] . '"/>';
        }

        $xml .= '</w:tcPr>';

        $xml .= '</w:tblStylePr>';

        return $xml;
    }
}
