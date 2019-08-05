<?php
namespace Phpdocx\Elements;
/**
 * Create paragraph styles
 *
 * @category   Phpdocx
 * @package    elements
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @version    2017.11.16
 * @link       https://www.phpdocx.com
 */
class CreateParagraphStyle
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
     *
     * @access public
     * @param string $name
     * @param array $styleOptions
     * @return array
     */
    public function addParagraphStyle($name, $styleOptions)
    {
        $this->style = $styleOptions;
        if (isset($styleOptions['spacingTop']) ||
                isset($styleOptions['spacingBottom']) ||
                isset($styleOptions['lineSpacing'])) {
            $this->style['spacing'] = true;
        }
        if (isset($styleOptions['indent_left']) ||
                isset($styleOptions['indent_right']) ||
                isset($styleOptions['indent_firstLine']) ||
                isset($styleOptions['hanging'])) {
            $this->style['ind'] = true;
        }
        $style = array();
        $style[0] = $this->createPStyle($name, $styleOptions['pStyle']);
        $style[1] = $this->createCarStyle($name);
        return $style;
    }

    /**
     * Used by createCharacterStyle. Aboud adding Car to w:styleId
     * 
     * @access public
     * @param string $name
     * @return string
     */
    public function createCustomCharacterStyle($name, $styleOptions)
    {
        $this->style = $styleOptions;

        $carStyle = '<w:style ';
        $carStyle .= 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" ';
        $carStyle .= 'w:type="character" w:customStyle="1" w:styleId="' . $name . '">';
        $carStyle .= '<w:name w:val="' . $name . '"/>'; //check ids with spaces and non-standard characters
        //$carstyle .= '<w:basedOn w:val="DefaultParagraphFontPHPDOCX"/>';
        $carStyle .= '<w:link w:val="' . $name . '"/>';
        $carStyle .= '<w:uiPriority w:val="99"/><w:semiHidden/><w:unhideWhenUsed/><w:rsid w:val="006E0FDA"/>';
        $carStyle .= $this->XMLRPr();
        $carStyle .= '</w:style>';

        return $carStyle;
    }

    /**
     *
     * @access public
     * @return string
     */
    public function XMLPPr()
    {
        $sequence = array(
            'backgroundColor' => 'generateBackgroundColor',
            'bidi' => 'generateBidi',
            'border' => 'generateBorders',
            'contextualSpacing' => 'booleanProp',
            'ind' => 'generateIndentation',
            'jc' => 'valProp',
            'keepNext' => 'booleanProp',
            'keepLines' => 'booleanProp',
            'outlineLvl' => 'valProp',
            'pageBreakBefore' => 'booleanProp',
            'spacing' => 'generateSpacing',
            'tabPositions' => 'generateTabPositions',
            'textDirection' => 'booleanProp',
            'widowControl' => 'booleanProp',
            'wordWrap' => 'booleanProp',
        );

        $pPr = '<w:pPr>';
        foreach ($sequence as $key => $value) {
            if (isset($this->style[$key])) {
                if ($value == 'booleanProp') {
                    $pPr .= $this->generateBooleanProp($key);
                } else if ($value == 'valProp') {
                    $pPr .= $this->generateValProp($key);
                } else {
                    $pPr .= $this->$value();
                }
            }
        }
        $pPr .= '</w:pPr>';

        return $pPr;
    }

    /**
     *
     * @access public
     * @return string
     */
    public function XMLRPr()
    {
        $sequence = array(
            'b' => 'booleanProp',
            'caps' => 'booleanProp',
            'characterBorder' => 'characterBorderProp',
            'color' => 'valProp',
            'doubleStrikeThrough' => 'booleanTrueProp',
            'em' => 'valProp',
            'font' => 'generateFontProp',
            'highlight' => 'valProp',
            'i' => 'booleanProp',
            'position' => 'valProp',
            'rtl' => 'generateRtl',
            'scaling' => 'valProp',
            'smallCaps' => 'booleanProp',
            'strikeThrough' => 'booleanTrueProp',
            'sz' => 'valProp',
            'u' => 'valProp',
            'vanish' => 'booleanTrueProp',
        );

        $rPr = '<w:rPr>';
        foreach ($sequence as $key => $value) {
            if (isset($this->style[$key])) {
                if ($value == 'booleanProp') {
                    $rPr .= $this->generateBooleanProp($key);
                } else if ($value == 'booleanTrueProp') {
                    $rPr .= $this->generateBooleanTrueProp($key);
                } else if ($value == 'valProp') {
                    $rPr .= $this->generateValProp($key);
                } else if ($value == 'characterBorderProp') {
                    $rPr .= $this->generateCharacterBorderProp();
                } else {
                    $rPr .= $this->$value();
                }
            }
        }
        $rPr .= '</w:rPr>';

        return $rPr;
    }

    /**
     *
     * @access private
     * @param string $name
     * @return string
     */
    private function createCarStyle($name)
    {
        $carStyle = '<w:style ';
        $carStyle .= 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" ';
        $carStyle .= 'w:type="character"  w:customStyle="1" w:styleId="' . $name . 'Car">';
        $carStyle .= '<w:name w:val="' . $name . 'Car"/>'; //check ids with spaces and non-standard characters
        //$carstyle .= '<w:basedOn w:val="DefaultParagraphFontPHPDOCX"/>';
        $carStyle .= '<w:link w:val="' . $name . '"/>';
        $carStyle .= '<w:uiPriority w:val="99"/><w:semiHidden/><w:unhideWhenUsed/><w:rsid w:val="006E0FDA"/>';
        $carStyle .= $this->XMLRPr();
        $carStyle .= '</w:style>';

        return $carStyle;
    }

    /**
     *
     * @access private
     * @param string $name
     * @return string
     */
    private function createPStyle($name, $basedOn = '')
    {
        $pStyle = '<w:style ';
        $pStyle .= 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" ';
        $pStyle .= 'w:type="paragraph" w:styleId="' . $name . '">';
        $pStyle .= '<w:name w:val="' . $name . '"/>'; //check ids with spaces and non-standard characters
        if (!empty($basedOn)) {
            $pStyle .= '<w:basedOn w:val="' . $basedOn  . '"/>';
        }
        $pStyle .= '<w:link w:val="' . $name . 'Car"/>';
        $pStyle .= '<w:uiPriority w:val="99"/><w:semiHidden/><w:unhideWhenUsed/><w:rsid w:val="006E0FDA"/>';
        $pStyle .= $this->XMLPPr();
        $pStyle .= $this->XMLRPr();
        $pStyle .= '</w:style>';

        return $pStyle;
    }

    /**
     * @access private
     * @return string
     */
    private function generateBackgroundColor()
    {
        return '<w:shd w:val="clear" w:fill="' . $this->style['backgroundColor'] . '" />';
    }

    /**
     * @access private
     * @return string
     */
    private function generateBidi()
    {
        return '<w:bidi w:val="on" />';
    }

    /**
     *
     * @access private
     * @param string $tag
     * @return string
     */
    private function generateBooleanProp($tag)
    {
        if ($this->style[$tag] == 'on') {
            return '<w:' . $tag . '/>';
        }
    }

    /**
     *
     * @access private
     * @param string $tag
     * @return string
     */
    private function generateBooleanTrueProp($tag)
    {
        // normalize the tag names
        if ($tag == 'doubleStrikeThrough') {
            $tag = 'dstrike';
        } else if ($tag == 'strikeThrough') {
            $tag = 'strike';
        }

        return '<w:' . $tag . '/>';
    }

    /**
     *
     * @access private
     * @return string
     */
    private function generateBorders()
    {
        //Some auxiliary arrays
        $sides = array('top', 'left', 'bottom', 'right');
        $type = array('sz' => 4, 'color' => '000000', 'style' => 'single', 'space' => 2);

        foreach ($type as $key => $value) {
            foreach ($sides as $side) {
                if (isset($this->style['border_' . $side . '_' . $key])) {
                    $opt['border_' . $side . '_' . $key] = $this->style['border_' . $side . '_' . $key];
                } else if (isset($this->style['border_' . $key])) {
                    $opt['border_' . $side . '_' . $key] = $this->style['border_' . $key];
                } else {
                    $opt['border_' . $side . '_' . $key] = $value;
                }
            }
        }
        $strNode = '<w:pBdr>';
        foreach ($sides as $side) {
            $strNode .='<w:' . $side . ' w:val="' . $opt['border_' . $side . '_style'] . '" ';
            $strNode .= 'w:color="' . $opt['border_' . $side . '_color'] . '" ';
            $strNode .= 'w:sz="' . $opt['border_' . $side . '_sz'] . '" ';
            $strNode .= 'w:space="' . $opt['border_' . $side . '_space'] . '" />';
        }
        $strNode .= '</w:pBdr>';
        return $strNode;
    }

    /**
     *
     * @access private
     * @return string
     */
    private function generateCharacterBorderProp()
    {
        $value = $this->style['characterBorder'];

        if (!isset($value['color'])) {
            $value['color'] = 'auto';
        }
        if (!isset($value['spacing'])) {
            $value['spacing'] = 0;
        }
        if (!isset($value['type'])) {
            $value['type'] = 'single';
        }
        if (!isset($value['width'])) {
            $value['width'] = 4;
        }

        return '<w:bdr w:color="' . $value['color'] . '" w:space="' . $value['spacing'] . '" w:sz="' . $value['width'] . '" w:val="' . $value['type'] . '" />';
    }

    /**
     *
     * @access private
     * @return string
     */
    private function generateFontProp()
    {
        $font = $this->style['font'];
        return '<w:rFonts w:ascii="' . $font . '" w:hAnsi="' . $font . '" w:eastAsia="' . $font . '" w:cs="' . $font . '" />';
    }

    /**
     *
     * @access private
     * @return string
     */
    private function generateIndentation()
    {
        $strNode = '<w:ind ';
        if (isset($this->style['indent_left'])) {
            $strNode .= 'w:left="' . $this->style['indent_left'] . '" ';
        }
        if (isset($this->style['indent_right'])) {
            $strNode .= 'w:right="' . $this->style['indent_right'] . '" ';
        }
        if (isset($this->style['indent_firstLine'])) {
            $strNode .= 'w:firstLine="' . $this->style['indent_firstLine'] . '" ';
        }
        if (isset($this->style['hanging'])) {
            $strNode .= 'w:hanging="' . $this->style['hanging'] . '" ';
        }
        if (isset($this->style['firstLineIndent'])) {
            $strNode .= 'w:firstLine="' . $this->style['firstLineIndent'] . '" ';
        }

        $strNode .= ' />';

        return $strNode;
    }

    /**
     * @access private
     * @return string
     */
    private function generateRtl()
    {
        return '<w:rtl w:val="on" />';
    }

    /**
     *
     * @access private
     * @return string
     */
    private function generateSpacing()
    {
        $strNode = '<w:spacing ';
        if (isset($this->style['spacingTop'])) {
            $strNode .= 'w:before="' . $this->style['spacingTop'] . '" ';
        }
        if (isset($this->style['spacingBottom'])) {
            $strNode .= 'w:after="' . $this->style['spacingBottom'] . '" ';
        }
        if (isset($this->style['lineSpacing'])) {
            $strNode .= 'w:line="' . $this->style['lineSpacing'] . '" ';
        }
        $strNode .= 'w:lineRule="auto" ';
        $strNode .= ' />';

        return $strNode;
    }
    
    /**
     *
     * @access private
     * @return string
     */
    private function generateTabPositions()
    {
        if (isset($this->style['tabPositions']) && is_array($this->style['tabPositions'])) {
            $strNode = '<w:tabs>';
            foreach($this->style['tabPositions'] as $key => $value){
                $strNode .= '<w:tab w:val="' . $value['type'] . '" ';
                if(isset($value['leader'])){
                    $strNode .= 'w:leader="' . $value['leader'] . '" ';
                }
                $strNode .= 'w:pos="' . $value['position'] . '" />';
            }
            $strNode .= '</w:tabs>';
        }

        return $strNode;
    }

    /**
     *
     * @access private
     * @param string $tag
     * @return string
     */
    private function generateValProp($tag)
    {
        return '<w:' . $tag . ' w:val="' . $this->style[$tag] . '" />';
    }

}
