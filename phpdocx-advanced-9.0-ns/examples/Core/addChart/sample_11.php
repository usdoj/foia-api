<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$docx->addText('We will now add a surface chart to the Word document:');

$data = array(
    'legend' => array('Series 1', 'Series 2', 'Series 3'),
    'data' => array(
        array(
            'name' => 'Value1',
            'values' => array(4.3, 2.4, 2),
        ),
        array(
            'name' => 'Value2',
            'values' => array(2.5, 4.4, 2),
        ),
        array(
            'name' => 'Value3',
            'values' => array(3.5, 1.8, 3),
        ),
        array(
            'name' => 'Value4',
            'values' => array(4.5, 2.8, 5),
        ),
        array(
            'name' => 'Value5',
            'values' => array(5, 2, 3),
        ),
    ),
);

$paramsChart = array(
    'data' => $data,
    'type' => 'surfaceChart',
    'legendpos' => 't',
    'legendoverlay' => false,
    'sizeX' => 12,
    'sizeY' => 8,
    'chartAlign' => 'center',
    'color' => 2,
);
$docx->addChart($paramsChart);

$docx->createDocx('example_addChart_11');