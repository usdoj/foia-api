<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/docxpath/sections.docx');

$referenceToBeCloned = array(
    'type' => 'paragraph',
    'occurrence' => 2,
    'contains' => 'This is',
);

$referenceNodeTo = array(
    'type' => 'section',
    'occurrence' => 1,
);

$docx->cloneWordContent($referenceToBeCloned, $referenceNodeTo, 'before');

$docx->createDocx('example_cloneWordContent_4');