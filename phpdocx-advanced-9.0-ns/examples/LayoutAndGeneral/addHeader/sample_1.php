<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

// create a Word fragment with an image to be inserted in the header of the document
$imageOptions = array(
	'src' => '../../img/image.png',
	'dpi' => 300,
);

$headerImage = new Phpdocx\Elements\WordFragment($docx, 'defaultHeader');
$headerImage->addImage($imageOptions);

$docx->addHeader(array('default' => $headerImage));
// add some text
$docx->addText('This document has a header with just one image.');

$docx->createDocx('example_addHeader_1');