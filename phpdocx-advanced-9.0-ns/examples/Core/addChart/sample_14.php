<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$docx->addText('We will now add a combo chart to the Word document:');

$chartFragment = new Phpdocx\Elements\WordFragment($docx);

$data = array(
    'legend' => array('Series 4', 'Series 5'),
    'data' => array(
        array(
            'name' => 'data A',
            'values' => array(40, 30),
        ),
        array(
            'name' => 'data B',
            'values' => array(50, 60),
        ),
        array(
            'name' => 'data C',
            'values' => array(10, 70),
        ),
        array(
            'name' => 'data D',
            'values' => array(20, 60),
        ),
    ),
);
$paramsChart = array(
    'data' => $data,
    'type' => 'lineChart',
    'symbol' => 'none',
    'smooth' => true,
    'returnChart' => true,
);
$comboChart = $chartFragment->addChart($paramsChart);

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
    'comboChart' => $comboChart,
    'type' => 'colChart',
    'color' => '2',
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
    'groupBar' => 'clustered',
    'legendPos' => 'none',
);
$docx->addChart($paramsChart);

$docx->createDocx('example_addChart_14');