<?php
require APPPATH . 'views/templates/header.php';
require APPPATH . 'views/templates/sidebar.php';
$daftarPelunasan = is_array($daftarPelunasan ?? null) ? $daftarPelunasan : [];
$flash = $flash ?? null;
$e = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$rupiah = static fn($v): string => 'Rp ' . number_format((int)$v, 0, ',', '.');
?>
<style>
.p10-wrap{color:#263445}.p10-banner{background:linear-gradient(120deg,#164f91,#287fd0);color:#fff;padding:22px 26px;border-radius:10px;margin-bottom:18px;box-shadow:0 6px 18px #164f911c}.p10-banner h2{margin:0 0 6px;color:#fff;font-size:22px;font-weight:700}.p10-banner p{margin:0;color:#eaf3ff}.p10-panel{border:1px solid #dce6f1;border-radius:9px;overflow:hidden;box-shadow:0 4px 16px #203b2a0a}.p10-panel>.box-header{padding:16px 20px;background:#fff;border-bottom:1px solid #dce6f1}.p10-panel>.box-body{padding:18px 20px}.p10-table{font-size:13px}.p10-table th{background:#edf5ff;color:#31577e;white-space:nowrap}.p10-table td,.p10-table th{vertical-align:middle!important}.p10-code{font-weight:700;color:#245b9b;white-space:nowrap}.p10-amount{font-weight:700;color:#1769c2;white-space:nowrap}.p10-pill{display:inline-block;background:#eaf3ff;color:#1b5fa8;padding:5px 9px;border-radius:20px;font-size:11px;font-weight:700}.p10-empty{text-align:center;padding:28px!important;color:#7a8793}.p10-btn{background:#1769c2;border-color:#1769c2;color:#fff}.p10-btn:hover,.p10-btn:focus{background:#0f4f98;border-color:#0f4f98;color:#fff}@media(max-width:767px){.p10-banner{padding:18px}.p10-panel>.box-body{padding:12px}}
</style>
<div class="content-wrapper p10-wrap">
 <section class="content-header"><h1>Pelunasan Transaksi <small>Daftar transaksi dengan sisa kewajiban pembayaran</small></h1><ol class="breadcrumb"><li><a href="<?= $e(site_url('admin')) ?>"><i class="fa fa-dashboard"></i> Dashboard</a></li><li class="active">Pelunasan Transaksi</li></ol></section>
 <section class="content">
  <div class="p10-banner"><h2><i class="fa fa-money"></i> Pelunasan Rental</h2><p>Pilih transaksi yang masih memiliki sisa pembayaran untuk dicatat pelunasannya.</p></div>
  <?php if (is_array($flash) && !empty($flash['message'])): ?><div class="alert <?= ($flash['type']??'')==='success'?'alert-success':(($flash['type']??'')==='info'?'alert-info':'alert-danger') ?> alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= $e($flash['message']) ?></div><?php endif; ?>
  <div class="box p10-panel"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-list"></i> Transaksi yang Dapat Diproses</h3></div>
   <div class="box-body table-responsive"><table class="table table-bordered table-striped p10-table"><thead><tr><th>No.</th><th>Kode Sewa</th><th>Penyewa</th><th>No. HP</th><th>Periode Sewa</th><th>Status</th><th>Total Transaksi</th><th>Total Terbayar</th><th>Sisa Kewajiban</th><th>Aksi</th></tr></thead><tbody>
   <?php if (!$daftarPelunasan): ?><tr><td colspan="10" class="p10-empty">Tidak ada transaksi yang memiliki sisa kewajiban untuk dilunasi.</td></tr>
   <?php else: foreach ($daftarPelunasan as $i=>$item): $kode=(string)($item['kode_sewa']??''); ?>
    <tr><td><?= (int)$i+1 ?></td><td class="p10-code"><?= $e($kode) ?></td><td><strong><?= $e($item['nm_lngkp']??'') ?></strong><br><small class="text-muted"><?= $e($item['user_plg']??'') ?></small></td><td><?= $e($item['no_hp']??'') ?></td><td><?= $e($item['tgl_mulai']??'') ?> s.d. <?= $e($item['tgl_selesai']??'') ?></td><td><span class="p10-pill"><?= $e($item['status_sewa']??'') ?></span></td><td><?= $rupiah($item['total_transaksi']??0) ?></td><td><?= $rupiah($item['total_terbayar']??0) ?></td><td class="p10-amount"><?= $rupiah($item['sisa_kewajiban']??0) ?></td><td><a class="btn btn-xs p10-btn" href="<?= $e(site_url('admin/pembayaran/pelunasan/'.str_replace('/','~',$kode))) ?>"><i class="fa fa-search"></i> Detail / Proses</a></td></tr>
   <?php endforeach; endif; ?></tbody></table></div></div>
 </section>
</div>
<?php require APPPATH . 'views/templates/footer.php'; ?>
