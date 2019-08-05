<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$docx->addText('We will now add a line chart with trendlines to the Word document:');

$data = array(
    'legend' => array('Series 1', 'Series 2', 'Series 3'),
    'trendline' => array(
        array(
            'color' => '0000ff',
            'type' => 'log',
            'display_equation' => true,
            'display_rSquared' => true,
        ),
        array(),
        array(
            'color' => '0000ff',
            'type' => 'power',
            'line_style' => 'dot',
        ),
    ),
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
    'vgrid' => 0,
    'hgrid' => 0,
);
$docx->addChart($paramsChart);

$docx->createDocx('example_addChart_13');