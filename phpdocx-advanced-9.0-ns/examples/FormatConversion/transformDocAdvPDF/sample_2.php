<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$valuesTable = array(
    array(
        11,
        12,
        13,
        14
    ),
    array(
        21,
        22,
        23,
        24
    ),
    array(
        31,
        32,
        33,
        34
    ),

);

$paramsTable = array(
    'border' => 'single',
    'tableAlign' => 'center',
    'borderWidth' => 10,
    'borderColor' => 'B70000',
);

$docx->addTable($valuesTable, $paramsTable);

$link = new Phpdocx\Elements\WordFragment($docx);
$options = array(
    'url' => 'http://www.google.es'
);

$link->addLink('Link to Google', $options);

$image = new Phpdocx\Elements\WordFragment($docx);
$options = array(
    'src' => '../../img/image.png'
);

$image->addImage($options);

$valuesTable = array(
    array(
        'Title A',
        'Title B',
        'Title C'
    ),
    array(
        'Line A',
        $link,
        $image
    )
);


$paramsTable = array(
    'columnWidths' => array(1000, 2500, 3000),
    'cellMargin' => array('top' => 90, 'right' => 90, 'bottom' => 120, 'left' => 190),
    );

$docx->addTable($valuesTable, $paramsTable);

$docx->createDocx('example_transformDocAdvPDF_2.docx');

$transform = new Phpdocx\Transform\TransformDocAdvPDF('example_transformDocAdvPDF_2.docx');
$transform->transform('example_transformDocAdvPDF_2.pdf');