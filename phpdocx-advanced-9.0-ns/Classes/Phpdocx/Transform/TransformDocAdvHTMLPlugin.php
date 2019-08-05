<?php
namespace Phpdocx\Transform;

/**
 * Transform DOCX to HTML using native PHP classes. Abstract class
 *
 * @category   Phpdocx
 * @package    trasform
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @version    2019.01.31
 * @link       https://www.phpdocx.com
 */

abstract class TransformDocAdvHTMLPlugin
{
    /**
     * Base CSS
     * @var string
     */
    protected $baseCSS;

    /**
     * Base HTML
     * @var string
     */
    protected $baseHTML;

    /**
     * Base JavaScript
     * @var string
     */
    protected $baseJavaScript;

    /**
     * Base Meta
     * @var string
     */
    protected $baseMeta;

    /**
     * Conversion factor, used by the transformSizes method
     * @var float
     */
    protected $conversionFactor;

    /**
     * Generate section tags
     * @var bool
     */
    protected $generateSectionTags = true;

    /**
     * Images as base64
     * @var bool
     */
    protected $imagesAsBase64;

    /**
     * Target folder for images and other external contents. Not used for images is $imagesAsBase64 is true
     * @var string
     */
    protected $outputFilesPath;

    /**
     * Conversion unit
     * @var int
     */
    protected $unit;

    /**
     * Generate class name to be added to tags
     */
    abstract public function generateClassName();

    /**
     * Transform colors
     * @param string $color
     * @return string New color
     */
    abstract public function transformColors($color);

    /**
     * Transform content sizes
     * @param string $value  OOXML size
     * @param string $source OOXML type size
     * @param string $target Target size
     * @return string HTML/CSS size
     */
    abstract public function transformSizes($value, $source, $target = null);

    /**
     * Getter $baseCSS
     * @return string
     */
    public function getBaseCSS()
    {
        return $this->baseCSS;
    }
    /**
     * Setter $baseCSS
     * @param string $baseCSS
     */
    public function setBaseCSS($baseCSS)
    {
        $this->baseCSS = $baseCSS;
    }

    /**
     * Getter $baseHTML
     * @return string
     */
    public function getBaseHTML()
    {
        return $this->baseHTML;
    }
    /**
     * Setter $baseHTML
     * @param string $baseHTML
     */
    public function setBaseHTML($baseHTML)
    {
        $this->baseHTML = $baseHTML;
    }

    /**
     * Getter $baseJavaScript
     * @return string
     */
    public function getBaseJavaScript()
    {
        return $this->baseJavaScript;
    }
    /**
     * Setter $baseJavaScript
     * @param string $baseJavaScript
     */
    public function setBaseJavaScript($baseJavaScript)
    {
        $this->baseJavaScript = $baseJavaScript;
    }

    /**
     * Getter $baseMeta
     * @return string
     */
    public function getBaseMeta()
    {
        return $this->baseMeta;
    }
    /**
     * Setter $baseMeta
     * @param string $baseMeta
     */
    public function setBaseMeta($baseMeta)
    {
        $this->baseMeta = $baseMeta;
    }

    /**
     * Getter $conversionFactor
     * @return float
     */
    public function getConversionFactor()
    {
        return $this->conversionFactor;
    }
    /**
     * Setter $conversionFactor
     * @param float $conversionFactor
     */
    public function setConversionFactor($conversionFactor)
    {
        $this->conversionFactor = $conversionFactor;
    }

    /**
     * Getter extra class value
     * @return string
     */
    public function getExtraClass($tag)
    {
        if (isset($this->extraClasses[$tag])) {
            return $this->extraClasses[$tag];
        }
    }
    /**
     * Getter $extraClasses
     * @return string
     */
    public function getExtraClasses()
    {
        return $this->extraClasses;
    }

    /**
     * Setter $extraClasses
     * @param string $tag
     * @param string $class
     */
    public function setExtraClasses($tag, $class)
    {
        $this->extraClasses[$tag] = $class;
    }
    /**
     * Getter $generateSectionTags
     * @return bool
     */
    public function getGenerateSectionTags()
    {
        return $this->generateSectionTags;
    }

    /**
     * Setter $generateSectionTags
     * @param bool $generateSectionTags
     */
    public function setGenerateSectionTags($generateSectionTags)
    {
        $this->generateSectionTags = $generateSectionTags;
    }

    /**
     * Getter $imagesAsBase64
     * @return bool
     */
    public function getImagesAsBase64()
    {
        return $this->imagesAsBase64;
    }
    /**
     * Setter $imagesAsBase64
     * @param bool $imagesAsBase64
     */
    public function setImagesAsBase64($imagesAsBase64)
    {
        $this->imagesAsBase64 = $imagesAsBase64;
    }

    /**
     * Getter $outputFilesPath
     * @return string
     */
    public function getOutputFilesPath()
    {
        return $this->outputFilesPath;
    }
    /**
     * Setter $outputFilesPath
     * @param string $outputFilesPath
     */
    public function setOutputFilesPath($outputFilesPath)
    {
        $this->outputFilesPath = $outputFilesPath;
    }

    /**
     * Getter $tag value
     * @param string $tag
     * @return string
     */
    public function getTag($tag)
    {
        return $this->tags[$tag];
    }
    /**
     * Setter $setTag
     * @param string $tag
     * @param string $value
     */
    public function setTag($tag, $value)
    {
        $this->tags[$tag] = $value;
    }
    /**
     * Getter $tags
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Getter $unit
     * @return int
     */
    public function getUnit()
    {
        return $this->unit;
    }
    /**
     * Setter $setUnit
     * @param int $unit
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;
    }
}
