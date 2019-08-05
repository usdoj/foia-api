<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/DOCXPathTemplate.docx');

$content = new Phpdocx\Elements\WordFragment($docx, 'document');
$content->addText('New text');
$content->addImage(array('src' => '../../img/image.png' , 'scaling' => 50));
$valuesTable = array(
    array(
        'AAA',
        'BBB',
    ),
    array(
        'Text',
        'Text: More text',
    ),

);
$paramsTable = array(
    'border' => 'single',
    'tableAlign' => 'center',
    'borderWidth' => 10,
    'borderColor' => 'B70000',
    'textProperties' => array('bold' => true),
);
$content->addTable($valuesTable, $paramsTable);
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
            'values' => array(25, 0, 14)
        ),
    ),
);
$paramsChart = array(
    'data' => $data,
    'type' => 'pie3DChart',
    'rotX' => 20,
    'rotY' => 20,
    'perspective' => 30,
    'color' => 2,
    'sizeX' => 10,
    'sizeY' => 5,
    'chartAlign' => 'center',
    'showPercent' => 1,
);
$content->addChart($paramsChart);
$linkOptions = array('url'=> 'http://www.google.es', 'color' => 'B70000', 'underline' => 'none');
$content->addLink('Link to Google in red color and not underlined', $linkOptions);
$itemList = array(
    'Line 1',
    'Line 2',
    'Line 3',
    'Line 4',
    'Line 5'
);
$content->addList($itemList, 1);

$html = '<h1 style="color: #b70000">An embedHTML() example.</h1>';
$content->embedHTML($html);

$referenceNode = array(
    'type' => 'paragraph',
    'occurrence' => 1,
    'contains' => 'Another heading',
);

$docx->insertWordFragment($content, $referenceNode, 'after');

$docx->createDocx('example_insertWordFragment_9');