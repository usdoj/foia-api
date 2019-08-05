<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$styleOptions = array(
    'borderColor' => '00FF00',
    'borderTopWidth' => 24,
    'borderBottomWidth' => 24,
    'borderLeftColor' => '0000FF',
    'borderRightColor' => '0000FF',
    'borderInsideH' => 'nil',
    'borderInsideV' => 'dashed',
    'borderInsideHColor' => 'FF0000',
    'borderInsideVColor' => 'FF0000',
    'cellMargin' => array('top' => 400, 'left' => 150),
);

$docx->createTableStyle('myTableStyle', $styleOptions);

$textFragment = new Phpdocx\Elements\WordFragment($docx);
$text = array();
$text[] = array('text' => 'Fit text and ');
$text[] = array('text' => 'Word fragment', 'bold' => true);
$textFragment->addText($text);

// establish some row properties for the first row
$trProperties = array();
$trProperties[0] = array('minHeight' => 1000, 
    'tableHeader' => true
    );

$col_1_1 = array(
    'rowspan' => 4,
    'value' => '1_1', 
    'backgroundColor' => 'cccccc',
    'borderColor' => 'b70000',
    'border' => 'single',
    'borderTopColor' => '0000FF',
    'borderWidth' => 16,
    'cellMargin' => 200,
);

$col_2_2 = array(
    'rowspan' => 2, 
    'colspan' => 2, 
    'width' => 200,
    'value' => $textFragment, 
    'backgroundColor' => 'ffff66',
    'borderColor' => 'b70000',
    'border' => 'single',
    'cellMargin' => 200,
    'fitText' => 'on',
    'vAlign' => 'bottom',
);

$col_2_4 = array(
    'rowspan' => 3,
    'value' => 'Some rotated text', 
    'backgroundColor' => 'eeeeee',
    'borderColor' => 'b70000',
    'border' => 'single',
    'borderWidth' => 16,
    'textDirection' => 'tbRl',
);

$options = array(
    'columnWidths' => array(400,1400,400,400,400), 
    'border' => 'single', 
    'borderWidth' => 4, 
    'borderColor' => 'cccccc', 
    'borderSettings' => 'inside',
    'float' => array(
        'align' => 'right', 
        'textMargin_top' => 300, 
        'textMargin_right' => 400, 
        'textMargin_bottom' => 300, 
        'textMargin_left' => 400,
    ),
    'tableStyle' => 'myTableStyle',
);
$values = array(
    array($col_1_1, '1_2', '1_3', '1_4', '1_5'),
    array($col_2_2, $col_2_4, '2_5'),
    array('3_5'),
    array('4_2', '4_3', '4_5'),
);

$docx->addTable($values, $options, $trProperties);

$docx->addTable($valuesTable, $paramsTable);

$docx->createDocx('example_createTableStyle_2');