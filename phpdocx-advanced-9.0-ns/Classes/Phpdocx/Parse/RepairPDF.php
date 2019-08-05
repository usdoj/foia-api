<?php
namespace Phpdocx\Parse;
use Phpdocx\Logger\PhpdocxLogger;
use Phpdocx\Resources\OOXMLResources;
/**
 * Repair docx files cleaning, removing or addind content
 *
 * @category   Phpdocx
 * @package    parser
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @version    2017.09.11
 * @link       https://www.phpdocx.com
 */
class RepairPDF
{

    /**
     * @access public
     * @var array
     * @static
     */
    public static $numberings = array(
        'none',
        'bullet',
        'decimal',
        'upperRoman',
        'lowerRoman',
        'upperLetter',
        'lowerLetter',
        'ordinal',
    );

    /**
     * Repair tables for PDF conversion
     *
     * @access public
     * @param string $docXML
     * @param string $numXML
     * @param \DOMDocument $stylesXML
     * @param array $options possible keys an values are:
     *     lists (boolean)
     *     tables (boolean)
     * @static
     */
    public static function repairPDFConversion($docXML, $numXML, $stylesXML, $options)
    {
        $repairTables = true;
        if (isset($options['tables'])) {
            $repairTables = $options['tables'];
        }
        $repairLists = true;
        if (isset($options['lists'])) {
            $repairLists = $options['lists'];
        }
        $repairBreaks = true;
        if (isset($options['breaks'])) {
            $repairBreaks = $options['breaks'];
        }

        $docDOM = new \DOMDocument();
        $optionEntityLoader = libxml_disable_entity_loader(true);
        $docDOM->loadXML($docXML);
        libxml_disable_entity_loader($optionEntityLoader);
        $docXPath = new \DOMXPath($docDOM);
        $docXPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        if ($numXML != '' && $repairLists) {
            $numDOM = new \DOMDocument();
            $optionEntityLoader = libxml_disable_entity_loader(true);
            $numDOM->loadXML($numXML);
            libxml_disable_entity_loader($optionEntityLoader);

            // Issue: numbering problems when two or more w:num use the same w:abstractNumId
            // LibreOffice shows them using not correlative values
            // Solution: replace the w:numId in document.xml to use the first w:num value

            $docNumXPath = new \DOMXPath($numDOM);
            $docNumXPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');            
            // get all w:abstractNum tags and keep their IDs
            $query = '//w:abstractNum';
            $abstractNumNodes = $docNumXPath->query($query);
            $abstractNumValues = array();
            foreach ($abstractNumNodes as $abstractNumNode) {
                $abstractNumValues[] = $abstractNumNode->getAttribute('w:abstractNumId');
            }

            // iterate numValues to get repeated values
            $numValues = array();
            foreach ($abstractNumValues as $abstractNumValue) {
                $query = '//w:num[w:abstractNumId[@w:val="'.$abstractNumValue.'"]]';
                $numNodes = $docNumXPath->query($query);

                // the same abstractNumId value can be used by more than one w:num.
                // Get each numId for each abstractNum
                foreach ($numNodes as $numNode) {
                    $numValues[$abstractNumValue][] = $numNode->getAttribute('w:numId');
                }
            }

            // iterate numValues to replace numId to use the same numId when the abstractNum is the same
            $numIdDocNodes = $docDOM->getElementsByTagName('numId');
            foreach ($numIdDocNodes as $numIdDocNode) {
                foreach ($numValues as $numValueKey => $numValue) {
                    // get the array key that contains the numId
                    $keyValue = array_search($numIdDocNode->getAttribute('w:val'), $numValue);
                    // replace the numId by the first value of the array key if it existis
                    // and the $numIdDocNodes has moren than a single value
                    if ($keyValue && count($numValues) > 0) {
                        $numIdDocNode->setAttribute('w:val', $numValues[$numValueKey][0]);
                    }
                }
            }

            // iterate w:numId in the styles to the same than in numbering.xml previously
            if ($stylesXML != null && $stylesXML != '') {
                $numIdDocNodes = $stylesXML->getElementsByTagName('numId');
                foreach ($numIdDocNodes as $numIdDocNode) {
                    foreach ($numValues as $numValueKey => $numValue) {
                        // get the array key that contains the numId
                        $keyValue = array_search($numIdDocNode->getAttribute('w:val'), $numValue);
                        // replace the numId by the first value of the array key if it existis
                        // and the $numIdDocNodes has moren than a single value
                        if ($keyValue && count($numValues) > 0) {
                            $numIdDocNode->setAttribute('w:val', $numValues[$numValueKey][0]);
                        }
                    }
                }
            }
        }

        if ($repairTables) {
            $tblNodes = $docDOM->getElementsByTagName('tbl');
            foreach ($tblNodes as $tblNode) {
                //0. Check if there is a table grid element with well defined vals
                $repairTable = false;
                $tblGridNodes = $tblNode->getElementsByTagName('tblGrid');
                if ($tblGridNodes->length > 0) {
                    $gridColNodes = $tblGridNodes->item(0)->getElementsByTagName('gridCol');
                    foreach ($gridColNodes as $gridNode) {
                        $wAttribute = $gridNode->getAttribute('w:w');
                        if (empty($wAttribute)) {
                            $repairTable = true;
                        }
                    }
                } else {
                    $repairTable = true;
                }
                //1. Determine the total table width
                $tblWNodes = $tblNode->getElementsByTagName('tblW');
                if ($tblWNodes->length > 0) {
                    //check if the width is given in twips
                    $widthUnits = $tblWNodes->item(0)->getAttribute('w:type');
                    if ($widthUnits == 'dxa') {
                        $tableWidth = $tblWNodes->item(0)->getAttribute('w:w');
                    } else {
                        $tableWidth = false;
                        PhpdocxLogger::logger('For proper conversion to PDF, tables may not have their width set in percentage.', 'info');
                    }
                } else {
                    $tableWidth = false;
                    PhpdocxLogger::logger('For proper conversion to PDF, tables should have their width set.', 'info');
                }
                if (!empty($tableWidth) && $repairTable) {
                    //2. Extract the rows
                    $tableRows = $tblNode->getElementsByTagName('tr');
                    $rowNumber = 0;
                    $grid = array();
                    foreach ($tableRows as $row) {
                        $grid[$rowNumber] = array();
                        $weights[$rowNumber] = array();
                        //3. Extract the cells of each row
                        $cellNodes = $row->getElementsByTagName('tc');
                        foreach ($cellNodes as $cellNode) {
                            $gridSpan = 1;
                            $spanNodes = $cellNode->getElementsByTagName('gridSpan');
                            if ($spanNodes->length > 0) {
                                $span = $spanNodes->item(0)->getAttribute('w:val');
                                if (isset($span) && $span > 1) {
                                    $gridSpan = $span;
                                }
                            }
                            $tcWidths = $cellNode->getElementsByTagName('tcW');
                            if ($tcWidths->length > 0) {
                                $widthData = $tcWidths->item(0)->getAttribute('w:w');
                                $widthUnits = $tcWidths->item(0)->getAttribute('w:type');
                                if ($widthUnits == 'dxa') {
                                    $cellWidth = $widthData;
                                } else if ($widthUnits == 'pct') {
                                    //the width is given in fitieths of a percent
                                    $cellWidth = floor($widthData * $tableWidth / 5000);
                                } else {
                                    $cellWidth = 0;
                                }
                            } else {
                                $cellWidth = 0;
                            }
                            //let us build the grid and weight arrays for this cell
                            if ($gridSpan > 1) {
                                $cellWidth = floor($cellWidth / $gridSpan);
                                for ($j = 0; $j < $gridSpan; $j++) {
                                    $grid[$rowNumber][] = $cellWidth;
                                    $weights[$rowNumber][] = 0;
                                }
                            } else {
                                $grid[$rowNumber][] = $cellWidth;
                                $weights[$rowNumber][] = 1;
                            }
                        }
                        $rowNumber++;
                    }
                    //we have now all the required info to build the gridCol array
                    $gridCol = array();
                    $rowPos = 0;
                    foreach ($grid as $row) {
                        $cellPos = 0;
                        foreach ($row as $cell) {
                            if ($weights[$rowPos][$cellPos] == 1 && !empty($grid[$rowPos][$cellPos])) {
                                $gridCol[$cellPos] = $grid[$rowPos][$cellPos];
                            } else if ($weights[$rowPos][$cellPos] == 0 && !empty($grid[$rowPos][$cellPos]) && empty($gridCol[$cellPos])) {
                                $gridCol[$cellPos] = $grid[$rowPos][$cellPos];
                            }
                            $cellPos++;
                        }
                        $rowPos++;
                    }
                    //create the tblGrid node node and insert it
                    $gridColXML = '<w:tblGrid xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">';
                    foreach ($gridCol as $col) {
                        $gridColXML .= '<w:gridCol w:w="' . $col . '"/>';
                    }
                    $gridColXML .= '</w:tblGrid>';
                    //remove any previous tblGrid elements if any
                    $tblGridNodes = $tblNode->getElementsByTagName('tblGrid');
                    if ($tblGridNodes->length > 0) {
                        $tblGridNodes->item(0)->parentNode->removeChild($tblGridNodes->item(0));
                    }
                    //insert this node just before the first tr node
                    $firstRow = $tblNode->getElementsByTagName('tr')->item(0);
                    $gridFragment = $docDOM->createDocumentFragment();
                    $gridFragment->appendXML($gridColXML);
                    $tblNode->insertBefore($gridFragment, $firstRow);
                }
            }
        }
        /*if ($repairBreaks) {
            $query = '//w:p[w:pPr/w:pageBreakBefore[@w:val="on"]]';
            $breaks = $docXPath->query($query);
            foreach($breaks as $break){
                //insert a page break before the first w:t node
                $ts = $break->getElementsByTagName('t');
                if ($ts->length > 0) {
                    $t = $ts->item(0);
                    $br = $t->ownerDocument->createElement('w:br');
                    $br->setAttribute('w:type', 'page');
                    $t->parentNode->insertBefore($br, $t);
                    //remove the w:pageBreakBeforeElement
                    $pBB = $break->getElementsByTagName('pageBreakBefore')->item(0);
                    $pBB->parentNode->removeChild($pBB);
                }
            }
        }*/
        //return the resulting XML string
        $repairedXML = $docDOM->saveXML();
        $repairedXML = str_replace('<w:r xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">', '<w:r>', $repairedXML);
        $repairedXML = str_replace('<w:u xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"', '<w:u', $repairedXML);

        return array(
            'content' => $repairedXML,
            'styles' => $stylesXML,
        );
    }

}
