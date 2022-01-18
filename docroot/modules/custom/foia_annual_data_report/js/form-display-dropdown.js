// Define the various elements that will be used.
// Div wrapper for the ul nav.
var navDiv = document.getElementById('adr-form-section-nav');
// The ul nav.
var ul = document.querySelector('ul.form-section-nav');
// The close X link/button.
var navClose = document.getElementById('form-section-nav-close');
// Click the div to open/expose the nav.
navDiv.onclick = function () {
  ul.classList.add('clicked');
  document.getElementById('form-section-nav-close').style.display = 'block';
};
// Click the X to hide the nav.
navClose.onclick = function () {
  ul.classList.remove('clicked');
  document.getElementById('form-section-nav-close').style.display = 'none';
};
