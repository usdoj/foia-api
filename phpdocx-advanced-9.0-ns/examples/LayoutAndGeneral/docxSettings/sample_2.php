<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$text = 'Enable proofState option to set spelling and grammatical checking state';
$docx->addText($text);

$settings = array(
    'customSetting' => array(
        'tag' => 'proofState',
        'values' => array('w:grammar' => 'dirty', 'w:spelling' => 'dirty'),
    )
);
$docx->docxSettings($settings);

$docx->createDocx('example_docxSettings_2');