<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

// create a Word fragment with a TOC
$text = new Phpdocx\Elements\WordFragment($docx);
$text->addText('Table of Contents', array('bold' => true, 'fontSize' => 14));

$legend = array(
    'text' => 'Click here to update the TOC', 
    'color' => 'B70000', 
    'bold' => true, 
    'fontSize' => 12,
);
$toc = new Phpdocx\Elements\WordFragment($docx);
$toc->addTableContents(array('autoUpdate' => true), $legend, '../../files/crazyTOC.docx');

$text = array();

$text[] = array(
    'text' => 'Write a text string: ',
    'bold' => true
    );
$text[] = $text;
$text[] = $toc;

$docx->addText($text);

// add some headings so they show up in the TOC
$docx->addText('Chapter 1', array('pStyle' => 'Heading1PHPDOCX'));
$docx->addText('Section', array('pStyle' => 'Heading2PHPDOCX'));
$docx->addText('Another TOC entry', array('pStyle' => 'Heading3PHPDOCX'));

$docx->createDocx('example_addTableContents_2');