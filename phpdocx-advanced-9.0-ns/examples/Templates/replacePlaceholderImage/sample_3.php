<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/placeholderImage.docx');

$image_1 = array(
    'height' => 'auto',
    'streamMode' => true,
    'width' => 'auto',
    'target' => 'header',
);

$docx->replacePlaceholderImage('HEADERIMG', 'https://www.phpdocx.com/img/logo_badge.png', $image_1);

$docx->createDocx('example_replacePlaceholderImages_3');