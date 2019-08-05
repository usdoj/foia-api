<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/TemplatePipeSymbol.docx');

$docx->setTemplateSymbol('|');

$docx->replaceVariableByText(array('FIRST' => 'Hello World!'));

$docx->createDocx('example_setTemplateSymbol_1');