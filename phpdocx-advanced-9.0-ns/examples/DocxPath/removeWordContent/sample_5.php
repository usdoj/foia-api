<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/docxpath/headings.docx');

$referenceNode = array(
    'type' => 'paragraph',
    'occurrence' => 1,
    'attributes' => array('w:outlineLvl' => array('w:val' => 2)),
);

$docx->removeWordContent($referenceNode);

$docx->createDocx('example_removeWordContent_5');