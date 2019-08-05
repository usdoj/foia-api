<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/Heading_First.docx');

$link = new Phpdocx\Elements\WordFragment($docx);
$link->addCrossReference('Page-3', array('type' => 'bookmark', 'referenceName'=> 'sample'));
$docx->replaceVariableByWordFragment(array('link' => $link));

$docx->addCrossReference('Page-1', array('type' => 'heading', 'referenceName'=> 'Heading First'));

$docx->createDocx('example_replaceVariableByWordFragment_4');