jQuery(function () {
    if (jQuery.widget && jQuery.ui && jQuery.ui.button) {
        jQuery.widget.bridge('uibutton', jQuery.ui.button);
    }

    var el = document.getElementById('running-text-data');
    if (!el) { return; }

    var runningText = [];
    try {
        runningText = JSON.parse(el.getAttribute('data-running') || '[]');
    } catch (e) {
        runningText = [];
    }

    var teks = '';
    jQuery.each(runningText, function (i, v) {
        var aman = jQuery('<div>').text(v && v.text != null ? v.text : '').html();
        teks += '<span class="ml-3 mr-3">' + aman + '</span> &bull; ';
    });

    jQuery('#running-text-siswa').html(teks);

    if (jQuery.fn.marquee) {
        jQuery('.marquee').marquee({
            duration: 15000,
            gap: 20,
            delayBeforeStart: 1,
            direction: 'left',
            duplicated: true
        });
    }
});