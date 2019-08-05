<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$item1 = new Phpdocx\Elements\WordFragment($docx);
$item1->addText('Line 1', array('pStyle' => 'Heading1PHPDOCX'));
$item2 = new Phpdocx\Elements\WordFragment($docx);
$item2->addText('Line 2', array('pStyle' => 'Heading2PHPDOCX'));
$item3 = new Phpdocx\Elements\WordFragment($docx);
$item3->addText('Line 3', array('pStyle' => 'Heading3PHPDOCX'));

$itemList = array(
    $item1,
    $item2,
    $item3,
);

$docx->addList($itemList, 1, array('useWordFragmentStyles' => true));

$docx->createDocx('example_addList_5');