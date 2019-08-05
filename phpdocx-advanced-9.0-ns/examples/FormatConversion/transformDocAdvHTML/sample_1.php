<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$text = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.';
$docx->addText($text);

$paragraphOptions = array(
    'backgroundColor' => 'ff0000',
    'bold' => true,
    'border' => 'inset',
    'borderColor' => 'b70000',
    'borderWidth' => 12,
    'borderSpacing' => 80,
    'borderTopColor' => '000000',
    'caps' => true,
    'firstLineIndent' => 20,
    'fontSize' => 9,
    'hanging' => 10,
    'headingLevel' => 2,
    'indentLeft' => 80,
    'indentRight' => 70,
    'italic' => true,
    'lineSpacing' => 360,
    'spacingBottom' => 60,
    'spacingTop' => 50,
    'textAlign' => 'center',
    'underline' => 'dash',
    'wordWrap' => true,
);

$docx->addText($text, $paragraphOptions);

$text = array();
$text[] =
    array(
        'text' => 'We know this looks ugly',
        'underline' => 'single',
    );
$text[] =
    array(
        'text' => ' supercript ',
        'superscript' => true,
        'bold' => true,
    );
$text[] =
    array(
        'text' => ' subscript ',
        'subscript' => true,
    );
$text[] =
    array(
        'text' => ' but we only want to illustrate some of the functionality of the addText method.',
        'bold' => false,
        'italic' => true,
        'lineBreak' => 'before',
        'strikeThrough' => true,
        'color' => '0000ff',
    );

$docx->addText($text);

$docx->createDocx('example_transformDocAdvHTML_1.docx');

$transformHTMLPlugin = new Phpdocx\Transform\TransformDocAdvHTMLDefaultPlugin();

$transform = new Phpdocx\Transform\TransformDocAdvHTML('example_transformDocAdvHTML_1.docx');
$html = $transform->transform($transformHTMLPlugin);

echo $html;