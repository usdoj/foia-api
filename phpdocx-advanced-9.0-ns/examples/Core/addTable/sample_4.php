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
    'font' => 'Arial'
);

$docx->addText($text, $paragraphOptions);

$footnote = new Phpdocx\Elements\WordFragment($docx, 'document');
$footnote->addFootnote(
  array(
    'textDocument' => 'footnote',
    'textFootnote' => 'The footnote we want to insert.',
    'referenceMark' => array('b' => 'on'),
  )
);

$textFragment = new Phpdocx\Elements\WordFragment($docx, 'document');

$text = array();
$text[] = array('text' => 'Other text ');
$text[] = $footnote;

$paragraphOptions = array(
  'textAlign' => 'center',
  'bold' => true,
);
$textFragment->addText($text, $paragraphOptions);

$htmlFragment = new Phpdocx\Elements\WordFragment($docx, 'document');

$htmlFragmentString = new Phpdocx\Elements\WordFragment($docx, 'document');
$htmlFragmentString->embedHtml('<p style="font-family: verdana; font-size: 11px">HTML tags <b>bold</b></p>');

$textHtml = array();
$textHtml[] = $htmlFragmentString;

$htmlFragment->addText($textHtml);

$valuesTable = array(
  array(
    $textFragment,
  ),
  array(
    '2',
  ),
  array(
    $htmlFragment,
  ),
);

$docx->addTable($valuesTable);

$docx->createDocx('example_addTable_4');