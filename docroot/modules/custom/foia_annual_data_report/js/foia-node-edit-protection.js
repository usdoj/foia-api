/**
 * @file
 * Stops page from changing when user is posting.
 */

(function($, Drupal, once) {
  Drupal.foiaNodeEditProtection = {};
  var click = false, edit = false;

  Drupal.behaviors.foiaNodeEditProtection = {
    attach: function(context) {
      var $context = $(context);
      var $form = $context.find('form.autosave-form');
      if ($form.length === 0) {
        var $form = $context.parents('form.autosave-form');
      }

      $(context).find('form').once('form').each(function(index, element) {
        if (!$(element).length) {
          return false;
        }

        // Don't run on full edit mode page, i.e. if there is anything after
        // /edit on the path.
	if (window.location.pathname.substr(-5) == '/edit' ||
          window.location.pathname.substr(-5) == 'edit/') {
          return false;
	}

        // Copied and modified from the autosave_form contrib module; may be
        // redundant with some of the stuff after.
        // Detect new elements added through field widgets.
        if ($context.find('.ajax-new-content').length > 0) {
          console.log("Setting edit based on ajax.");
          edit = true;
        }
        // Add a change handler that will help us determine if any inputs
        // inside the entity forms have changed values.
        $form.find(':input, [contenteditable="true"]')
          .on('change textInput input', function (event) {
            var $form = $(event.target).parents('.autosave-form').first();
            if ($form.length) {
              var val = $(this).val();
              if(val != $(this).attr('autosave-old-val') ){
                $(this).attr('autosave-old-val',val);
                console.log("Setting edit based on form1.");
                edit = true;
              }
            }
          })
          // Detect Ajax changes e.g. removing an element.
          .on('mousedown', function (event) {
            if (event.target.type === 'submit') {
              console.log("setting edit based on from2.");
              edit = true;
            }
          });
	// End of autosave_form copy.


        $(".node-form :input").each(function() {
          var oVal = $(this).val();
          $(this).blur(function(e) {
            var nVal = e.target.value;
            if (oVal !== nVal) {
              console.log("Setting edit.");
              edit = true;
            }
          });
          $(this).on('change input keyup formUpdated', function(e) {
            var nVal = e.target.value;
            if (oVal !== nVal) {
              console.log("Setting edit-c.");
              edit = true;
            }
          });
        });

        $(".vertical-tabs__pane").each(function() {
          $(this).click(function(e) {

            $(".node-form :input").each(function() {
              var oVal = $(this).val();
              $(this).blur(function(e) {
                var nVal = e.target.value;
                if (oVal !== nVal) {
                  console.log("Setting edit2.");
                  edit = true;
                }
              });
              $(this).on('change input keyup formUpdated', function(e) {
                var nVal = e.target.value;
                if (oVal !== nVal) {
                  console.log("Setting edit-c.");
                  edit = true;
                }
              });

            });
          })
	});

        // Let all form submit buttons through.
        $(".node-form input[type='submit'], .node-form button[type='submit']").each(function() {
          $(this).addClass('node-edit-protection-processed');
          $(this).click(function() {
            click = true;
          });
        });

        // Let all vertical tabs through ( for full edit mode ). Not sure this
        // completely works, note code above to bail out on /edit urls.
        var tabs = document.querySelector('.js-vertical-tabs--main > .vertical-tabs > .vertical-tabs__menu');
        $('> .vertical-tabs__menu-item', tabs).each(function (index, menuItem) {
          $(menuItem).addClass('node-edit-protection-processed');
        });

        $(".vertical-tabs__menu-item > a").each(function() {
          $(this).addClass('node-edit-protection-processed');
        });



        $("a:not(.node-edit-protection-processed), button, input[type='submit']:not(.node-edit-protection-processed), button[type='submit']:not(.node-edit-protection-processed)")
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
                  _confirm = window.confirm(Drupal.t("You have unsaved changes on this page!  To return to this page so that you can save your changes, click Cancel.  To navigate away from this page without saving your changes, click OK."));

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
})(jQuery, Drupal, once);
