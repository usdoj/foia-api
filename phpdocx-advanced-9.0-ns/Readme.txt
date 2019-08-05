=== PHPdocX 9 ===
https://www.phpdocx.com/

PHPDOCX is a PHP library designed to dynamically generate reports in Word format (WordprocessingML).

The reports may be built from any available data source like a MySQL database or a spreadsheet. The resulting documents remain fully editable in Microsoft Word (or any other compatible software like OpenOffice) and therefore the final users are able to modify them as necessary.

The formatting capabilities of the library allow the programmers to generate dynamically and programmatically all the standard rich formatting of a typical word processor.

This library also provides an easy method to generate documents in other standard formats such as PDF or HTML.

=== What's new on PHPdocX 9? ===

- HTML extended: call phpdocx methods from custom HTML tags to add headers, footers, comments, page number, TOC, WordFragments and many other contents (Premium licenses only)
- Tracking support: add people, track new, replaced and removed contents and styles, accept and reject existing trackings, get tracking information (Premium licenses only)
- Native PHP conversion from DOCX to PDF (Advanced and Premium licenses only)
- Huge performance improvement in HTML to DOCX transformations: 60% less memory used and 15% faster (average)
- New method to extract and analyze styles in the main document body: getWordStyles (Advanced and Premium licenses only)
- New options in the conversion plugin based on LibreOffice: export comments (inline and margins), export form fields (structured document tags: input and select) and lossless compression
- Added support for generating the Table of contents automatically with the conversion plugin with msword (Advanced and Premium licenses only)
- HTML to DOCX: added support for custom paragraph styles in LI tags and the start attribute in ol tags, removed two notices when using PHP 7.2 or newer and solved the Font_Metrics error when using not valid fonts
- New method to extract existing files from the DOCX: getWordFiles (Advanced and Premium licenses only)
- Improved performance when generating charts: external files to create them has been moved to an internal PHP structure file
- New styles added to createListStyle: align and position
- Indexer now extracts people, sections information and styles of documents (Advanced and Premium licenses only)
- Support for in-memory DOCX using Indexer (Premium licenses only)
- Added pageNumberType as option to addSection and modifyPageLayout methods to set page number format and start values
- New signature of the method transformDocument
- New option to avoid using a wrap value with Tidy to prevent extra blank spaces when transforming HTML to DOCX: disableWrapValue
- Added to watermarkDocx a new option to force the conversion plugin based on LibreOffice showing text watermarks: add_vshapetype_tag (Advanced and Premium licenses only)
- Improvements in DOCX to HTML: support for sdt tags when wrapping cell tables, added default values for margin-top and margin-bottom in the base CSS of the default plugin, supported nil as value in internal cells of tables, added support of the start attribute in lists
- Now AES-256-CBC is used in the encryptDOCX method to get the cipher iv length
- Updated the function to get the dpi value from PNG images
- The enableCompatibilityMode method has been removed
- Added an extra check to detect the image file extension in HTML to DOCX methods when the image doesn't have an extension
- The static wordML variable in HTML2WordML has been replaced by a protected variable
- The transformDocxUsingMSWord method has been removed from the Basic license and added as new conversion plugin with new options (Advanced and Premium licenses only)
- Updated the included version of TCPDF to the latest release

8.5 VERSION

