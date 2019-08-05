<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

// custom Bullets
$latinListOptions = array();
$latinListOptions[0]['type'] = 'lowerLetter';
$latinListOptions[0]['format'] = '%1.';
$latinListOptions[0]['bold'] = 'on';
$latinListOptions[0]['color'] = 'FF0000';
$latinListOptions[1]['type'] = 'lowerRoman';
$latinListOptions[1]['format'] = '%1.%2.';
$latinListOptions[1]['bold'] = 'on';
$latinListOptions[1]['color'] = '00FF00';
$latinListOptions[1]['underline'] = 'single';

// create the list style with name: latin
$docx->createListStyle('myList', $latinListOptions);

// list items
$myList = array('item 1', array('subitem 1.1', 'subitem 1.2'), 'item 2');

// insert custom list into the Word document
$docx->addList($myList, 'myList');

$docx->createDocx('example_createListStyle_2');