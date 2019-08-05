<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/example_area_chart.docx');

$referenceNode = array(
    'type' => 'chart',
);

$contents = $docx->getWordStyles($referenceNode);

print_r($contents);