<?php
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$rupiah=static fn($v)=>'Rp '.number_format((float)$v,0,',','.');
require APPPATH.'views/katalog/header.php';
require APPPATH.'views/katalog/navbar.php';
?>
<style>
.p9-list-wrap{max-width:1120px;margin:38px auto 65px;padding:0 18px;color:#203047}.p9-list-hero{padding:27px 30px;border-radius:17px;background:linear-gradient(120deg,#245b9b,#287fd0);color:#fff;box-shadow:0 12px 30px #245b9b25}.p9-list-hero h1{margin:0 0 7px;color:#fff;font-size:27px;font-weight:800}.p9-list-card{margin-top:20px;padding:22px;background:#fff;border:1px solid #e7edf5;border-radius:15px;box-shadow:0 8px 25px #17385d0c}.p9-table-wrap{overflow-x:auto}.p9-table{width:100%;border-collapse:collapse;min-width:850px}.p9-table th,.p9-table td{padding:13px 12px;border-bottom:1px solid #e9eef5;text-align:left;vertical-align:middle}.p9-table th{color:#64748b;font-size:13px;background:#f7f9fc}.p9-pill{display:inline-block;padding:6px 10px;border-radius:20px;font-size:12px;font-weight:700;background:#fff2d7;color:#956000}.p9-pill.valid{background:#dcfce7;color:#164f91}.p9-pill.invalid{background:#fee2e2;color:#991b1b}.p9-action{display:inline-block;padding:8px 12px;border-radius:8px;background:#1769c2;color:white;text-decoration:none;font-weight:700}.p9-empty{padding:35px;text-align:center;color:#64748b}.p9-small{color:#64748b;font-size:12px}
</style>
<main class="p9-list-wrap">
 <section class="p9-list-hero"><h1><i class="fa fa-credit-card"></i> Pembayaran Saya</h1><p class="mb-0">Lihat tagihan DP, unggah bukti transfer, dan pantau status pembayaran penyewaan kendaraan Anda.</p></section>
 <?php if(!empty($flash)): ?><div class="alert <?= ($flash['type']??'')==='success'?'alert-success':'alert-danger' ?>" style="margin-top:18px"><?= $e($flash['message']??'') ?></div><?php endif; ?>
 <section class="p9-list-card"><h3 style="margin:0 0 18px;font-weight:800">Riwayat Pembayaran DP</h3>
 <?php if(empty($daftar)): ?><div class="p9-empty"><i class="fa fa-inbox fa-3x"></i><h4>Belum ada pembayaran</h4><p>Tagihan DP akan muncul di sini setelah Anda menyelesaikan checkout.</p><a class="p9-action" href="<?= $e(site_url('katalog')) ?>">Lihat Katalog Kendaraan</a></div>
 <?php else: ?><div class="p9-table-wrap"><table class="p9-table"><thead><tr><th>No.</th><th>Kode Bayar</th><th>Kode Sewa</th><th>Nominal DP</th><th>Tanggal</th><th>Status Bayar</th><th>Status Sewa</th><th>Aksi</th></tr></thead><tbody>
 <?php foreach($daftar as $i=>$row): $st=(string)($row['sttus_byr']??'pending'); ?><tr>
 <td><?= $i+1 ?></td><td><strong><?= $e($row['kode_bayar']) ?></strong></td><td><?= $e($row['kode_sewa']) ?></td><td><strong><?= $rupiah($row['nominal']) ?></strong></td>
 <td><?= $e($row['tgl_wkt_byr']??'-') ?></td><td><span class="p9-pill <?= $st==='valid'?'valid':($st==='invalid'?'invalid':'') ?>"><?= $e(strtoupper($st)) ?></span></td><td><?= $e(ucfirst((string)($row['status_sewa']??'-'))) ?></td>
 <td><a class="p9-action" href="<?= $e(site_url('transaksi/pembayaran?kode_sewa='.rawurlencode((string)$row['kode_sewa']))) ?>">Detail / Bayar</a></td></tr><?php endforeach; ?>
 </tbody></table></div><?php endif; ?>
 </section>
</main>
<?php require APPPATH.'views/katalog/footer.php'; ?>
