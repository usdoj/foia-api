<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/TemplateHeaderAndFooter.docx');

$docx->removeFooters();

$docx->createDocx('example_removeFooters_1');