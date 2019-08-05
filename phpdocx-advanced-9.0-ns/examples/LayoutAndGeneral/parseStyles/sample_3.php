<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

// parse styles of an external template
$docx->parseStyles('../../files/stylesTemplate.docx');

$docx->createDocx('example_parseStyles_3');