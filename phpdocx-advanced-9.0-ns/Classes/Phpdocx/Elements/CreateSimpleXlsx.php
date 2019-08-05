<?php
namespace Phpdocx\Elements;
/**
 * Create xlsx for pie, ofpie charts
 *
 * @category   Phpdocx
 * @package    elements
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @version    2017.09.11
 * @link       https://www.phpdocx.com
 */
class CreateSimpleXlsx extends CreateXlsx implements InterfaceXlsx
{

    /**
     * @access private
     * @var CreateFooter
     * @static
     */
    private static $_instance = NULL;

    /**
     *
     * @access public
     * @return CreateXlsx
     * @static
     */
    public static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new CreateSimpleXlsx();
        }
        return self::$_instance;
    }

    /**
     * Create excel sheet
     *
     * @access public
     * @param string $args[0]
     * @param array $args[1]
     */
    public function createExcelSheet($dats)
    {
        $this->_xml = '';
        $sizeDats = count($dats['data']);
        $sizeCols = 1;
        $this->generateWORKSHEET();
        $this->generateDIMENSION($sizeDats, $sizeCols);
        $this->generateSHEETVIEWS();
        $this->generateSHEETVIEW();
        $this->generateSELECTION($sizeDats + $sizeCols);
        $this->generateSHEETFORMATPR();
        $this->generateCOLS();
        $this->generateCOL();
        $this->generateSHEETDATA();
        $row = 1;

        // add the legend
        $col = 1;
        $letter = 'A';
        $this->generateROW($row, $sizeCols);
        $this->generateC($letter . $row, '', 's');
        $this->generateV($sizeDats + $sizeCols);
        $letter++;
        foreach ($dats['data'] as $legend) {
            $this->generateC($letter . $row, '', 's');
            $this->generateV($col - 1);
            $col++;
            $letter++;
        }
        $this->cleanTemplateROW();
        $row++;

        // add the data
        foreach ($dats['data'] as $data) {
            $this->generateROW($row, $sizeCols);
            $col = 1;
            $letter = 'A';
            $this->generateC($letter . $row, 1, 's');
            $this->generateV($sizeCols + $row - 2);
            $letter++;
            foreach ($data['values'] as $value) {
                $s = '';
                if ($col != $sizeCols) {
                    $s = 1;
                }
                $this->generateC($letter . $row, $s);
                $this->generateV($value);
                $col++;
                $letter++;
            }
            $row++;
            $this->cleanTemplateROW();
        }
        $this->generateROW($row + 1, $sizeCols);
        $row++;
        $this->generateC('B' . $row, 2, 's');
        $this->generateV($sizeDats + $sizeCols + 1);
        $this->generatePAGEMARGINS();
        $this->generateTABLEPARTS();
        $this->generateTABLEPART(1);
        $this->cleanTemplate();

        return $this->_xml;
    }

    /**
     * Create excel shared strings
     *
     * @param string $args[0]
     * @param string $args[1]
     * @access public
     */
    public function createExcelSharedStrings($dats)
    {
        $this->_xml = '';
        $tamDatos = count($dats['data']);
        $tamCols = 1;
        $this->generateSST($tamDatos + $tamCols + 2);

        $this->generateSI();
        $this->generateT('0');

        foreach ($dats['data'] as $data) {
            $this->generateSI();
            $this->generateT($data['name']);
        }
        $this->generateSI();
        $this->generateT(' ', 'preserve');

        $msg = 'To change the range size of values,' .
                'drag the bottom right corner';
        $this->generateSI();
        $this->generateT($msg);

        $this->cleanTemplate();

        return $this->_xml;
    }

    /**
     * Create excel table
     *
     * @access public
     * @param string args[0]
     * @param string args[1]
     */
    public function createExcelTable($dats)
    {
        $this->_xml = '';
        $tamDatos = count($dats['data']);
        $tamCols = 1;
        $this->generateTABLE($tamDatos, $tamCols);
        $this->generateTABLECOLUMNS($tamCols + 1);
        $this->generateTABLECOLUMN(1, ' ');
        $this->generateTABLECOLUMN(2, '0');

        $this->generateTABLESTYLEINFO();
        $this->cleanTemplate();

        return $this->_xml;
    }

}
