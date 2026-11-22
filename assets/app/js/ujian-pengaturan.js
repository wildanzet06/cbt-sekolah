    (function () {
        var KUNCI = 'mode_sederhana_pengaturan';
        var $ul = $('#tab-pengaturan');
        var $sw = $('#mode-sederhana');
        if (!$ul.length || !$sw.length) { return; }

        function terapkan(sederhana) {
            $ul.toggleClass('mode-sederhana', sederhana);
            $sw.prop('checked', sederhana);
            if (sederhana && $ul.find('li.tab-lanjutan a.active').length) {
                $ul.find('a[href="#pane-peserta"]').tab('show');
            }
            try { localStorage.setItem(KUNCI, sederhana ? '1' : '0'); } catch (e) {}
        }

        var __up = document.getElementById('ujian-pengaturan-data');
        var paksaRinci = __up ? __up.dataset.paksa === '1' : false;
        var awal = true;
        try {
            var simpan = localStorage.getItem(KUNCI);
            if (simpan !== null) { awal = simpan === '1'; }
        } catch (e) {}
        if (paksaRinci) { awal = false; }

        $sw.on('change', function () { terapkan(this.checked); });
        terapkan(awal);
    })();
