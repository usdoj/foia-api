<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Utilities\DocxUtilities();
$source = '../../files/example_area_chart.docx';
$target = 'example_area_chart_replace_data.docx';

$data = array();
$data[0] = array(
    'title' => 'New title',
    'legends' => array(
        'new legend',
    ),
    'categories' => array(
        'cat 1',
        'cat 2',
        'cat 3',
        'cat 4',
    ),
    'values' => array(
        array(25),
        array(20),
        array(15),
        array(10)
    ),
);
$data[1] = array(
    'title' => 'Other title',
    'legends' => array(
        'legend 1',
        'legend 2',
        'legend 3',
    ),
    'categories' => array(
        'other cat 1',
        'other cat 2',
        'other cat 3',
        'other cat 4',
    ),
    'values' => array(
        array(25, 10, 5),
        array(20, 5, 4),
        array(15, 0, 3),
        array(10, 15, 2),
    ),
);

$docx->replaceChartData($source, $target, $data);