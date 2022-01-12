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

        // Don't run on full edit mode page, i.e. if there is anything after
        // /edit on the path.
	if (window.location.pathname.substr(-5) == '/edit' ||
          window.location.pathname.substr(-5) == 'edit/') {
          return false;
	}

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
/*          $(this).oninput(function(e) {
            var nVal = e.target.value;
            if (oVal !== nVal) {
              console.log("Setting edit-i.");
              edit = true;
            }
          });
          $(this).keyup(function(e) {
            var nVal = e.target.value;
            if (oVal !== nVal) {
              console.log("Setting edit-k.");
              edit = true;
            }
          }); */
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
/*          $(this).change(function(e) {
            var nVal = e.target.value;
            if (oVal !== nVal) {
              console.log("Setting edit2-c.");
              edit = true;
            }
          });
          $(this).oninput(function(e) {
            var nVal = e.target.value;
            if (oVal !== nVal) {
              console.log("Setting edit2-i.");
              edit = true;
            }
          });
          $(this).keyup(function(e) {
            var nVal = e.target.value;
            if (oVal !== nVal) {
              console.log("Setting edit2-k.");
              edit = true;
            }
          }); */

        });
	    })});

/*        $("[id^='edit-field']").each(function() {
          var oVal = $(this).val();
          $(this).blur(function(e) {
            var nVal = e.target.value;
            if (oVal !== nVal) {
              console.log("Setting edit on edit-field.");
              edit = true;
            }
          });
        }); */


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
})(jQuery, Drupal);
