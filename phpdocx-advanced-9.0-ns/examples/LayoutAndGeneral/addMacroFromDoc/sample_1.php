<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx('docm');

$docx->addMacroFromDoc('../../files/fileMacros.docm');

$docx->createDocx('example_addMacroFromDoc_1');
rename('example_addMacroFromDoc_1.docx', 'example_addMacroFromDoc_1.docm');