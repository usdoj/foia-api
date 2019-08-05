<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

// create a Word fragment with an image to be inserted in the header of the document
$imageOptions = array(
	'src' => '../../img/image.png',
	'dpi' => 300,
);

$footerImage = new Phpdocx\Elements\WordFragment($docx, 'defaultFooter');
$footerImage->addImage($imageOptions);

$docx->addFooter(array('default' => $footerImage));
// add some text
$docx->addText('This document has a footer with just one image.');

$docx->createDocx('example_addFooter_1');