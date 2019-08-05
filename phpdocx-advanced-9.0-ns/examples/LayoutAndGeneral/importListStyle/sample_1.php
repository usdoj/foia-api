<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$docx->importListStyle('../../files/TemplateStyleList.docx', '1', 'myliststyle');

$itemList = array(
    'Line 1',
    'Line 2',
    'Line 3',
    'Line 4',
    'Line 5'
);

$docx->addList($itemList, 'myliststyle');

$docx->createDocx('example_importListStyle_1');