- MS Word 2019 support
- XLSX and PPTX utilities: sign, encrypt and change contents (Premium licenses only)
- PHP 7.3 support
- New method to optimize DOCX files that reduces their sizes while keeping contents and styles: optimizeDocx (Advanced and Premium licenses only)
- New option for HTML to DOCX methods to generate MS Word list styles automatically from HTML and CSS. Supported list styles: decimal, lower-alpha, lower-latin, lower-roman, upper-alpha, upper-latin, upper-roman
- DOCX to HTML improvements: support for paragraph and table default styles set by a w:style tag with a w:default="1" property, w:cs attributes when w:ascii doesn't exist in font tags, new default values for top and bottom margins when they are not set in the DOCX, added a default HTML content for empty contents such as paragraphs, better support for insideH, insideV, band1Horz, band2Horz, firstCol, lastCol, firstRow, lastRow tags and styles in tables, increment heading indexes automatically based on style and occurrence values (Advanced and Premium licenses only)
- PDF output stream mode support for mergePdf, removePagesPdf and watermarkPdf methods (Premium licenses only)
- PDFUtilities, new method to remove pages in PDFs: removePagesPdf (Advanced and Premium licenses only)
- Added support for base64 images in embedHTML and replaceVariableByHTML methods
- Set downloadImages as true by default in embedHTML and replaceVariableByHTML methods
- New method to create custom table styles: createTableStyle
- Added support for completed comments. Only compatible with MS Word 2013 and newer
- New method to set comments as completed or not completed: changeStatusComments. Only compatible with MS Word 2013 and newer (Advanced and Premium licenses only)
- Replaced the create_function function if PHP 5.3 or newer when using HTML import methods. Replaced by an anonymous function
- Indexer now shows the fonts used in the document (Advanced and Premium licenses only)
- Added revision value to the addProperties method
- Removed a warning message when merging PDFs with Index elements using PHP 7.2 or newer (Advanced and Premium licenses only)
- Updated check.php to test phpdocx requirements

8.2 VERSION

- New class to get DOCX documents from any stream to be used as templates or for merging (Premium licenses only)
- PHP 7.2 compatibility: OpenSSL support and removed PHP deprecated messages
- Added a new option in cloneBlock to allow cloning blocks and subblocks by position (Advanced and Premium licenses only)
- New options for signPDF to add supplementary data, customize the signature and add an image as signature (Premium licenses only)
- Removed mcrypt methods to encrypt documents. OpenSSL is now used in the Crypto module (Premium licenses only)
- New method to customize the string used to wrap blocks: setTemplateBlockSymbol
- Block placehoders in templates don't need to be unique in templates
- New improvements added to DOCX to HTML transformation: support for comments, new checks when default styles don't exist, added links for endnote, footnote and comment references (Advanced and Premium licenses only)
- DOCXPath: much better performance when using moveWordContent and cloneWordContent (Advanced and Premium licenses only)
- Improvements for the conversion plugin when transforming HTML using colspans and rowspans in the first row (Advanced and Premium licenses only)

8.0 VERSION

