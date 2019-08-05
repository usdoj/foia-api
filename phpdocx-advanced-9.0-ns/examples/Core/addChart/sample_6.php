<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$docx->addText('We will now add a radar chart to the Word document:');

$data = array(
    'legend' => array('Series 1', 'Series 2', 'Series 3'),
    'data' => array(
        array(
            'name' => 'data 1',
            'values' => array(10, 7, 5),
        ),
        array(
            'name' => 'data 2',
            'values' => array(20, 60, 3),
        ),
        array(
            'name' => 'data 3',
            'values' => array(50, 33, 7),
        ),
        array(
            'name' => 'data 4',
            'values' => array(25, 0, 14),
        ),
    ),
);
$paramsChart = array(
    'data' => $data,
    'type' => 'radarChart',
    'style' => 'radar',
    'color' => '2',
    'chartAlign' => 'center',
    'sizeX' => '12',
    'sizeY' => '10',
    'legendPos' => 'r',
    'legendOverlay' => '0',
    'hgrid' => '1',
    'vgrid' => '1'
);
$docx->addChart($paramsChart);

$docx->addText('And now the same radar chart but with filled style:');

$data = array(
    'legend' => array('Series 1', 'Series 2', 'Series 3'),
    'data' => array(
        array(
            'name' => 'data 1',
            'values' => array(10, 7, 5),
        ),
        array(
            'name' => 'data 2',
            'values' => array(20, 60, 3),
        ),
        array(
            'name' => 'data 3',
            'values' => array(50, 33, 7),
        ),
        array(
            'name' => 'data 4',
            'values' => array(25, 0, 14),
        ),
    ),
);

$paramsChart = array(
    'data' => $data,
    'type' => 'radarChart',
    'style' => 'filled',
    'color' => '2',
    'chartAlign' => 'center',
    'sizeX' => '12',
    'sizeY' => '10',
    'legendPos' => 'r',
    'legendOverlay' => '0',
    'hgrid' => '1',
    'vgrid' => '1'
);
$docx->addChart($paramsChart);

$docx->createDocx('example_addChart_6');