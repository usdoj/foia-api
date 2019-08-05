<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$docx->addBookmark(array('type' => 'start', 'name' => 'bookmark_name'));
$docx->addText('Text that has been bookmarked.');
$docx->addBookmark(array('type' => 'end', 'name' => 'bookmark_name'));

$docx->addBreak(array('type' => 'page'));

$docx->addCrossReference('Page-1', array('type' => 'bookmark', 'referenceName'=> 'bookmark_name'));

$docx->createDocx('example_addCrossReference_1');