<?php
namespace Phpdocx\Elements;
/**
 * Create Bar and col Chart
 *
 * @category   Phpdocx
 * @package    elements
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @version    2018.01.26
 * @link       https://www.phpdocx.com
 */
class CreateBarChart extends CreateGraphic implements InterfaceGraphic
{

    /**
     * Create embedded xml chart
     *
     * @access public
     */
    public function createEmbeddedXmlChart()
    {
        $this->_xmlChart = '';
        $this->generateCHARTSPACE();
        $this->generateDATE1904(1);
        $this->generateLANG();
        $this->generateROUNDEDCORNERS($this->_roundedCorners);
        $color = 2;
        if ($this->_color) {
            $color = $this->_color;
        }
        $this->generateSTYLE($color);
        $this->generateCHART();
        if ($this->_title != '') {
            $this->generateTITLE();
            $this->generateTITLETX();
            $this->generateRICH();
            $this->generateBODYPR();
            $this->generateLSTSTYLE();
            $this->generateTITLEP();
            $this->generateTITLEPPR();
            $this->generateDEFRPR('title');
            $this->generateTITLER();
            $this->generateTITLERPR();
            $this->generateTITLET($this->_title);
            $this->cleanTemplateFonts();
        } else {
            $this->generateAUTOTITLEDELETED();
            $title = '';
        }
        if (strpos($this->_type, '3D') !== false) {
            $this->generateVIEW3D();
            $rotX = 30;
            $rotY = 30;
            $perspective = 30;
            if ($this->_rotX != '') {
                $rotX = $this->_rotX;
            }
            if ($this->_rotY != '') {
                $rotY = $this->_rotY;
            }
            if ($this->_perspective != '') {
                $perspective = $this->_perspective;
            }
            $this->generateROTX($rotX);
            $this->generateROTY($rotY);
            $this->generateRANGAX($this->_rAngAx);
            $this->generatePERSPECTIVE($perspective);
        }
        if ($this->values == '') {
            exit('You haven`t added data');
        }
        $this->generatePLOTAREA();
        $this->generateLAYOUT();
        if (strpos($this->_type, '3D') !== false) {
            $this->generateBAR3DCHART();
        } else {
            $this->generateBARCHART();
        }

        $typeBar = 'bar';

        if (strpos($this->_type, 'col') !== false) {
            $typeBar = 'col';
        }

        $this->generateBARDIR($typeBar);
        $groupBar = 'clustered';
        if ($this->_groupBar != '') {
            $groupBar = $this->_groupBar;
        }

        $this->generateGROUPING($groupBar);
        if (isset($this->values['legend'])) {
            $legends = $this->values['legend'];
        } else {
            echo('You haven`t added legends');
            return false;
        }
        $numValues = count($this->values['data']);

        $this->generateVARYCOLORS($this->_varyColors);
        $letter = 'A';
        // keep the max id value to be used with combo charts
        $idxMax = 0;
        for ($i = 0; $i < count($legends); $i++) {
            $this->generateSER();
            $this->generateIDX($i);
            $this->generateORDER($i);
            $letter++;

            $this->generateTX();
            $this->generateSTRREF();
            $this->generateF('Sheet1!$' . $letter . '$1');
            $this->generateSTRCACHE();
            $this->generatePTCOUNT();
            $this->generatePT();
            $this->generateV($legends[$i]);

            $this->cleanTemplate2();

            if (isset($this->values['trendline'][$i])) {
                $this->generateTRENDLINE($this->values['trendline'][$i]);
            }

            $this->generateCAT();
            $this->generateSTRREF();
            $this->generateF('Sheet1!$A$2:$A$' . ($numValues + 1));
            $this->generateSTRCACHE();
            $this->generatePTCOUNT($numValues);

            $num = 0;
            foreach ($this->values['data'] as $value) {
                $this->generatePT($num);
                $this->generateV($value['name']);
                $num++;
            }
            $this->cleanTemplate2();

            $this->generateVAL();
            $this->generateNUMREF();
            $this->generateF('Sheet1!$' . $letter . '$2:$B$' . ($numValues + 1));
            $this->generateNUMCACHE();
            $this->generateFORMATCODE();
            $this->generatePTCOUNT($numValues);
            $num = 0;
            foreach ($this->values['data'] as $name => $value) {
                $this->generatePT($num);
                $this->generateV($value['values'][$i]);
                $num++;
            }
            $this->cleanTemplate3();

            $idxMax = $i;
        }
        //Generate labels
        $this->generateSERDLBLS();
        $this->generateSHOWLEGENDKEY($this->_showLegendKey);
        $this->generateSHOWVAL($this->_showValue);
        $this->generateSHOWCATNAME($this->_showCategory);
        $this->generateSHOWSERNAME($this->_showSeries);
        $this->generateSHOWPERCENT($this->_showPercent);
        $this->generateSHOWBUBBLESIZE($this->_showBubbleSize);

        $shape = 'box';
        if (strpos($this->_type, 'Cylinder') !== false) {
            $shape = 'cylinder';
        } elseif (strpos($this->_type, 'Cone') !== false) {
            $shape = 'cone';
        } elseif (strpos($this->_type, 'Pyramid') !== false) {
            $shape = 'pyramid';
        }
        $this->generateSHAPE($shape);
        if (isset($this->_groupBar) && ($this->_groupBar == 'stacked' ||
                $this->_groupBar == 'percentStacked') && (strpos($this->_type, '3D') === false)
        ) {
            $this->generateOVERLAP();
        }

        $this->generateAXID();
        $this->generateAXID(59040512);

        if ($this->_comboChart !== null) {
            $this->addComboChart($idxMax + 1);
        }

        $this->generateCATAX();
        $this->generateAXAXID(59034624);
        $this->generateSCALING();
        $this->generateDELETE($this->_delete);
        $this->generateORIENTATION();
        $this->generateAXPOS();
        switch ($this->_vgrid) {
            case 1:
                $this->generateMAJORGRIDLINES();
                break;
            case 2:
                $this->generateMINORGRIDLINES();
                break;
            case 3:
                $this->generateMAJORGRIDLINES();
                $this->generateMINORGRIDLINES();
                break;
            default:
                break;
        }
        if (!empty($this->_haxLabel)) {
            $this->generateAXLABEL($this->_haxLabel);
            $vert = 'horz';
            $rot = 0;
            if ($this->_haxLabelDisplay == 'vertical') {
                $vert = 'wordArtVert';
            }
            if ($this->_haxLabelDisplay == 'rotated') {
                $rot = '-5400000';
            }
            $this->generateAXLABELDISP($vert, $rot);
        }
        $this->generateTICKLBLPOS();
        $this->generateCROSSAX();
        $this->generateCROSSES();
        $this->generateAUTO();
        $this->generateLBLALGN();
        $this->generateLBLOFFSET();
        if (!empty($this->_showTable)) {
            $this->generateDATATABLE();
        }

        $this->generateLEGEND();
        $this->generateLEGENDPOS($this->_legendPos);
        $this->generateLEGENDOVERLAY($this->_legendOverlay);


        $this->generateVALAX();
        $this->generateAXAXID(59040512);
        $this->generateSCALING(true);
        $this->generateDELETE($this->_delete);
        $this->generateORIENTATION();
        $this->generateAXPOS('l');
        switch ($this->_hgrid) {
            case 1:
                $this->generateMAJORGRIDLINES();
                break;
            case 2:
                $this->generateMINORGRIDLINES();
                break;
            case 3:
                $this->generateMAJORGRIDLINES();
                $this->generateMINORGRIDLINES();
                break;
            default:
                break;
        }
        if (!empty($this->_vaxLabel)) {
            $this->generateAXLABEL($this->_vaxLabel);
            $vert = 'horz';
            $rot = 0;
            if ($this->_vaxLabelDisplay == 'vertical') {
                $vert = 'wordArtVert';
            }
            if ($this->_vaxLabelDisplay == 'rotated') {
                $rot = '-5400000';
            }
            $this->generateAXLABELDISP($vert, $rot);
        }
        if ($this->_formatCode) {
            $this->generateNUMFMT($this->_formatCode, 0);
        } else {
            $this->generateNUMFMT();
        }
        $this->generateTICKLBLPOS($this->_tickLblPos, true);
        $this->generateMAJORUNIT($this->_majorUnit);
        $this->generateMINORUNIT($this->_minorUnit);

        $this->generateCROSSAX(59034624);
        $this->generateCROSSES();
        $this->generateCROSSBETWEEN();


        $this->generatePLOTVISONLY();
        if (!isset($this->_border) || $this->_border == 0 || !is_numeric($this->_border)) {
            $this->generateSPPR();
            $this->generateLN();
            $this->generateNOFILL();
        } else {
            $this->generateSPPR();
            $this->generateLN($this->_border);
        }

        if ($this->_font != '') {
            $this->generateTXPR();
            $this->generateLEGENDBODYPR();
            $this->generateLSTSTYLE();
            $this->generateAP();
            $this->generateAPPR();
            $this->generateDEFRPR();
            $this->generateRFONTS($this->_font);
            $this->generateENDPARARPR();
        }


        $this->generateEXTERNALDATA();
        $this->cleanTemplateDocument();
        return $this->_xmlChart;
    }

    public function dataTag()
    {
        return array('val');
    }

    /**
     * retrun the type of the xlsx object
     *
     * @access public
     */
    public function getXlsxType()
    {
        return CreateCompletedXlsx::getInstance();
    }

}
