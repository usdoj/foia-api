<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/docxpath/tables.docx');

// remove the second row of the table
$referenceNode = array(
    'customQuery' => '//w:tbl/w:tr[2]',
);

$docx->removeWordContent($referenceNode);

$docx->createDocx('example_removeWordContent_9');