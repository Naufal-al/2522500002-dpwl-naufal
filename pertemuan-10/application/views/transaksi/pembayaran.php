<?php
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$rupiah=static fn($v)=>'Rp '.number_format((float)$v,0,',','.');
require APPPATH.'views/katalog/header.php';
require APPPATH.'views/katalog/navbar.php';
$status=(string)($bayar['sttus_byr']??'pending');
?>
<style>
.p9-wrap{max-width:1050px;margin:38px auto 65px;padding:0 18px;color:#203047}.p9-hero{padding:28px;border-radius:18px;background:linear-gradient(120deg,#102f63,#1769b4);color:#fff;box-shadow:0 12px 30px #153e7025}.p9-hero h1{font-size:28px;font-weight:800;margin:0 0 8px;color:#fff}.p9-hero p{margin:0;color:#dcecff}.p9-card{background:#fff;border:1px solid #e7edf5;border-radius:16px;padding:24px;margin-top:20px;box-shadow:0 8px 25px #17385d0c}.p9-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}.p9-item{padding:14px;background:#f7f9fc;border-radius:11px}.p9-item small{display:block;color:#718096;margin-bottom:5px}.p9-item strong{overflow-wrap:anywhere}.p9-amount{font-size:30px;font-weight:800;color:#1263b4}.p9-status{display:inline-block;padding:7px 13px;border-radius:30px;background:#fff3d6;color:#966000;font-weight:700}.p9-btn{border:0;border-radius:9px;padding:12px 19px;background:#1769c2;color:#fff;font-weight:700}.p9-muted{color:#6c7b91}@media(max-width:600px){.p9-grid{grid-template-columns:1fr}.p9-hero h1{font-size:23px}}
</style>
<main class="p9-wrap">
 <section class="p9-hero"><h1><i class="fa fa-credit-card"></i> Pembayaran Uang Muka (DP)</h1><p>Silakan transfer sesuai nominal yang tercatat pada transaksi, lalu unggah bukti pembayaran.</p></section>
 <?php if(!empty($flash)): ?><div class="alert <?= ($flash['type']??'')==='success'?'alert-success':'alert-danger' ?>" style="margin-top:18px"><?= $e($flash['message']??'') ?></div><?php endif; ?>
 <section class="p9-card"><h3 style="margin-top:0;font-weight:800">Ringkasan Pembayaran</h3><div class="p9-grid">
  <div class="p9-item"><small>Kode Pembayaran</small><strong><?= $e($bayar['kode_bayar']) ?></strong></div>
  <div class="p9-item"><small>Kode Transaksi</small><strong><?= $e($bayar['kode_sewa']) ?></strong></div>
  <div class="p9-item"><small>Nama Penyewa</small><strong><?= $e($bayar['nm_lngkp']) ?></strong></div>
  <div class="p9-item"><small>Metode</small><strong><?= $e(ucfirst($bayar['mtode_byr'])) ?></strong></div>
  <div class="p9-item"><small>Tanggal Mulai - Selesai</small><strong><?= $e($bayar['tgl_mulai']) ?> — <?= $e($bayar['tgl_selesai']) ?></strong></div>
  <div class="p9-item"><small>Status Pembayaran</small><span class="p9-status"><?= $e(strtoupper($status)) ?></span></div>
 </div><div style="margin-top:22px"><div class="p9-muted">Nominal DP yang harus dibayarkan</div><div class="p9-amount"><?= $rupiah($bayar['nominal']) ?></div><small class="p9-muted">Nominal diambil dari database dan tidak dapat diubah melalui formulir.</small></div></section>
 <section class="p9-card"><h3 style="margin-top:0;font-weight:800">Bukti Transfer</h3>
 <?php if(!empty($bayar['ft_byr'])): ?><p><a class="btn btn-default" target="_blank" rel="noopener" href="<?= $e(site_url('transaksi/bukti?kode_bayar='.rawurlencode($bayar['kode_bayar']))) ?>"><i class="fa fa-picture-o"></i> Lihat bukti yang diunggah</a></p><?php endif; ?>
 <?php if($status==='pending'): ?><p class="p9-muted">Format JPG, PNG, atau WEBP. Maksimal 3 MB. Status akan tetap pending sampai admin melakukan verifikasi.</p>
 <form method="post" enctype="multipart/form-data" action="<?= $e(site_url('transaksi/unggah-bukti')) ?>"><input type="hidden" name="_token" value="<?= $e($token) ?>"><input type="hidden" name="kode_sewa" value="<?= $e($bayar['kode_sewa']) ?>"><div class="form-group"><label for="ft_byr">Pilih gambar bukti transfer</label><input id="ft_byr" class="form-control" type="file" name="ft_byr" accept="image/jpeg,image/png,image/webp" required></div><button class="p9-btn" type="submit"><i class="fa fa-upload"></i> Unggah Bukti Pembayaran</button></form>
 <?php else: ?><p class="alert alert-info">Pembayaran sudah diproses. Bukti dan status tidak dapat diubah kembali.</p><?php endif; ?>
 </section>
 <p style="margin-top:18px"><a href="<?= $e(site_url('katalog')) ?>">&larr; Kembali ke katalog</a></p>
</main>
<?php require APPPATH.'views/katalog/footer.php'; ?>
