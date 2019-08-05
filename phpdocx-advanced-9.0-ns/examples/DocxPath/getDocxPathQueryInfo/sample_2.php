<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/DOCXPathTemplate.docx');

$referenceNode = array(
    'type' => 'paragraph',
    'contains' => 'heading',
);

$queryInfo = $docx->getDocxPathQueryInfo($referenceNode);

for ($i = 1; $i <= $queryInfo['length']; $i++) {
    $content = new Phpdocx\Elements\WordFragment($docx, 'document');

    $referenceNode = array(
        'type' => 'paragraph',
        'contains' => 'heading',
        'occurrence' => $i,
    );

    $content->addText('New text', array('sz' => 18));

    $docx->insertWordFragment($content, $referenceNode, 'after');
}

$docx->createDocx('example_getDocxPathQueryInfo_2');