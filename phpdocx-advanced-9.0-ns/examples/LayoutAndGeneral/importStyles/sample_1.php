<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();
// you may first check the available styles using the parseStyles('../files/TemplateStyles.docx') method

$docx->importStyles('../../files/TemplateStyles.docx', 'merge', array('crazyStyle'));

$docx->addText('This is the resulting paragraph with the "CrazyStyle".', array('pStyle' => 'crazyStyle'));

// you may also import a complete XML style sheet by
// $docx->importStyles('../files/TemplateStyles.docx', 'replace');

$docx->createDocx('example_importStyles_1');