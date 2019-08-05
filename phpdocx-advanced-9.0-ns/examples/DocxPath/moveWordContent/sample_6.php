<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/DOCXPathTemplate.docx');

$referenceNodeFrom = array(
    'type' => 'table',
    'occurrence' => 1,
);

$referenceNodeTo = array(
    'type' => 'chart',
    'occurrence' => 1,
);

$docx->moveWordContent($referenceNodeFrom, $referenceNodeTo, 'before');

$docx->createDocx('example_moveWordContent_6');