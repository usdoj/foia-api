<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docxPathUtilities = new Phpdocx\Utilities\DOCXPathUtilities();
$docxPathUtilities->removeSection('../../files/document_sections.docx', 'removeSection.docx', 2);