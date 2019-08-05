<?php

namespace Phpdocx\Utilities;

/**
 * Storage DOCX internal structure
 * 
 * @category   Phpdocx
 * @package    utilities
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @version    2019.01.31
 * @link       https://www.phpdocx.com
 */
class DOCXStructure
{
    /**
     * DOCX structure
     * @access private
     * @var array
     */
    private $docxStructure = array();

    /**
     * Parse a DOCX file
     * 
     * @access public
     * @param string $path File path
     */
    public function __construct() { }

    /**
     * Getter docxStructure
     * @param string $format array or stream
     * @return mixed DOCX structure
     */
    public function getDocx($format) {
        return $docxStructure;
    }

    /**
     * Add new content to the DOCX
     * @param string $internalFilePath Path in the DOCX
     * @param string $content Content to be added
     */
    public function addContent($internalFilePath, $content)
    {
        $this->docxStructure[$internalFilePath] = $content;
    }

    /**
     * Add a new file to the DOCX
     * @param string $internalFilePath Path in the DOCX
     * @param string $file File path to be added
     */
    public function addFile($internalFilePath, $file)
    {
        $this->docxStructure[$internalFilePath] = file_get_contents($file);
    }

    /**
     * Delete content in the DOCX
     * @param string $internalFilePath Path in the DOCX
     */
    public function deleteContent($internalFilePath)
    {
        if (isset($this->docxStructure[$internalFilePath])) {
            unset($this->docxStructure[$internalFilePath]);
        }
    }

    /**
     * Get existing content from the DOCX
     * @param string $internalFilePath Path in the DOCX
     * @return mixed File content or false
     */
    public function getContent($internalFilePath)
    {
        if (isset($this->docxStructure[$internalFilePath])) {
            return $this->docxStructure[$internalFilePath];
        }

        return false;
    }

    /**
     * Get existing content from the DOCX by its type
     * @param string $internalFilePath Path in the DOCX
     * @return array Contents
     */
    public function getContentByType($type)
    {
        // get the main Content types XML
        $contentTypesXML = $this->getContent('[Content_Types].xml');

        // load XML content
        $contentTypesDOM = new \DOMDocument();
        $optionEntityLoader = libxml_disable_entity_loader(true);
        $contentTypesDOM->loadXML($contentTypesXML);
        libxml_disable_entity_loader($optionEntityLoader);

        $contentTypesXPath = new \DOMXPath($contentTypesDOM);
        $contentTypesXPath->registerNamespace('xmlns', 'http://schemas.openxmlformats.org/package/2006/content-types');
        
        $queryXpath = '';
        switch ($type) {
            case 'headers':
                $queryXpath = '//xmlns:Override[@ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"]';
                break;
            case 'footers':
                $queryXpath = '//xmlns:Override[@ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"]';
                break;
            case 'document':
            case 'default':
                $queryXpath = '//xmlns:Override[@ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"]';
                break;
        }


        $xpathEntries = $contentTypesXPath->query($queryXpath);

        $contents = array();
        foreach ($xpathEntries as $xpathEntry) {
            $contents[] = array(
                'content' => $this->getContent(substr($xpathEntry->getAttribute('PartName'), 1)),
                'name' => substr($xpathEntry->getAttribute('PartName'), 1),
            );
        }

        return $contents;
    }

    /**
     * Parse an existing DOCX
     * @param string $path File path
     */
    public function parseDocx($path)
    {
        $zip = new \ZipArchive();

        if ($zip->open($path) === TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $fileName = $zip->getNameIndex($i);
                $this->docxStructure[$zip->getNameIndex($i)] = $zip->getFromName($fileName);
            }
        } else {
            throw new \Exception('Error while trying to open the (base) template as a zip file');
        }

        // if there's no document.xml file, get application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml and rels files and rename them
        if (!isset($this->docxStructure['word/document.xml'])) {
            // parse the Content_Types
            $contentTypesContent = $this->docxStructure['[Content_Types].xml'];
            $contentTypesXml = simplexml_load_string($contentTypesContent);

            // get the main document XML and rename it to word/document.xml
            $contentTypesDom = dom_import_simplexml($contentTypesXml);
            $contentTypesXpath = new \DOMXPath($contentTypesDom->ownerDocument);
            $contentTypesXpath->registerNamespace('ct', 'http://schemas.openxmlformats.org/package/2006/content-types');
            $relsEntries = $contentTypesXpath->query('//ct:Override[@ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"]');
            $partNameDocumentMainXML = $relsEntries->item(0)->getAttribute('PartName');
            $partNameDocumentMainXMLNameStructure = substr($partNameDocumentMainXML, 1);
            $this->docxStructure['word/document.xml'] = $this->docxStructure[$partNameDocumentMainXMLNameStructure];
            $partNameDocumentMainRelsXMLNameStructure = str_replace('word/', 'word/_rels/', $partNameDocumentMainXMLNameStructure) . '.rels';
            $this->docxStructure['word/_rels/document.xml.rels'] = $this->docxStructure[$partNameDocumentMainRelsXMLNameStructure];
            unset($this->docxStructure[$partNameDocumentMainXMLNameStructure]);
            unset($this->docxStructure[$partNameDocumentMainRelsXMLNameStructure]);
            
            // replace the previous main document name by the new one
            $this->docxStructure['[Content_Types].xml'] = str_replace('"/'.$partNameDocumentMainXMLNameStructure.'"', '"/word/document.xml"', $this->docxStructure['[Content_Types].xml']);
            $this->docxStructure['_rels/.rels'] = str_replace('"/'.$partNameDocumentMainXMLNameStructure.'"', '"/word/document.xml"', $this->docxStructure['_rels/.rels']);
        }
    }

    /**
     * Save docxStructure as ZIP
     * @param string $path File path
     * @param bool $forceFile Force DOCX as file, needed for charts when working with streams
     * @return DOCXStructure Self
     */
    public function saveDocx($path, $forceFile = false) {
        // check if the path has as extension
        if(substr($path, -5) !== '.docx') {
            $path .= '.docx';
        }

        // return the structure object instead of creating the file
        if (file_exists(dirname(__FILE__) . '/ZipStream.php') && \Phpdocx\Create\CreateDocx::$returnDocxStructure == true) {
            return $this;
        }

        // check if stream mode is true
        if (file_exists(dirname(__FILE__) . '/ZipStream.php') && \Phpdocx\Create\CreateDocx::$streamMode === true && $forceFile === false) {
            $docxFile = new \Phpdocx\Utilities\ZipStream();

            foreach ($this->docxStructure as $key => $value) {
                $docxFile->addFile($key, $value);
            }
            $docxFile->generateStream($path);
        } else {
            $docxFile = new \ZipArchive();

            // if dest file exits remove it to avoid duplicate content
            if (file_exists($path) && is_writable($path)) {
                unlink($path);
            }

            if ($docxFile->open($path, \ZipArchive::CREATE) === TRUE) {
                foreach ($this->docxStructure as $key => $value) {
                    $docxFile->addFromString($key, $value);
                }

                $docxFile->close();
            } else {
                throw new \Exception('Error while trying to write to ' . $path);
            }
        }

        // return the structure object after creating the file
        return $this;
    }
    
}