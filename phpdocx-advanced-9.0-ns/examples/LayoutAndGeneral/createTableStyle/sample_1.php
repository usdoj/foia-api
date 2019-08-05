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

$valuesTable = array(
    array(11, 12, 13, 14),
    array(21, 22, 23, 24),
    array(31, 32, 33, 34),
);

$paramsTable = array(
    'tableStyle' => 'myTableStyle',
);

$docx->addTable($valuesTable, $paramsTable);

$docx->createDocx('example_createTableStyle_1');