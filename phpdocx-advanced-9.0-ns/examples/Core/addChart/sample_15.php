<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$docx->addText('We will now add a chart from a XLSX:');

$paramsChart = array(
    'externalXLSX' => array(
        'src' => '../../files/Book.xlsx',
    ),
    'sizeX' => 10,
    'sizeY' => 5,
);
$docx->addChart($paramsChart);

$docx->createDocx('example_addChart_15');