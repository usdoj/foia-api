# Node to docX custom module

## Provenance

This module was is derived from the PHPDocX library Namespaces version 9.5.

The following changes have been made:

- Updated to meet Drupal coding standards.
- Revised theme  preprocessing to render Annual FOIA Report tables.
 `node--node-to-docx.html.twig` includes hard-coded docx twig templates, which
 correspond to Annual Report sections.
- Premium _Extended HTML_ tags are enabled in `generateDocxFromHtml()`.

## Updating

Given the extent of the modifications, it will generally be preferable to patch
individual security or performance updates rather than replacing the module's
codebase.

## Debugging

To enable debugging of docx, uncomment the following line in `NodetoDocxHandler.php`

```php
// file_put_contents('/var/www/foia/docroot/debug.html', $drupalMarkup);.
```
