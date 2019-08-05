<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/docxpath/tables.docx');

$referenceNode = array(
    'customQuery' => '//w:tbl/w:tr[2]/w:tc[1]',
);

$contents = $docx->getWordContents($referenceNode);

print_r($contents);