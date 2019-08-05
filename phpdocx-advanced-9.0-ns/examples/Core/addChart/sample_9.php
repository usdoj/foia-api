<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$docx->addText('We will now add a doughnut chart to the Word document:');

$data = array(
    'data' => array(
        array(
            'name' => 'data 1',
            'values' => array(20),
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
        array(
            'name' => 'data 5',
            'values' => array(5),
        ),
    ),
);

$paramsChart = array(
    'data' => $data,
    'type' => 'doughnutChart',
    'showPercent' => true,
    'explosion' => 10,
    'holeSize' => 25,
    'sizeX' => 12,
    'sizeY' => 10,
    'chartAlign' => 'center',
    'color' => '2',
    'legendPos' => 'r',
    'legendOverlay' => true,
    'showTable' => true,
);
$docx->addChart($paramsChart);

$docx->createDocx('example_addChart_9');