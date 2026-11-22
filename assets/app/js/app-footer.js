jQuery(function () {
    if (jQuery.widget && jQuery.ui && jQuery.ui.button) {
        jQuery.widget.bridge('uibutton', jQuery.ui.button);
    }
});
