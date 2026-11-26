<?php
require APPPATH . 'views/templates/header.php';
require APPPATH . 'views/templates/sidebar.php';
$d = is_array($detailPelunasan ?? null) ? $detailPelunasan : [];
$riwayat = $d['riwayat_pembayaran'] ?? []; $kendaraan = $d['daftar_kendaraan'] ?? [];
$e = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$rp = static fn($v): string => 'Rp ' . number_format((int)$v, 0, ',', '.');
$total=(int)($d['total_transaksi']??0); $terbayar=(int)($d['total_terbayar']??0); $sisa=(int)($d['sisa_kewajiban']??0);
$status=(string)($d['status_sewa']??''); $kode=(string)($d['kode_sewa']??''); $adaPelunasan=false;
foreach($riwayat as $r){if(($r['jns_byr']??'')==='pelunasan'){$adaPelunasan=true;break;}}
$boleh=in_array($status,['disetujui','lunas'],true)&&$sisa>0&&!$adaPelunasan;
$urlProses=site_url('admin/pembayaran/pelunasan/proses/'.str_replace('/','~',$kode));
?>
<style>
.p10-detail{color:#273547}.p10-detail .p10-head{background:linear-gradient(120deg,#164f91,#287fd0);padding:20px 24px;border-radius:9px;color:white;margin-bottom:18px}.p10-detail .p10-head h2{margin:0 0 5px;color:#fff;font-weight:700}.p10-detail .p10-head p{margin:0;color:#eaf3ff}.p10-detail .box{border:1px solid #dce6f1;border-radius:8px;box-shadow:0 3px 14px #173c2608}.p10-detail .box-header{border-bottom:1px solid #dce6f1}.p10-detail .box-title{font-weight:700}.p10-detail .table th{background:#edf5ff;color:#31577e}.p10-detail .table td,.p10-detail .table th{vertical-align:middle!important}.p10-detail .p10-total{font-size:17px;color:#1769c2;font-weight:700}.p10-detail .p10-status{display:inline-block;padding:5px 9px;border-radius:20px;background:#eaf3ff;color:#1b5fa8;font-weight:700;font-size:11px}.p10-detail .p10-action{background:#1769c2;border-color:#1769c2;color:#fff}.p10-detail .p10-action:hover{background:#0f4f98;color:#fff}.p10-detail .p10-code{font-weight:700;color:#245b9b}
</style>
<div class="content-wrapper p10-detail">
 <section class="content-header"><h1>Detail Pelunasan <small>Informasi transaksi dan pencatatan pembayaran akhir</small></h1><ol class="breadcrumb"><li><a href="<?= $e(site_url('admin')) ?>">Dashboard</a></li><li><a href="<?= $e(site_url('admin/pembayaran/pelunasan')) ?>">Pelunasan Transaksi</a></li><li class="active">Detail</li></ol></section>
 <section class="content">
  <div class="p10-head"><h2><i class="fa fa-file-text-o"></i> Detail Transaksi Rental</h2><p>Periksa data penyewa, kendaraan, dan pembayaran sebelum mencatat pelunasan.</p></div>
  <?php if(is_array($flash??null)&&!empty($flash['message'])): ?><div class="alert <?= ($flash['type']??'')==='success'?'alert-success':'alert-danger' ?> alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= $e($flash['message']) ?></div><?php endif; ?>
  <div class="box"><div class="box-header with-border"><h3 class="box-title">Informasi Transaksi dan Penyewa</h3></div><div class="box-body"><dl class="dl-horizontal">
   <dt>Kode transaksi</dt><dd class="p10-code"><?= $e($kode) ?></dd><dt>Nama penyewa</dt><dd><?= $e($d['nm_lngkp']??'') ?></dd><dt>Username</dt><dd><?= $e($d['user_plg']??'') ?></dd><dt>NIK</dt><dd><?= $e($d['nik']??'') ?></dd><dt>Nomor HP</dt><dd><?= $e($d['no_hp']??'') ?></dd><dt>Alamat</dt><dd><?= nl2br($e($d['almt_lngkp']??'')) ?></dd><dt>Periode sewa</dt><dd><?= $e($d['tgl_mulai']??'') ?> s.d. <?= $e($d['tgl_selesai']??'') ?></dd><dt>Waktu pengambilan</dt><dd><?= $e($d['wkt_ambil']??'') ?></dd><dt>Status transaksi</dt><dd><span class="p10-status"><?= $e($status) ?></span></dd>
  </dl></div></div>
  <div class="box"><div class="box-header with-border"><h3 class="box-title">Kendaraan yang Disewa</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>No.</th><th>No. Polisi</th><th>Nama Kendaraan</th><th>Tarif Snapshot / Hari</th><th>Lama Sewa</th><th>Subtotal</th></tr></thead><tbody>
   <?php if(!$kendaraan): ?><tr><td colspan="6" class="text-center">Rincian kendaraan tidak tersedia.</td></tr><?php else: foreach($kendaraan as $i=>$v): ?><tr><td><?= $i+1 ?></td><td><?= $e($v['no_plat']??'') ?></td><td><?= $e($v['nm_mobil']??'') ?></td><td><?= $rp($v['hrg_sewa']??0) ?></td><td><?= (int)($v['n_hari']??0) ?> hari</td><td><?= $rp($v['subtotal']??0) ?></td></tr><?php endforeach; endif; ?>
   </tbody><tfoot><tr><th colspan="5" class="text-right">Total transaksi</th><th><?= $rp($total) ?></th></tr></tfoot></table></div></div>
  <div class="box"><div class="box-header with-border"><h3 class="box-title">Ringkasan Pembayaran</h3></div><div class="box-body"><table class="table table-bordered"><tbody><tr><th style="width:35%">Total transaksi</th><td><?= $rp($total) ?></td></tr><tr><th>Total pembayaran valid sebelumnya</th><td><?= $rp($terbayar) ?></td></tr><tr class="success"><th>Sisa kewajiban pelunasan</th><td class="p10-total"><?= $rp($sisa) ?></td></tr></tbody></table>
   <h4>Riwayat Pembayaran</h4><div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>No.</th><th>Kode Bayar</th><th>Tanggal</th><th>Jenis</th><th>Metode</th><th>Nominal</th><th>Status</th><th>Bukti</th></tr></thead><tbody>
   <?php if(!$riwayat): ?><tr><td colspan="8" class="text-center">Belum ada riwayat pembayaran.</td></tr><?php else: foreach($riwayat as $i=>$r): ?><tr><td><?= $i+1 ?></td><td class="p10-code"><?= $e($r['kode_bayar']??'') ?></td><td><?= $e($r['tgl_wkt_byr']??'') ?></td><td><?= $e($r['jns_byr']??'') ?></td><td><?= $e($r['mtode_byr']??'') ?></td><td><?= $rp($r['nominal']??0) ?></td><td><?= $e($r['sttus_byr']??'') ?></td><td><?php if(!empty($r['ft_byr'])): ?><a class="btn btn-info btn-xs" target="_blank" rel="noopener" href="<?= $e(site_url('transaksi/bukti?kode_bayar='.rawurlencode((string)$r['kode_bayar']))) ?>"><i class="fa fa-search"></i> Lihat bukti</a><?php else: ?>-<?php endif; ?></td></tr><?php endforeach; endif; ?>
   </tbody></table></div></div></div>
  <div class="box box-success"><div class="box-header with-border"><h3 class="box-title">Pencatatan Pelunasan</h3></div><div class="box-body">
   <?php if($boleh): ?><div class="alert alert-info">Pelunasan dilakukan satu kali sebesar seluruh sisa kewajiban. Pembayaran dicatat <strong>valid</strong> dan status transaksi menjadi <strong>lunas</strong>. Status kendaraan tidak diubah pada P10.</div>
   <form method="post" action="<?= $e($urlProses) ?>" enctype="multipart/form-data" id="p10-form-pelunasan"><input type="hidden" name="_token" value="<?= $e($token??'') ?>">
    <div class="form-group"><label for="mtode_byr">Metode pembayaran</label><select name="mtode_byr" id="mtode_byr" class="form-control" required><option value="">-- Pilih metode pembayaran --</option><option value="tunai">Tunai</option><option value="transfer">Transfer</option></select></div>
    <div class="form-group" id="p10-grup-bukti" style="display:none"><label for="ft_byr">Bukti transfer</label><input type="file" name="ft_byr" id="ft_byr" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"><p class="help-block">Unggah bukti transfer yang diterima admin dari penyewa. Format JPEG, PNG, atau WEBP, maksimal 5 MB.</p></div>
    <button type="submit" class="btn p10-action" onclick="return confirm('Catat pelunasan sebesar <?= $e($rp($sisa)) ?>? Pastikan pembayaran benar-benar telah diterima.')"><i class="fa fa-check"></i> Catat Pelunasan <?= $rp($sisa) ?></button>
   </form>
   <?php elseif($adaPelunasan): ?><div class="alert alert-success">Transaksi ini sudah memiliki record pelunasan. Pencatatan pelunasan tambahan tidak diizinkan.</div><?php elseif($sisa<=0): ?><div class="alert alert-info">Tidak terdapat sisa kewajiban pembayaran.</div><?php else: ?><div class="alert alert-warning">Status transaksi saat ini tidak memenuhi syarat pencatatan pelunasan pada P10.</div><?php endif; ?>
  </div></div>
  <a href="<?= $e(site_url('admin/pembayaran/dokumen/'.str_replace('/','~',$kode))) ?>" class="btn btn-info" target="_blank" rel="noopener"><i class="fa fa-print"></i> Lihat / Cetak Dokumen Transaksi</a>
  <a href="<?= $e(site_url('admin/pembayaran/pelunasan')) ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali ke daftar</a>
 </section>
</div>
<script>
(function(){'use strict';var m=document.getElementById('mtode_byr'),g=document.getElementById('p10-grup-bukti'),f=document.getElementById('ft_byr');if(!m||!g||!f)return;function sync(){var need=m.value==='transfer';g.style.display=need?'':'none';f.required=need;if(!need)f.value='';}m.addEventListener('change',sync);sync();})();
</script>
<?php require APPPATH . 'views/templates/footer.php'; ?>
