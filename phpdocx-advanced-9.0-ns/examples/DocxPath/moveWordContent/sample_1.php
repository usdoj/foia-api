<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$text = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, ' .
    'sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut ' .
    'enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut' .
    'aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit ' .
    'in voluptate velit esse cillum dolore eu fugiat nulla pariatur. ' .
    'Excepteur sint occaecat cupidatat non proident, sunt in culpa qui ' .
    'officia deserunt mollit anim id est laborum.';

$paragraphOptions = array(
    'bold' => true,
    'font' => 'Arial',
);

$docx->addText($text, $paragraphOptions);

$text = 'Sed ut perspiciatis unde omnis iste natus error sit voluptatem ' . 
    'accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ' . 
    'ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt ' . 
    'explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut ' . 
    'odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem ' . 
    'sequi nesciunt.';

$docx->addText($text, $paragraphOptions);

$text = 'Donec blandit ex nec lectus iaculis imperdiet. Donec a dictum odio. ' . 
    'Morbi viverra, urna eu consectetur tincidunt, ex urna commodo turpis, ut euismod ' . 
    'diam augue ac tellus. Mauris eget posuere orci. Etiam vehicula tincidunt ligula ac ' . 
    'molestie. Nullam sit amet lectus nec nisl facilisis aliquet. ' . 
    'Maecenas convallis vel ipsum rhoncus dictum. ';

$docx->addText($text, $paragraphOptions);

$referenceNodeFrom = array(
    'type' => 'paragraph',
    'contains' => 'Donec',
);

$referenceNodeTo = array(
    'type' => 'paragraph',
    'contains' => 'Lorem',
);
$docx->moveWordContent($referenceNodeFrom, $referenceNodeTo, 'after');

$docx->createDocx('example_moveWordContent_1');