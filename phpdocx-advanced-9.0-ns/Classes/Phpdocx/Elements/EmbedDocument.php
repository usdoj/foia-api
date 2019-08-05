<?php
namespace Phpdocx\Elements;
/**
 * Embed documents in DOCX.
 *
 * @category   Phpdocx
 * @package    elements
 * @package    transform
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @version    2017.09.11
 * @link       https://www.phpdocx.com
 */
interface EmbedDocument
{

    /**
     * Return current Id.
     *
     * @abstract
     * @return void
     */
    function getId();

    /**
     * Embed content or file.
     *
     * @abstract
     * @return void
     */
    function embed($matchSource = null);

    /**
     * Generate w:altChunk.
     *
     * @abstract
     * @return void
     */
    function generateALTCHUNK($matchSource = null);
}
