<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$docx->addText('We will now add a composite pie chart to the Word document:');

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
        array(
            'name' => 'data 5',
            'values' => array(55),
        ),
        array(
            'name' => 'data 6',
            'values' => array(75),
        ),
        array(
            'name' => 'data 7',
            'values' => array(60),
        ),
        array(
            'name' => 'data 8',
            'values' => array(25),
        ),
    ),
);

$paramsChart = array(
    'data' => $data,
    'type' => 'ofPieChart',
    'title' => 'Pie of pie chart',
    'color' => '26',
    'showPercent' => 1,
    'sizeX' => 15,
    'sizeY' => 10,
    'chartAlign' => 'center',
    'font' => 'Times New Roman',
    'gapWidth' => 150,
    'secondPieSize' => 75,
    'splitType' => 'val',
    'splitPos' => 30.0,
);
$docx->addChart($paramsChart);

$docx->addText('And now the same chart but with a bar subgraph:');

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
        array(
            'name' => 'data 5',
            'values' => array(55),
        ),
        array(
            'name' => 'data 6',
            'values' => array(75),
        ),
        array(
            'name' => 'data 7',
            'values' => array(60),
        ),
        array(
            'name' => 'data 8',
            'values' => array(25),
        ),
    ),
);
$paramsChart = array(
    'data' => $data,
    'type' => 'ofPieChart',
    'subtype' => 'bar',
    'title' => 'Bar of pie chart',
    'color' => '2',
    'showPercent' => 1,
    'sizeX' => 15,
    'sizeY' => 10,
    'chartAlign' => 'center',
    'font' => 'Times New Roman',
    'gapWidth' => 150,
    'secondPieSize' => 75,
    'splitType' => 'val',
    'splitPos' => 30.0,
);
$docx->addChart($paramsChart);

$docx->createDocx('example_addChart_7');