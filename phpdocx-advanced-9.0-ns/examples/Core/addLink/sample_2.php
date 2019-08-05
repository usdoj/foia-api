<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$link = new Phpdocx\Elements\WordFragment($docx);
$link->addLink('Google', array('url'=> 'http://www.google.es'));

$runs = array();
$runs[] = array('text' => 'Now we include a link to ');
$runs[] = $link;
$runs[] = array('text' => ' in the middle of a paragraph of plain text.');

$docx->addText($runs);

$docx->createDocx('example_addLink_2');