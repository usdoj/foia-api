<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$style = array(
    'bold' => true,
	'color' => 'ff0000',
    'font' => 'Arial',
    'fontSize' => 18,
    'italic' => true,
    'position' => 6,
	'underline' => 'single',
);

$docx->createCharacterStyle('myStyle', $style);

$text = array();
$text[] =
    array(
        'text' => 'A text in red color with the character style',
        'rStyle' => 'myStyle',
);
$text[] =
    array(
        'text' => ' other text set as bold but without the character style.',
        'bold' => true,
);

$docx->addText($text);

$docx->createDocx('example_createCharacterStyle_1');