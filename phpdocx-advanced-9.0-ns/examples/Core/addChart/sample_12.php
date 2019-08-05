<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$docx->addText('We will now add a 3D pie chart with a title styled to the Word document:');

$data = array(
    'data' => array(
        array(
            'name' => 'data 1',
            'values' => array(10),
        ),
        array(
            'name' => 'data 2',
            'values' => array(20),
        ),
        array(
            'name' => 'data 3',
            'values' => array(50),
        ),
        array(
            'name' => 'data 4',
            'values' => array(25),
        ),
    ),
);

$paramsChart = array(
    'data' => $data,
    'title' => 'My title',
    'type' => 'pie3DChart',
    'perspective' => 30,
    'color' => 2,
    'sizeX' => 10,
    'sizeY' => 5,
    'chartAlign' => 'center',
    'showPercent' => 1,
    'vgrid' => 0,
    'legendPos' => 'r',
    'font' => 'Arial',
    'stylesTitle' => array(
        'bold' => true,
        'color' => 'ff0000',
        'font' => 'Times New Roman',
        'fontSize' => 3600,
        'italic' => true,
    ),
);
$docx->addChart($paramsChart);

$docx->createDocx('example_addChart_12');