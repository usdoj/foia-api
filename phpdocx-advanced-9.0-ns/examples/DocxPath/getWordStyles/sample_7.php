<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/SimpleExample.docx');

$referenceNode = array(
    'type' => 'table',
);

$contents = $docx->getWordStyles($referenceNode);

//print_r($contents);

$referenceNode = array(
    'type' => 'table-row',
    'parent' => 'w:tbl/',
    'occurrence' => 1,
);

$contents = $docx->getWordStyles($referenceNode);

print_r($contents);

$referenceNode = array(
    'type' => 'table-cell',
    'parent' => 'w:tbl/w:tr/',
    'occurrence' => '1..2',
);

$contents = $docx->getWordStyles($referenceNode);

print_r($contents);