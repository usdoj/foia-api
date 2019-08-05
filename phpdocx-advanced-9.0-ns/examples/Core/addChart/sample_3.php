<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$docx->addText('We will now add a 3D column chart "100% stacked" to the Word document:');

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
    'type' => 'col3DChart',
    'color' => '2',
    'perspective' => '40',
    'rotX' => '30',
    'rotY' => '30',
    'chartAlign' => 'center',
    'showTable' => 1,
    'sizeX' => '10',
    'sizeY' => '10',
    'legendPos' => 't',
    'legendOverlay' => '0',
    'border' => '1',
    'hgrid' => '0',
    'vgrid' => '0',
    'groupBar' => 'percentStacked'
);
$docx->addChart($paramsChart);

$docx->addText('And now the same chart with a different color scheme and perspective, without the data table and simply stacked:');

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
    'type' => 'col3DChart',
    'color' => '3',
    'perspective' => '10',
    'rotX' => '10',
    'rotY' => '10',
    'chartAlign' => 'center',
    'sizeX' => '10',
    'sizeY' => '10',
    'legendPos' => 'b',
    'legendOverlay' => '0',
    'border' => '1',
    'hgrid' => '3',
    'vgrid' => '0',
    'groupBar' => 'stacked'
);
$docx->addChart($paramsChart);

$docx->createDocx('example_addChart_3');