<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$docx->addText('Add a chart in 2D with a color scheme, the table data and a number format:');

$data = array(
    'legend' => array('Legend 1', 'Legend 2', 'Legend 3'),
    'data' => array(
        array(
            'name' => 'data 1',
            'values' => array(10, 20, 5),
        ),
        array(
            'name' => 'data 2',
            'values' => array(20, 60, 3),
        ),
        array(
            'name' => 'data 3',
            'values' => array(50, 33, 7),
        ),
    ),
);
$paramsChart = array(
    'data' => $data,
    'type' => 'barChart',
    'color' => 5,
    'sizeX' => 15,
    'sizeY' => 10,
    'chartAlign' => 'center',
    'legendPos' => 'none',
    'legendOverlay' => '0',
    'border' => '1',
    'hgrid' => '1',
    'vgrid' => '0',
    'showTable' => '1',
    'formatCode' => '#,##0.00',
);
$docx->addChart($paramsChart);

$docx->createDocx('example_addChart_16');