- Blockchain module (Premium licenses only)
- Transform DOCX to HTML using native PHP classes (Advanced and Premium licenses only)
- New tags, styles and selectors supported when transforming HTML to DOCX: CSS3 selectors (nth-child, nth-of-type, first-of-type, last-of-type), tags (figcaption), styles (color and width for hr, margin-left and margin-right for td, auto and fixed for table-layout)
- Improved compatibility with MS Office 365 (when generating a not document.xml fixed name)
- New method to change the default styles dynamically: setDocumentDefaultStyles 
- New method to transform OMML (Office MathML) equations to MathML: transformOMMLToMathML
- New internal in-memory base template to improve performance when generating DOCX documents from scratch (Premium licenses only)
- DOMPDF and TCPDF libs have been moved to internal phpdocx classes
- New method to repair automatically common issues with tables, lists and extra pages when working with the conversion plugin and LibreOffice: enableRepairMode (Advanced and Premium licenses only)
- New styles for cross-references supported
- TOCs can be added as WordFragments
- Generate automatically the TOC in a DOCX with the conversion plugin (Advanced and Premium licenses only)
- Added lastModifiedBy as option to addProperties
- replaceChartData adds editable XLSX (Advanced and Premium licenses only)
- New methods to remove headers and footers: removeHeaders and removeFooters
- Apply a format string for axis (date, percentage, currency, custom...) when adding charts
- New option to remove extra line breaks when transforming HTML to DOCX with the embedHTML method (useful for working with LibreOffice and the conversion plugin when a string doesn't have a tag wrapper)
- New option to avoid adding default styles when importing HTML
- LibreOffice 6 support when using the conversion plugin (Advanced and Premium licenses only)
- Changed INC extensions to PHP extensions for all classes
- Moved to ZIP packages
- Removed OpenOffice from the packages
- Removed log4php from the packages, any PSR3 logging library can be used

7.5 VERSION

- Charts improvements: new data structure to allow repeating name values, trend lines, combo charts, all charts can be edited when the DOCX is opened, majorUnit, minorUnit, scalingMax and scalingMin options for bar, col, line, area, radar and scatter charts, smooth option for line and scatter charts, styles for titles, show legend keys and series labels, format data labels in col and bar charts (rotation and position)
- Support for adding images as streams with addImage, replacePlaceholderImage and HTML methods
- Add charts from existing XLSX files
- Character styles support
- Improved importing tables from HTML with colspan or rowspan values
- New method to create custom character styles: createCharacterStyle
- New styles for paragraphs, headings, links, texts and styles: double strike through, vanish, scaling, position, underline color, character spacing and character borders
- The conversion plugin allows to transform DOCX to XHTML (HTML is supported since phpdocx 4.5)
- New options for addImage: relativeToHorizontal and relativeToVertical to add images relative to page, margins, columns and other positions in the document
- Improved merging of DOCX that include list styles with images or themed charts
- Set protected and editable regions using CryptoPHPOCX
- Set math equations alignments
- SignDocx allows adding multiple signatures
- XMLAPI content can be added as strings and added a stream mode option
- The getDocxPathQueryInfo method of DOCXPath returns the elements of the query to be changed or queried
- New option to set continue numbering in lists
- New option to set a start value with createListStyle
- Set custom settings using the docxSettings method
- New exception thrown when phpdocx can't write to the target file

7.0 VERSION

- DOCXCustomizer: change styles of existing contents on the fly in documents created from scratch and templates (Premium licenses only)
- New cloneBlock method to clone blocks in documents (Advanced and Premium licenses only)
- DOCXPathUtilities: split DOCX, remove a section and its contents (Advanced and Premium licenses only)
- PDFUtilities: split PDF, watermark PDF (Advanced and Premium licenses only)
- DOCXPath: range of elements, iterate all elements not only the first one, siblings (Advanced and Premium licenses only)
- Merge DOCX documents using in-memory DOCX documents (Premium license only)
- replaceChartData improved to allow changing titles, legends and categories (Advanced and Premium licenses only)
- Added RTL support when importing HTML lists
- Added new functionality to Indexer: returns images sizes, charts data and document properties (Advanced and Premium licenses only)
- Joomla extension (Advanced and Premium licenses only)
- Improved replace WordFragment in headers and footers to achieve a better performance
- Use replaceListVariable and replaceTableVariable in headers and footers
- Support for PDF 1.5, 1.6 and 1.7 versions when adding watermarks, merging and signing documents
- Set created and modified dates
- Use superscripts, subscripts and strikethroughs in texts
- createDocxAndDownload improved: new option to remove the generated file after downloading it

6.5 VERSION

- Many improvements that enhances the performance of the library:
  · Generation of DOCX files doesn´t create temp files anymore, except when working with charts.
  · HTML to DOCX conversion requires less time and memory.
  · New option to generate DOCX files as a stream instead of physical files (Premium license only).
  · New class to optimize templates and decrease required time to replace placeholders (Premium license only).
  · It loads the base template in memory instead of saving it as a physical file (Premium license only).
  · New method to load templates in memory, which allows serializing them (Premium license only).
  · Support for HHVM (http://hhvm.com) (Premium license only).
- Indexer: a new class to extract and parse the content of the documents: body, comments, endnotes, footers, footnotes, headers, images and links (available for Advanced and Premium licenses).
- DOCXPATH: new method to extract text contents from documents using DOCXPATH queries
- replaceListVariable and replaceTableVariable options now support the use of WordFragments as values.
- New methods to clone and move contents with DocxPath (available for Advanced and Premium licenses). 
- New option to add URLs in images with addImage.
- New option to apply the WordFragments styles when placing content in lists.
- rawSearchAndReplace, a new method that replaces strings in any XML file of a DOCX.
- Added page-of option to insert numberings of the "page X of Y" kind.
- Removed the deprecated constructor messages in FPDI when using PHP 7.
- Htmlawed updated to the last available version.

6.0 VERSION

- DOCXPath: A new class for adding WordFragments, replacing contents for WordFragments and deleting existing contents in the main document. It is compatible with new documents and templates and simplifies low level document edition with a new set of easy to use methods.
- WYSIWYG editor: Many improvements to create a WYSIWYG editor for generating DOCX or PDF files. Support for new styles and tags in HTML to DOCX conversion. It includes the recommended configuration for CKEditor (http://ckeditor.com).
- Crossreferences: Support for adding cross-references in the document.
- Import list styles: Import styles from existing lists to apply them in new documents and templates.
- Parse carriage returns for parseLineBreaks option: Makes possible to use literals like '\n' and carriage returns like "\n" with the parseLineBreaks option.
- PhpdocxLogger may be disabled: A new method to completely disable the logger.
- Styles for image captions: New parameters for adding styles to image captions.
- XXE automatically instead of as an option: It integrates automatic protection against XXE attacks (https://www.owasp.org/index.php/XML_External_Entity_(XXE)_Processing).
- Drupal 8 module: A new module to use phpdocx with Drupal 8.
- Chart new options: New options to define maximum and minimum range of line and bar charts.
- replaceVariableByText by raw elements: It allows to replace any existing string text in a document for other string text.

5.5 VERSION

- A document statistics module: counts and prints the number of pages, words, characters, paragraphs, lines, tables and images. Only for Corporate and Enterprise licenses.
- LibreOffice version 5 improvements: phpdocx and LibreOffice are even more compatible. With phpdocx 5.5 you can generate better looking docx. Also, to convert PDFs is easier, as it automates the process even more.
- Phar for namespaces packages: install and use phpdocx copying a single PHAR file. Only for Corporate and Enterprise licenses.
- WordFragments support for math equations.
- Improved clean temp files: Now phpdocx deletes temporal files more efficiently.
- Emphasis mark type support.
- Adding image captions.

5.1 VERSION

- Added support for PHP 7.

5.0 VERSION

- XML Api: An API for document generation without PHP knowledge. Its easy tagging allows to access the phpdocx methods as well as working with templates. This feature doesn´t require any programming skills. Only available for Corporate and Enterprise licenses.
- Logger: Phpdocx runs an inside logger. From version 5.0 on, phpdocx allows to use a custom logger that complies with the PSR-3 Logger interface.

4.6 VERSION

This new version adds support to generate and transform the Table of contents (TOC) when using the conversion plugin, replaces placeholder in headers and footers by WordFragments and adds a new option to solve the CVE-2014-2056 vulnerability.

4.5 VERSION

This version includes a new conversion plugin that uses LibreOffice as the transformation tool. It doesn't need OdfConverter to transform the documents.

Both LibreOffice and OpenOffice suites are supported.

The Corporate and Enterprise licenses include plugins to use phpdocx with Drupal, WordPress, Symfony and any framework or development that use composer.

4.1 VERSION

This new version adds support for charts when the documents are transformed to PDF (using JpGraph http://jpgraph.net or Ezcomponents http://ezcomponents.org) and a new package that includes namespaces support.

The namespaces package is only available for Corporate and Enterprise licenses.

4.0 VERSION

This new major version represents a big step forward regarding the functionality and scope of PHPDocX.

The most important change introduced in this new version is that it removes all the restrictions regarding custom templates that were limitating previous versions.

With PHPDocX 4.0 one may use concurrently the standard template methods of the past (that have also been improved and refactored) with any of the core PHPDocX methods. This means in practice that one is not limited to modify the contents of a custom by simply replacing variables but that one may insert arbitrary Word content anywhere within the template.

Another important new feature is the introduction of "Word fragments" that allow for a simpler and more flexible creation of content. One may create a Word fragment with arbitrary content: paragraphs, lists, charts, images, footnote, etcetera and later insert it anywhere in the Word document in a truly simple and transparent way.

The main changes can be summarized as follows:

CORE AND TEMPLATES:
      * Completely refactored CreateDocx class and a completely new CreateDocxFromTemplate class that extends the former and allows for a complete control of documents based on custom templates.
      * New WordFragment class that greatly simplifies the process of nesting content within the Word document.
      * Complete refactoring of the prior template methods that allow for higher control and the replacement of variables by arbitrarily complex Word fragments.
      * createListStyle: greater choice of options for the creation of custom list styles.
      * addTextBox: includes now new formatting options and it is easier to include arbitrary content within textboxes.
      * insertWordFragmentBefore/After: allows to include content anywhere within a Word template.

DOCX UTILITIES PACKAGE
      * MultiMerge: much faster API that allows for the merging of an arbitrarily large number of Word documents with just
      one line of code.
 
CONVERSION PLUGIN
     * The conversion of numbered lists has been greatly improved as well as the handling of sections with multiple columns.

Besides these new classes and methods we have also included some minor bug fixes and multiple improvements in the API including extra options and a uniformization the typing and units conventions.

3.7 VERSION

The main goal of this version is to allow for the generation of "right to left language" (like, for example, Arabic or Hebrew) Word documents with PHPDocX.

It is now possible to set up global RTL properties that affect the whole document or to stablish them for just some particular elements like paragraphs, tables, etcetera.

The following methods/classes/files have been added or modified:

CORE:
      * phpdocxconfig.ini now admits to new global options: bidi and rtl.
      * setRTL: allows to set global RTL properties for a particular document.
      * embedHTML: now the HTML parser supports HTML and CSS standard RTL options.
      * The following methods allow for rtl options:
               * addSection
               * modifyPageLayout
               * addDateAndHour
               * addEndnote
               * addFootnote
               * addFormElement
               * addLink
               * addMergeField
               * modifyPageLayout
               * addPageNumber
               * addSimpleField
               * addStructuredDocumentTag
               * addTable
               * addTableContents
               * addSection
               * addText
        * createListStyle: allows now to use custom fonts for bullets.

DOCX UTILITIES PACKAGE
      * The merging includes some improvements for images included within shapes.

CONVERSION PLUGIN
     * There is a new debugging mode to simplify the installation process.

3.6 VERSION

CORE:
      * addMergeField: it is now posible to include standard Microsoft Word merge fields. Although PHPDocx has its own protocols for the substitution of variables several of our clients have requested this feature to allow further manipulations in Microsoft Office with the generated docx files.
      * modifyInputField: together with the tickCheckbox method allows to fulfill forms integrated in a template.

DOCX UTILITIES PACKAGE
      * It is now possible to enforce section page breaks when merging documents even when the original documents have continuous section types
 
CONVERSION PLUGIN
     * We have included extra preprocessing of the documents prior to conversion to improve table rendering.

The package now includes an improved version of check.php that outputs info useful to debug any issue or problem related to the license or the library.

3.5.1 VERSION

This minor version includes:
    * New version of check.php script that includes license info and better guidance for the installation of the conversion plugin.
    * Improvements in the PDF conversion plugin.
    * Several improvements for the embedding of HTML into Word.
    * Minor bug fixes.

3.5 VERSION

This version includes several changes that greatly improve the core functionality of PHPDocX and in particular the conversion of HTML into Word.

CORE:
      * addComment: it is now posible to include Word comments that may incorporate complex formatting as well as images and HTML content. It is also posible to fully customize the comment properties and reference marks.
      * addFotnote and addEndnote: these methods have been completely refactored to leverage their capabilities to the new addComment method, therefore it is now posible to create truly sophisticated endnotes and footnotes.
      * createListStyle: one may now create fully customized list styles that may be used directly in conjunction with the addList method or  with the embedHTML/replaceTemplateVariable BYHTML methods (seee below).
      * createParagraphStyle: simplify your coding creating reusable custom paragraph styles that incorporate multiple formatting options (practically the full standard).
      * lineNumbering: it is posible now to insert customized line numbering in your Word document.
      * addPageBorders: its name explains it all (custom border types, colors, width, ...).
      * addText: it is now posible to customize paragraph hanging properties and indent the first line of text.
      * addMathMML: refactored to include inline math equations.

HTML2DOCX
      * 'useCustomLists' option: it allows to mimic sophisticated CSS list styles with PHPDocX. One should create first a custom list style via the createListStyle method with the same name as the CSS style that one wants to reproduce and if this option is set to true the corresponding Word list style will be used.
      * General improvements in the format rendering of list elements and table cells (in particular with sophisticated row and colspan layouts).

DOCX UTILITIES PACKAGE
      * setLineNumbering: allows for the modification of the line numbering properties of an existing Word document.
 
CONVERSION PLUGIN
     * New integrated versions of ODFConverter for Linux 64-bit OS and Windows.

3.3 VERSION

This new version includes some changes that greatly improve the PHPDocX functionality. There are several brand new methods:

CORE:
      * addSimpleField: allows for the insertion of standard Word fields in the body of the document sucha as the number of pages, document title, author, creation date, etecetera.
      * addHeading: to insert standard Word headings that may be directly included in the Table of Contents.
      * docxSettings: this method allows you to modify many of the general properties of a Word document such as the zoom on openning, the printing options, to show or hide grammar and spelling, etcetera.

TEMPLATE MANAGEMENT
      * tickCheckbox: one may now tag standard Word checkboxes in a given template and later change theirs state with the help of this useful method.

DOCX UTILITIES PACKAGE
      * modifyDocxSettings: allows for the modification of the general properties of a given pre-existing Word document. One may, for example, change the zoom properties on openning of all the documents contained in a folder with a few lines of PHP code.
      * parseCheckboxes: With the help of this method one may tick or not all the checkboxes of a given pre-existing Word document.

Besides these new methods we have improved previously existing functionality:
      * embedHTML: we improved the management of CSS page break properties and HTML line breaks.
      * addDocx: we have included the OOXML "matchSource" property to improve the rendering of embedded docx document when there are conflicting styles.
      * addTemplateVariable: we have "removed the extra empty line" that was added when replacing a template variable by a DOCX, HTML, MHT or RTF file.
      * Minor bug fixes.

We have also restructured the API documentation to simplify the access to relevant information. We have also included in the v3.3 package a refined version of  the "installation script" that now checks not only for the PHP modules required by PHPDocX but also permission rights as well as the correct installation of the PDF conversion plugin (both via web or CLI).

3.2 VERSION

This version includes some important changes that greatly improve the PHPDocX functionality:

- It is now possible to create really sophisticated tables that practically covered all posibilities offered by Word:
      * arbitrary row and column spans,
      * advanced positioning: floating, content wrapping, ...,
      * custom borders at the table, row or cell level (type, color and width),
      * custom cell paddings and border spacings,
      * text may be fitted to the size of a cell,
      * etcetera 
- There are several brand new methods:
      * addStructuredDocumentTags: that allows for the insertion of combo boxes, date pickers, dropdown menus and richtext boxes,
      * addFormElement: to insert standard form elements like text fields, check boxes or selects,
      * cleanTemplateVariables: to remove unused PHPDocX template variables together with is container element (optional) from the resulting Word template.
- It is now posible to insert arbitrarily complex tabbed content into the Word document (tab positions, leader symbol and tab type).
- There is a new UTF-8 detection algorithm more reliable that the standard PHP one based on mb_detect_encoding
- There is a new external config file that will simplify future extensibility
 
Besides these improvements, PHPDocX v3.2 also offers:
- Minor improvements in the addText method: one may use the caps option to capitalize text and it is now easier to set the different paragraph margins.
- Minor bug fixes

3.1 VERSION

This new version includes quite a few new features that you may find interesting:

- It is now posible to insert arbitrary content within a paragraph with the updated addText method:
      * multiple runs of text with diverse formatting options (color, bold, size, ...)
      * inline or floating images and charts that may be carefully positioned  thanks to the new vertical and horizontal offset parameters
      * page numbers and current date
      * footnotes and endnotes
      * line breaks and column breaks
      * links and bookmarks
      * inline HTML content
      * shapes
- In general the new addText method accepts any inline WordML fragment. This will make trivial to insert new elements in paragraphs as they are integrated in PHPDocX.
- We have greatly improved the automatic generation of the table of Contents via the addTableContents method. One may now:
      * request automatic updating of the TOC on the first openning of the document (the user will be automatically prompted to update fields in the Word document)
      * limit the TOC levels that should be shown (the default value is all)
      * import the TOC formatting from an existing Word document
- The addTemplateImage has now more configuration options so it is no longer necessary to include a placeholder image with the exact size and dpi in the PHPDocX Word template. Moreover one can now use the same generic placehorder image for the whole document simplifying considerably the process.
- The logging framework has been updated to the latest stable version of log4php.
- You may now use an external script to transform DOCX into PDF using TransformDocAdv.inc class. This script fixes the problems related to runnig system commands using Apache or any other not CGI/FastCGI web server.

Besides these improvements v3.1 also offers:
- Minor improvements in the HTML to Word conversion: one may change the orientation of text within a table cell and avoid the splitting of a table row between pages.
- New configuration options for the addImage method
- Now it is simpler to link internal bookmarks with the addLink method
- When merging two Word documents one can choose to insert line breaks between them to clearly separate the contents
- One may import styles using also their id (this may simplify some tasks)
- Minor bug fixes

3.0 VERSION

This version includes substantial changes that have required that this new version were not fully backwards compatible with the latest v2.7.

Nevertheles the changes in the API are not difficult to implement in already existing scripts and the advantages are multiple.

The main changes are summarized as follows:

- The new version handles in a different way the embedding of Word elements within other elements like tables, lists and headers/footers. The 
majority of methods have now a 'rawWordML' option that in combination with the new 'createWordMLFragment' allows for the generation of chunks of 
WordMl code that can be inserted with great flexibility anywhere within the Word document. its is now, for example, trivial to include paragraphs, 
charts, tables, etcetera in a table cell.
-One may create sophisticated headers and footers with practically no restriction whatsoever by the use of the 'createWordMLFragment'  method.
-The embedHTML and replaceTemplateVariableByHTML have been improved to include practically all CSS styles and parse floats. It is also posible now 
to filter the HTML content via XPath expressions and associate different native Word styles to individual CSS classes, ids or HTML tags.
-New chart types have been included: scatter, bubbles, donoughts and the code has been refactor to allow for greater flexibility.
-The addsection method has been extended and improved.
-The addTextBox method has been greatly improved to include many more formatting options.
-The refactored addText method allows for the introduction of line breaks inside a paragraph.
-New addPageNumber method
-New addDateAndHour method

2.7 VERSION

The main differences with respect the prior stable major version PHPDocX v2.6 can be summarized as follows:

- New chart types: percent stacked bar and col charts and double pie charts (pie or bar chart for the second one)
- Improvements in the HTML parser (floating tables, new CSS properties implemented)
- Now is posible to insert watermarks (text and/or images)
- New CryptoPHPDocX class (only CORPORATE) that allos for password protected docuemnts
- Automatic leaning of temporary files
- New method: setColorBackgraound to modify the background color of a Word document
- Several other minor improvements and bug fixes

2.6 VERSION

The main improvements are:

New and more powerfull conversion plugin for PRO+ and CORPORATE packages.
New HTML parser engine for the embedding of HTML into Word: 20% faster and up to 50% less RAM consumption.
New HTML tags and properties parsed (now covering practically the whole standard):
 -HTML headings become true Word headings
 -Flaoting images are now embedded as floated images in Word
 -Anchors as parsed as links and bookmarks
 -Web forms are converted into native Word forms
 -Horizontal rulers are also parsed into Word
 -Several other minor improvements and bug fixes
New addParagraph method that allows to create complex paragraphs that may include:
 -Formatted text
 -Inline or floating images
 -Links
 -Bookmarks
 -Footnotes and endnotes
New addBookmark method
Improvements in the DocxUtilities class (only PRO+ and Corporate licenses): improved merging capabilities that cover documents with charts, images, footnotes, comments, lists, headers and footers, etcetera.

2.5.2 FREE VERSION
- Docx to TXT to convert Docx documents to pure text

2.5.2 PRO VERSION
- New format converter for Windows (MS Word must be installed)
- Now you can replace the image in headers
- New method DocxtoTXT to convert docx documents to pure text
- Better implementation of HTML to WORDML
- Bug fixes

2.5.1 PRO VERSION
One of the most demanded functionalities by PHPDocX users is the posibility to generate Word documents out of HTML retaining the format and construct documents with different HTML blocks. Now we give a little step to make this functionality more powerful.

Since the launch of the 2.5.1 version of PHPDocX we have at your disposal two new methods: embedHTML() and replaceTemplateVariableByHTML() - new on this version- that allow to convert HTML into Word with a high degree of customization.

Moreover this conversion is obtained by direct translation of the HTML code into WordProcessingML (the native Word format) so the result is fully compatible with Open Office (and all its avatars), the Microsoft compatibility pack for Word 2003 and most importantly with the conversion to PDF, DOC, ODT and RTF included in the library.

2.5 PRO VERSION
This version of PHPDocX includes several enhancements that will greatly simplify the generation of Word documents with PHP.
The main improvements can be summarized as follows:
- New embedHTML method that:
  o Directly translates HTML into WordProcessingXML.
  o Allows to use native Word Styles, i.e. we may require that the HTML tables are formatted following a standard Word table style.
  o Is compatible with OpenOffice and the Word 2003 compatibility pack.
  o May download external HTML pages (complete or selected portions) embedding their images into the Word document.

- PHPDocX v2.5.1 now uses base templates that allow:
  o To use all standard Word styles for:
    - Paragraphs.
    - Tables with special formatting for first and last rows and columns, banded rows and columns and another standard features.
    - Lists with several different numbering styles.
    - Footnotes and endnotes.
  o Include standard headings (numbered or not).
  o Include customized headers and footers as well as front pages.

- There are new methods that allow you to parse all the available styles of a Word document and import them into your base template:
  o parseStyles  generates a Word document with all the available styles as well as the required PHPDocX code to use them in your final Word document (you may download here the result of this method applied to the default PHPDocX base template).
  o importStyles allows to integrate new styles  extracted from an external Word document into your base template.

- New conversion plugin (based on OpenOffice) that improves the generation of PDFs, RTFs and legacy versions of Word documents.

- New standardized page layout properties (A4, A3, letter, legal and portrait/landscape modes) trough the new modifyPageLayout method.

- The addTemplate method has been upgraded to greatly improve its performance.

- You may directly import sophisticated headers and footers from an existing Word document with the new  importHeadersAndFooters method.

As well as many other minor fixes and improvements.
We have also upgraded our documentation section by simplifying the access to the available library examples and we have included a tutorial that will help newcomers to get grasp of the power of PHPDocX.

====What are the minimum technical requirements?====
To run PHPDocX you need to have a functional PHP setup, this should include:

- PHP 5
- Required : Support ZipArchive
- A webserver (such as Apache, Nginx or Lighttpd) or PHP-CLI