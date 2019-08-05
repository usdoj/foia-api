<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

// create a Word fragment to insert in the footer
$numbering = new Phpdocx\Elements\WordFragment($docx, 'defaultFooter');
// set some formatting options
$options = array(
    'textAlign' => 'right',
    'bold' => true,
    'sz' => 14,
    'color' => 'B70000',
);
$numbering->addPageNumber('numerical', $options);

$docx->addFooter(array('default' => $numbering));

$text = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, ' .
    'sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut ' .
    'enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut' .
    'aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit ' .
    'in voluptate velit esse cillum dolore eu fugiat nulla pariatur. ' .
    'Excepteur sint occaecat cupidatat non proident, sunt in culpa qui ' .
    'officia deserunt mollit anim id est laborum.';

$docx->addText($text);

$paramsText = array(
    'b' => true
);

$docx->addText($text, $paramsText);

$docx->addSection('nextPage', 'A4', array('pageNumberType' => array('fmt' => 'lowerRoman', 'start' => 12)));

$docx->addText($text);

$paramsText = array(
    'b' => true
);

$docx->addText($text, $paramsText);

$docx->createDocx('example_addSection_2');