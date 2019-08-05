<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$docx->addText('In this first example we just add an image with a dashed border:');
$options = array(
    'src' => '../../img/image.png',
    'imageAlign' => 'center',
    'scaling' => 50,
    'spacingTop' => 10,
    'spacingBottom' => 0,
    'spacingLeft' => 0,
    'spacingRight' => 20,
    'textWrap' => 0,
    'borderStyle' => 'lgDash',
    'borderWidth' => 6,
    'borderColor' => 'FF0000',
    'caption' => array('show_label' => true, 'text' => ' Sample Image')
);

$docx->addImage($options);

$docx->addText('This is a closing paragraph.');

$docx->createDocx('example_addImage_1');