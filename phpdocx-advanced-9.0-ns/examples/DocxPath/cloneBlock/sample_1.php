<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/TemplateBlocks_2.docx');

$docx->cloneBlock('FIRST');

$docx->clearBlocks();

$docx->createDocx('example_cloneBlock_1');