    $.fn.dataTableExt.oApi.fnPagingInfo = function (oSettings) {
        return {
            "iStart": oSettings._iDisplayStart,
            "iEnd": oSettings.fnDisplayEnd(),
            "iLength": oSettings._iDisplayLength,
            "iTotal": oSettings.fnRecordsTotal(),
            "iFilteredTotal": oSettings.fnRecordsDisplay(),
            "iPage": Math.ceil(oSettings._iDisplayStart / oSettings._iDisplayLength),
            "iTotalPages": Math.ceil(oSettings.fnRecordsDisplay() / oSettings._iDisplayLength)
        };
    };

    function ajaxcsrf() {
        var meta = function (name) { var el = document.querySelector('meta[name="' + name + '"]'); return el ? el.getAttribute('content') : ''; };
        var csrfname = meta('cbt-csrf-name') || 'csrf_token';
        var csrfhash = meta('cbt-csrf-hash');
        var csrf = {};
        csrf[csrfname] = csrfhash;
        $.ajaxSetup({
            "data": csrf
        });
    }

    function reload_ajax() {
        table.ajax.reload();
    }

    var initDestroyTimeOutPace = function () {
        var counter = 0;

        var refreshIntervalId = setInterval(function () {
            var progress;

            if (typeof $('.pace-progress').attr('data-progress-text') !== 'undefined') {
                progress = Number($('.pace-progress').attr('data-progress-text').replace("%", ''));
            }

            if (progress === 99) {
                counter++;
            }

            if (counter > 50) {
                clearInterval(refreshIntervalId);
                Pace.stop();
            }
        }, 100);
    };
    initDestroyTimeOutPace();

