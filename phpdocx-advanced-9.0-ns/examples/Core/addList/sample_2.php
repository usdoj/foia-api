<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$itemList = array(
    'Line 1',
    array(
        'Line A',
        'Line B',
        'Line C'
    ),
    'Line 2',
    'Line 3',
);

// set the style type to 2: ordered list
$docx->addList($itemList, 2);

$docx->createDocx('example_addList_2');