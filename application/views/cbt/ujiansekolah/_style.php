<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<style>
    /* ---- Daftar Ujian ------------------------------------------------ */
    .ujian-item { transition: background-color .12s ease-in-out; }
    .ujian-item:hover { background: #f7fbff; }
    .ujian-item:last-child { border-bottom: 0 !important; }

    .ujian-chips .chip {
        display: inline-block;
        font-size: .78rem;
        padding: .15rem .5rem;
        border-radius: 999px;
        border: 1px solid transparent;
        margin: 0 .3rem .25rem 0;
        white-space: nowrap;
    }
    .ujian-chips .chip-ok { background: #e8f7ee; color: #1e7e34; border-color: #c3e6cb; }
    .ujian-chips .chip-no { background: #fdecea; color: #b02a37; border-color: #f5c6cb; }
    .ujian-chips .chip-ok i, .ujian-chips .chip-no i { font-size: .7rem; margin-right: .15rem; }

    #filter-ujian .nav-link { color: #495057; }
    #filter-ujian .nav-link.active { font-weight: 600; }

    /* ---- Wizard ------------------------------------------------------ */
    .wizard-steps { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 0; }
    .wizard-steps .step {
        display: flex; align-items: center; gap: .5rem;
        padding: .5rem .85rem; border-radius: .5rem;
        background: #f1f3f5; color: #6c757d; font-size: .9rem; cursor: default;
        border: 1px solid #e9ecef;
    }
    .wizard-steps .step .num {
        display: inline-flex; align-items: center; justify-content: center;
        width: 24px; height: 24px; border-radius: 50%;
        background: #adb5bd; color: #fff; font-size: .8rem; font-weight: 600;
    }
    .wizard-steps .step.aktif { background: #e7f1ff; color: #0b5ed7; border-color: #b6d4fe; }
    .wizard-steps .step.aktif .num { background: #0d6efd; }
    .wizard-steps .step.selesai .num { background: #198754; }

    .wizard-pane { display: none; }
    .wizard-pane.aktif { display: block; }

    .kartu-bank {
        border: 2px solid #e9ecef; border-radius: .5rem; padding: .75rem 1rem;
        cursor: pointer; transition: all .12s ease-in-out; margin-bottom: .6rem;
    }
    .kartu-bank:hover { border-color: #b6d4fe; background: #f7fbff; }
    .kartu-bank.dipilih { border-color: #0d6efd; background: #eef5ff; }
    .kartu-bank .judul { font-weight: 600; }
    .kartu-bank .meta { font-size: .82rem; color: #6c757d; }

    .ringkas-ujian {
        background: #f8f9fa; border: 1px dashed #ced4da; border-radius: .5rem;
        padding: .75rem 1rem; font-size: .9rem;
    }
    .ringkas-ujian .baris { margin-bottom: .15rem; }

    .opsi-lock { opacity: .65; }

    /* ---- Pembagian peserta ------------------------------------------ */
    .ruang-baris {
        display: flex; align-items: center; justify-content: space-between;
        padding: .5rem .25rem; border-bottom: 1px dashed #e9ecef;
    }
    .ruang-baris:last-child { border-bottom: 0; }
    .ruang-baris .kode {
        display: inline-block; min-width: 64px; font-weight: 600; color: #495057;
    }
    .kapasitas-bar { height: 6px; border-radius: 3px; background: #e9ecef; overflow: hidden; }
    .kapasitas-bar span { display: block; height: 100%; background: #0d6efd; }

    /* ---- Pantau ------------------------------------------------------ */
    .status-dot {
        display: inline-block; width: 9px; height: 9px; border-radius: 50%;
        margin-right: .35rem; background: #adb5bd;
    }
    .status-dot.sedang { background: #ffc107; }
    .status-dot.selesai { background: #198754; }
    .status-dot.belum { background: #ced4da; }
</style>
