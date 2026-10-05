<?php
// Alamat snap.js dan client key mengikuti pengaturan di application/config/midtrans.php
$snap_produksi = (bool) $this->config->item('midtrans_is_production');
$snap_url      = $snap_produksi
    ? 'https://app.midtrans.com/snap/snap.js'
    : 'https://app.sandbox.midtrans.com/snap/snap.js';
$client_key    = (string) $this->config->item('midtrans_client_key');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pembayaran</title>
    <script src="<?= $snap_url; ?>" data-client-key="<?= html_escape($client_key); ?>"></script>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .loader { border: 5px solid #f3f3f3; border-top: 5px solid #3498db; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin: 0 auto 20px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="loader"></div>
    <h3>Memuat halaman pembayaran...</h3>
    <p>Jika tidak muncul dalam 5 detik, <a href="<?= site_url('pembayaran/bayar_spp'); ?>">klik di sini</a>.</p>
    <script type="text/javascript">
        var kembali = <?= json_encode(site_url('pembayaran/bayar_spp')); ?>;
        snap.pay(<?= json_encode($snapToken); ?>, {
            onSuccess: function (result) { window.location.href = kembali; },
            onPending: function (result) { window.location.href = kembali; },
            onError:   function (result) { alert('Pembayaran gagal!'); window.location.href = kembali; },
            onClose:   function () { window.location.href = kembali; }
        });
    </script>
</body>
</html>
