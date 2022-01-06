/**
 * @file
 * Stops page from changing when user is posting.
 */

(function($, Drupal) {
  Drupal.foiaNodeEditProtection = {};
  var click = false, edit = false;

  Drupal.behaviors.foiaNodeEditProtection = {
    attach: function(context) {
      $(context).find('form').once('form').each(function(index, element) {
        if (!$(element).length) {
          return false;
        }

        $(".node-form :input").each(function() {
          var oVal = $(this).val();
          $(this).blur(function(e) {
            var nVal = e.target.value;
            if (oVal !== nVal) {
              edit = true;
            }
          });
        });

        // Let all form submit buttons through.
        $(".node-form input[type='submit'], .node-form button[type='submit']").each(function() {
          $(this).addClass('node-edit-protection-processed');
          $(this).click(function() {
            click = true;
          });
        });

        // Let all vertical tabs through ( for full edit mode ).
        var tabs = document.querySelector('.js-vertical-tabs--main > .vertical-tabs > .vertical-tabs__menu');
        $('> .vertical-tabs__menu-item', tabs).each(function (index, menuItem) {
          $(menuItem).addClass('node-edit-protection-processed');
        });

        $("a, button, input[type='submit']:not(.node-edit-protection-processed), button[type='submit']:not(.node-edit-protection-processed)")
          .each(function() {
            $(this).click(function(e) {

              // Add CKEditor support.
              if (typeof (CKEDITOR) !== 'undefined' && typeof (CKEDITOR.instances) !== 'undefined') {
                for (var i in CKEDITOR.instances) {
                  if (CKEDITOR.instances[i].checkDirty()) {
                    edit = true;
                    break;
                  }
                }
              }

              if (edit) {
                e.preventDefault();
                var target = e.target,
                  tagName = target.tagName,
                  isLink = tagName === 'A',
                  _confirm = window.confirm(Drupal.t("You have unsaved changes on this page!"));

                if (_confirm) {
                  if (isLink) {
                    // For a links.
                    window.location = target.href;
                  } else {
                    // For buttons.
                    return true;
                  }
                }
                return false;
              }

            });
          });
      });

    }
  };
})(jQuery, Drupal);
