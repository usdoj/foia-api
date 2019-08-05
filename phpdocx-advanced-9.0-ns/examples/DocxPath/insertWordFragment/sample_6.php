<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/docxpath/sections.docx');

$contentA = new Phpdocx\Elements\WordFragment($docx, 'document');
$contentA->addText('New text at the beginning');
$referenceNode = array(
    'type' => '*',
    'occurrence' => 1,
);
$docx->insertWordFragment($contentA, $referenceNode, 'before');

$contentB = new Phpdocx\Elements\WordFragment($docx, 'document');
$contentB->addText('New text second page');
$referenceNode = array(
    'type' => 'section',
    'occurrence' => 1,
);
$docx->insertWordFragment($contentB, $referenceNode, 'after');

$contentC = new Phpdocx\Elements\WordFragment($docx, 'document');
$contentC->addText('New text first page');
$referenceNode = array(
    'type' => 'section',
    'occurrence' => 1,
);
$docx->insertWordFragment($contentC, $referenceNode, 'before', true);

$contentD = new Phpdocx\Elements\WordFragment($docx, 'document');
$contentD->addText('New text at the end');
$referenceNode = array(
    'type' => '*',
    'occurrence' => -1,
);
$docx->insertWordFragment($contentD, $referenceNode, 'after');

$docx->createDocx('example_insertWordFragment_6');