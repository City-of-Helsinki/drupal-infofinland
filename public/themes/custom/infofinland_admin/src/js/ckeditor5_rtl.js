// By default ckeditor dir is set based on UI language.
// Check the content language on page load and update dir accordingly.
document.addEventListener("DOMContentLoaded", (event) => {
  const langcode = drupalSettings.path.currentLanguage;
  if (langcode !== 'ar' && langcode !== 'fa') {return;}

  document.querySelectorAll('div.ck-content.ck-editor__editable').forEach((element)=>{
    element.dir = 'rtl'
  });
});

