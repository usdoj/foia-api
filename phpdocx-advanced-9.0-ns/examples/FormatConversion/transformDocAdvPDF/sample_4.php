<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$docx->setBackgroundColor('FFFFCC');

$text = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, ' .
    'sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut ' .
    'enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut' .
    'aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit ' .
    'in voluptate velit esse cillum dolore eu fugiat nulla pariatur. ' .
    'Excepteur sint occaecat cupidatat non proident, sunt in culpa qui ' .
    'officia deserunt mollit anim id est laborum.';

$docx->addText($text, array('textAlign' => 'left'));

$docx->addText($text, array('textAlign' => 'both'));

$docx->addText($text, array('textAlign' => 'center'));

$docx->addText($text, array('textAlign' => 'distribute'));

$docx->addText($text, array('textAlign' => 'left'));

$docx->addText($text, array('textAlign' => 'right'));

$docx->createDocx('example_transformDocAdvPDF_4.docx');

$transform = new Phpdocx\Transform\TransformDocAdvPDF('example_transformDocAdvPDF_4.docx');
$transform->transform('example_transformDocAdvPDF_4.pdf');