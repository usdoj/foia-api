<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$docx->addText('We will now add a 3D line chart with a title to the Word document:');

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
    'type' => 'line3DChart',
    'title' => 'Three dimensional line chart',
    'color' => '2',
    'perspective' => '30',
    'rotX' => '30',
    'rotY' => '30',
    'font' => 'Arial',
    'chartAlign' => 'center',
    'showTable' => 0,
    'sizeX' => '12',
    'sizeY' => '10',
    'legendPos' => 't',
    'legendOverlay' => '0',
    'haxLabel' => 'Horizontal label',
    'vaxLabel' => 'Vertical label',
    'haxLabelDisplay' => 'horizontal',
    'vaxLabelDisplay' => 'horizontal',
    'hgrid' => '3',
    'vgrid' => '1'
);
$docx->addChart($paramsChart);

$docx->addText('And now the same chart in 2D with a different color schem and options:');

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
    'type' => 'lineChart',
    'color' => '5',
    'chartAlign' => 'center',
    'showTable' => 0,
    'sizeX' => '12',
    'sizeY' => '10',
    'legendPos' => 'b',
    'legendOverlay' => '0',
    'haxLabel' => 'X Axis',
    'vaxLabel' => 'Y Axis',
    'haxLabelDisplay' => 'horizontal',
    'vaxLabelDisplay' => 'vertical',
    'hgrid' => '3',
    'vgrid' => '1'
);
$docx->addChart($paramsChart);

$docx->createDocx('example_addChart_4');