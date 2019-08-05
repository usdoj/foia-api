<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$styleOptions = array(
    'tblStyleRowBandSize' => true,
    'pPrStyles' => array('indentLeft' => 150),
    'firstRowStyle' => array(
        'backgroundColor' => '60E1DE',
        'vAlign' => 'center',
    ),
    'lastRowStyle' => array(
        'backgroundColor' => 'AEB7B7',
        'bordertOPWidth' => 30,
    ),
    'band1HorzStyle'  => array(
        'backgroundColor' => 'CBDBDA',
    ),
    'lastColStyle' => array(
        'borderLeftColor' => '2F812F',
        'borderLeftWidth' => 12,
        'backgroundColor' => '6299D4',
        'rPrStyles' => array('bold' => true),
    ),
);

$docx->createTableStyle('myTableStyle', $styleOptions);

$textFragmentA = new Phpdocx\Elements\WordFragment($docx);
$textFragmentA->addText('A', array('textAlign' => 'center', 'color' => 'ffffff', 'bold' => true, 'fontSize' => 14));

$textFragmentB = new Phpdocx\Elements\WordFragment($docx);
$textFragmentB->addText('B', array('textAlign' => 'center', 'color' => 'ffffff', 'bold' => true, 'fontSize' => 14));

$textFragmentC = new Phpdocx\Elements\WordFragment($docx);
$textFragmentC->addText('C', array('textAlign' => 'center', 'color' => 'ffffff', 'bold' => true, 'fontSize' => 14));

$textFragmentD = new Phpdocx\Elements\WordFragment($docx);
$textFragmentD->addText('D', array('textAlign' => 'center', 'color' => 'ffffff', 'bold' => true, 'fontSize' => 14));

$valuesTable = array(
    array($textFragmentA, $textFragmentB, $textFragmentC, $textFragmentD),
    array(11, 12, 13, 14),
    array(21, 22, 23, 24),
    array(31, 32, 33, 34),
    array(41, 42, 43, 44),
    array(51, 52, 53, 54),
);

$paramsTable = array(
    'tableStyle' => 'myTableStyle',
    'conditionalFormatting' => array('firstRow' => true, 'lastCol' => true, 'lastRow' => true),
);

$docx->addTable($valuesTable, $paramsTable);

$docx->createDocx('example_createTableStyle_3');