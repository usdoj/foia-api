<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

// create a Word fragment to insdert in the default header
$numbering = new Phpdocx\Elements\WordFragment($docx, 'defaultHeader');
// set some formatting options
$options = array(
    'textAlign' => 'right',
    'bold' => true,
    'sz' => 14,
    'color' => 'B70000',
);
$numbering->addPageNumber('numerical', $options);

$docx->addHeader(array('default' => $numbering));

// include a couple of pages to better illustrate the example
$docx->addText('This is the first page.');
$docx->addBreak(array('type' => 'page'));
$docx->addText('This is the second page.');
$docx->addBreak(array('type' => 'page'));
$docx->addText('This is the third page.');

$docx->createDocx('example_addPageNumber_1');