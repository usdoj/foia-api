<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/docxpath/lists.docx');

$referenceNode = array(
    'type' => 'list',
    'occurrence' => -1,
);

$docx->removeWordContent($referenceNode);

$docx->createDocx('example_removeWordContent_7');