<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/DOCXPathBlocks_2.docx');

$variables = $docx->getTemplateVariables();
$docx->processTemplate($variables);

$docx->cloneBlock('EXAMPLE');

$docx->cloneBlock('EXAMPLE');

$docx->cloneBlock('SUB_1');

$docx->cloneBlock('SUB_1', 2);

$docx->cloneBlock('SUB_1', 4);

$docx->cloneBlock('SUB_2', 2);

$docx->cloneBlock('SUB_2');

$docx->clearBlocks();

$docx->createDocx('example_cloneBlock_3');