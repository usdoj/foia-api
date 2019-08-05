<?php
namespace Phpdocx\Transform;

/**
 * Transform DOCX to HTML using native PHP classes. Default plugin
 *
 * @category   Phpdocx
 * @package    trasform
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @version    2019.01.31
 * @link       https://www.phpdocx.com
 */

class TransformDocAdvHTMLDefaultPlugin extends TransformDocAdvHTMLPlugin
{

    /**
     * Conversion factor, used by the transformSizes method
     * @var float
     */
    protected $conversionFactor = 1.3;

    /**
     * Generate section tags
     * @var boolean
     */
    protected $generateSectionTags = true;

    /**
     * Add image src as base64
     * @var boolean
     */
    protected $imagesAsBase64 = true;

    /**
     * Target folder for images and other external contents. Not used for images is $imagesAsBase64 is true
     * @var string
     */
    protected $outputFilesPath = 'output_files/';

    /**
     * OOXML => HTML tags
     * @var array
     */
    protected $tags = array(
        'bidi' => 'bidi',
        'br' => 'br',
        'comboBox' => 'select',
        'comboBoxItem' => 'option',
        'footer' => 'footer',
        'header' => 'header',
        'heading' => 'h',
        'hyperlink' => 'a',
        'image' => 'img',
        'itemList' => 'li',
        'orderedList' => 'ol',
        'paragraph' => 'p',
        'section' => 'section',
        'span' => 'span',
        'subscript' => 'sub',
        'superscript' => 'sup',
        'table' => 'table',
        'tc' => 'td',
        'tr' => 'tr',
        'unorderedList' => 'ul',
    );

    protected $unit = 'px';

    /**
     * Constructor. Init HTML, CSS, meta and javascript base contents
     */
    public function __construct() {
        $this->baseCSS = '<style>p { margin-top: 0px;margin-bottom: 0px;}</style>';
        $this->baseHTML = '<!DOCTYPE html><html>';
        $this->baseJavaScript = '';
        $this->baseMeta = '<meta charset="UTF-8">';
    }

    /**
     * Generate class name to be added to tags
     * @return string Class name
     */
    public function generateClassName()
    {
        return str_replace('.', '_', uniqid('docx_', true));
    }

    /**
     * Transform colors
     * @return string New color
     */
    public function transformColors($color) {
        $colorTarget = $color;

        if ($color == 'auto') {
            $colorTarget = '000000';
        }

        return $colorTarget;
    }

    /**
     * Transform content sizes
     * @param  string $value  OOXML size
     * @param  string $source OOXML type size
     * @param  string $target Target size
     * @return string HTML/CSS size
     */
    public function transformSizes($value, $source, $target = null)
    {
        $returnValue = 0;
        
        if ($target === null) {
            $target = $this->unit;
        }

        if ($source == 'twips') {
            if ($target == 'px') {
                if ($value) {
                    $returnValue = ($value / 20) * $this->conversionFactor;
                }
            }
        }

        if ($source == 'eights') {
            if ($target == 'px') {
                if ($value) {
                    $returnValue = ($value / 8) * $this->conversionFactor;
                }
            }

            // minimum value
            if ($returnValue < 2) {
                $returnValue = 2;
            }
        }

        if ($source == 'half-points') {
            if ($target == 'px') {
                if ($value) {
                    $returnValue = ($value / 2) * $this->conversionFactor;
                }
            }
        }

        if ($source == 'pts') {
            if ($target == 'px') {
                if ($value) {
                    $returnValue = $value * $this->conversionFactor;
                }
            }
        }

        if ($source == 'fifths-percent') {
            if ($target == '%') {
                if ($value) {
                    $returnValue = ($value / 50);
                }
            }
        }
        
        return (string)$returnValue . $target;
    }
}
