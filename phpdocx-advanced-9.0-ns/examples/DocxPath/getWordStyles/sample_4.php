<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/DOCXPathTemplate.docx');

$referenceNode = array(
    'type' => 'style',
    'contains' => 'ListParagraph',
);

$contents = $docx->getWordStyles($referenceNode);

print_r($contents);