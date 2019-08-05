<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$latinListOptions = array();
$latinListOptions[0]['type'] = 'lowerLetter';
$latinListOptions[0]['format'] = '%1.';
$latinListOptions[0]['bold'] = 'on';
$latinListOptions[0]['color'] = 'FF0000';
$latinListOptions[0]['align'] = 'right';
$latinListOptions[1]['type'] = 'lowerRoman';
$latinListOptions[1]['format'] = '%1.%2.';
$latinListOptions[1]['bold'] = 'on';
$latinListOptions[1]['color'] = '00FF00';
$latinListOptions[1]['underline'] = 'single';
$latinListOptions[1]['align'] = 'right';

$docx->createListStyle('myList1', $latinListOptions);

$myList = array('item 1', array('subitem 1.1', 'subitem 1.2'), 'item 2');

$docx->addList($myList, 'myList1');

$latinListOptions[0]['align'] = 'left';
$latinListOptions[1]['align'] = 'left';

$docx->createListStyle('myList2', $latinListOptions);

$docx->addList($myList, 'myList2');

$latinListOptions[0]['align'] = 'center';
$latinListOptions[0]['position'] = 5;
$latinListOptions[1]['align'] = 'center';
$latinListOptions[1]['position'] = 5;

$docx->createListStyle('myList3', $latinListOptions);

$docx->addList($myList, 'myList3');

$latinListOptions[0]['align'] = 'center';
$latinListOptions[0]['position'] = -24;
$latinListOptions[1]['align'] = 'center';
$latinListOptions[1]['position'] = -24;

$docx->createListStyle('myList4', $latinListOptions);

$docx->addList($myList, 'myList4');

$docx->createDocx('example_createListStyle_3');