<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

// style options
$style = array(
	'color' => 'ff0000',
	'fontSize' => 14,
    'backgroundColor' => 'CCCCCC',
    'spacingBottom' => 10,
    'spacingTop' => 10,
    'underline' => 'dash',
    'textAlign' => 'right',
    'pageBreakBefore' => true,
);

// set document default styles
$docx->setDocumentDefaultStyles($style);

$text = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, ' .
    'sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut ' .
    'enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut' .
    'aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit ' .
    'in voluptate velit esse cillum dolore eu fugiat nulla pariatur. ' .
    'Excepteur sint occaecat cupidatat non proident, sunt in culpa qui ' .
    'officia deserunt mollit anim id est laborum.';

$docx->addText($text);

$docx->addText($text);

$docx->createDocx('example_setDocumentDefaultStyles_1');