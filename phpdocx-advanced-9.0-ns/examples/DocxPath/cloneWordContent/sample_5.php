<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/docxpath/tables.docx');

$referenceToBeCloned = array(
    'type' => 'paragraph',
    'parent' => '/w:tc/',
    'occurrence' => 4,
);

$referenceNodeTo = array(
    'type' => 'paragraph',
    'parent' => '/w:tc/',
    'occurrence' => 8,
);

$docx->cloneWordContent($referenceToBeCloned, $referenceNodeTo, 'after');

$docx->createDocx('example_cloneWordContent_5');