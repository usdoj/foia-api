<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/TemplateHeaderAndFooter.docx');

$docx->removeHeaders();

$docx->createDocx('example_removeHeaders_1');