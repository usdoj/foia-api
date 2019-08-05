<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

// custom options
$latinListOptions = array();
$latinListOptions[0]['type'] = 'lowerLetter';
$latinListOptions[0]['format'] = '%1.';
$latinListOptions[1]['type'] = 'lowerRoman';
$latinListOptions[1]['format'] = '%1.%2.';

// create the list style with name: latin
$docx->createListStyle('latin', $latinListOptions);

// list items
$myList = array('item 1', array('subitem 1.1', 'subitem 1.2'), 'item 2');

// insert the custom list into the Word document
$docx->addList($myList, 'latin');

$docx->createDocx('example_createListStyle_1');