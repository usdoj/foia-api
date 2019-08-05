<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$comment = new Phpdocx\Elements\WordFragment($docx, 'document');

$comment->addComment(
    array(
        'textDocument' => 'comment',
        'textComment' => 'The comment we want to insert.',
        'initials' => 'PT',
        'author' => 'PHPDocX Team',
        'date' => '10 September 2000',
    )
);
                    
$text = array();
$text[] = array('text' => 'Here comes the ');
$text[] = $comment;
$text[] = array('text' => ' and some other text.');

$docx->addText($text);

$commentCompleted = new Phpdocx\Elements\WordFragment($docx, 'document');

$commentCompleted->addComment(
    array(
        'textDocument' => 'completed.',
        'textComment' => 'This comment is completed.',
        'initials' => 'PT',
        'author' => 'PHPDocX Team',
        'date' => '03 July 2018',
        'completed' => true,
    )
);

$text = array();
$text[] = array('text' => 'This comment is ');
$text[] = $commentCompleted;

$docx->addText($text);

$docx->createDocx('example_addComment_3');