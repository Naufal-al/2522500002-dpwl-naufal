<?php
require APPPATH.'/views/templates/header.php';
require APPPATH.'/views/templates/sidebar.php';
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$rupiah = static fn($v) => 'Rp '.number_format((float)$v, 0, ',', '.');
$rows = is_array($daftar ?? null) ? $daftar : [];
$counts = ['semua'=>count($rows), 'pending'=>0, 'valid'=>0, 'invalid'=>0];
foreach ($rows as $r) {
    $st = (string)($r['sttus_byr'] ?? '');
    if (isset($counts[$st])) $counts[$st]++;
}
$statusLabel = static function($status) {
    return match ((string)$status) {
        'valid' => ['Valid', 'success'],
        'invalid' => ['Ditolak', 'danger'],
        default => ['Menunggu verifikasi', 'warning'],
    };
};
$sewaLabel = static function($status) {
    return match ((string)$status) {
        'disetujui' => ['Disetujui', 'success'],
        'ditolak' => ['Ditolak', 'danger'],
        default => ['Diajukan', 'warning'],
    };
};
?>
<style>
.p9-admin{color:#243247}.p9-admin .p9-banner{padding:23px 26px;border-radius:13px;color:#fff;background:linear-gradient(115deg,#174477,#247fc1);margin-bottom:18px;box-shadow:0 7px 20px rgba(25,73,120,.12)}
.p9-admin .p9-banner h2{color:#fff;font-size:23px;font-weight:700;margin:0 0 7px}.p9-admin .p9-banner p{margin:0;color:#eaf4ff}
.p9-admin .p9-panel{border:1px solid #e8edf4;border-radius:10px;overflow:hidden;box-shadow:0 4px 16px rgba(25,55,90,.06)}.p9-admin .p9-panel>.box-header{padding:17px 20px;background:#fff;border-bottom:1px solid #edf1f6}.p9-admin .p9-panel>.box-body{padding:18px 20px}
.p9-admin .p9-tabs{display:flex;gap:7px;flex-wrap:wrap;justify-content:flex-end}.p9-admin .p9-tabs a{display:inline-block;padding:8px 12px;border:1px solid #e2e8f0;border-radius:6px;background:#fff;color:#526176;font-size:12px;text-decoration:none}.p9-admin .p9-tabs a.active{background:#2878b9;border-color:#2878b9;color:#fff}.p9-admin .p9-tabs .count{font-weight:700;margin-left:4px}
.p9-admin .p9-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px}.p9-admin .p9-toolbar label{font-weight:600;margin:0}.p9-admin .p9-toolbar select,.p9-admin .p9-toolbar input{height:34px;border:1px solid #d8e0ea;border-radius:5px;padding:5px 9px;background:#fff}.p9-admin table{margin-bottom:10px;font-size:13px}.p9-admin table th{background:#f2f6fb;color:#35445b;white-space:nowrap}.p9-admin table td,.p9-admin table th{vertical-align:middle!important}.p9-admin .p9-code{font-weight:700;color:#29496d;white-space:nowrap}.p9-admin .p9-badge{display:inline-block;padding:5px 8px;border-radius:4px;font-size:11px;font-weight:600;white-space:nowrap}.p9-admin .p9-badge.warning{background:#fff0d7;color:#99620b}.p9-admin .p9-badge.success{background:#dcf5e7;color:#1769c2}.p9-admin .p9-badge.danger{background:#fde3e3;color:#a52c2c}.p9-admin .p9-badge.muted{background:#edf0f4;color:#667085}.p9-admin .p9-actions{display:flex;gap:5px;flex-wrap:wrap}.p9-admin .p9-actions form{display:inline}.p9-admin .btn{border-radius:5px}.p9-admin .p9-footer{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;color:#68778c;font-size:12px}.p9-admin .p9-pagination button{border:1px solid #dce3ec;background:#fff;padding:5px 10px;margin-left:3px;border-radius:4px}.p9-admin .p9-pagination button.active{background:#2878b9;color:#fff;border-color:#2878b9}.p9-admin .p9-empty{text-align:center;padding:30px;color:#78869a}
@media(max-width:767px){.p9-admin .p9-tabs{justify-content:flex-start}.p9-admin .p9-panel>.box-body{padding:12px}.p9-admin .p9-banner{padding:18px}}
</style>
<div class="content-wrapper p9-admin">
 <section class="content-header"><h1>Verifikasi Pembayaran <small>Pembayaran DP oleh penyewa</small></h1><ol class="breadcrumb"><li><a href="<?= $e(site_url('admin')) ?>">Dashboard</a></li><li class="active">Verifikasi Pembayaran</li></ol></section>
 <section class="content">
  <div class="p9-banner"><h2><i class="fa fa-shield"></i> Pemeriksaan Pembayaran DP</h2><p>Periksa bukti transfer penyewa, lalu tentukan apakah pembayaran diterima atau ditolak.</p></div>
  <?php if (!empty($flash)): ?><div class="alert <?= ($flash['type'] ?? '') === 'success' ? 'alert-success' : 'alert-danger' ?>"><?= $e($flash['message'] ?? '') ?></div><?php endif; ?>
  <div class="box p9-panel">
   <div class="box-header">
    <div class="row" style="display:flex;align-items:center;flex-wrap:wrap;gap:12px">
     <div class="col-sm-4"><h3 class="box-title"><i class="fa fa-list-alt"></i> Data Pembayaran DP</h3></div>
     <div class="col-sm-8"><nav class="p9-tabs" aria-label="Filter status pembayaran">
      <a href="#" class="active" data-filter="semua">Semua <span class="count"><?= $counts['semua'] ?></span></a>
      <a href="#" data-filter="pending">Menunggu <span class="count"><?= $counts['pending'] ?></span></a>
      <a href="#" data-filter="valid">Valid <span class="count"><?= $counts['valid'] ?></span></a>
      <a href="#" data-filter="invalid">Ditolak <span class="count"><?= $counts['invalid'] ?></span></a>
     </nav></div>
    </div>
   </div>
   <div class="box-body">
    <div class="p9-toolbar">
     <label>Tampilkan <select id="p9-limit"><option>10</option><option>25</option><option>50</option><option value="all">Semua</option></select> data</label>
     <label>Cari: <input type="search" id="p9-search" placeholder="Kode bayar / penyewa..."></label>
    </div>
    <div class="table-responsive">
     <table class="table table-bordered table-hover" id="p9-table">
      <thead><tr><th>No</th><th>Kode Bayar</th><th>Kode Sewa</th><th>Penyewa</th><th>Nominal DP</th><th>Waktu Unggah</th><th>Bukti</th><th>Status Bayar</th><th>Status Sewa</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php if (!$rows): ?><tr><td colspan="10" class="p9-empty">Belum ada data pembayaran DP.</td></tr>
      <?php else: foreach ($rows as $i => $b):
        $status = (string)($b['sttus_byr'] ?? 'pending'); [$stText,$stClass] = $statusLabel($status); [$ssText,$ssClass] = $sewaLabel($b['status_sewa'] ?? '');
        $hasProof = !empty($b['ft_byr']);
        $searchText = strtolower(implode(' ', [(string)$b['kode_bayar'],(string)$b['kode_sewa'],(string)$b['nm_lngkp'],(string)$b['user_plg'],(string)$status]));
      ?>
       <tr data-status="<?= $e($status) ?>" data-search="<?= $e($searchText) ?>">
        <td><?= $i+1 ?></td>
        <td class="p9-code"><?= $e($b['kode_bayar']) ?></td>
        <td class="p9-code"><?= $e($b['kode_sewa']) ?></td>
        <td><strong><?= $e($b['nm_lngkp']) ?></strong><br><small class="text-muted"><?= $e($b['user_plg']) ?></small></td>
        <td><strong><?= $rupiah($b['nominal']) ?></strong></td>
        <td><?= $hasProof ? $e($b['tgl_wkt_byr']) : '<span class="text-muted">Belum diunggah</span>' ?></td>
        <td><?php if ($hasProof): ?><a class="btn btn-info btn-xs" target="_blank" rel="noopener" href="<?= $e(site_url('transaksi/bukti?kode_bayar='.rawurlencode($b['kode_bayar']))) ?>"><i class="fa fa-search"></i> Periksa</a><?php else: ?><span class="p9-badge muted">Belum diunggah</span><?php endif; ?></td>
        <td><span class="p9-badge <?= $stClass ?>"><?= $e($stText) ?></span></td>
        <td><span class="p9-badge <?= $ssClass ?>"><?= $e($ssText) ?></span></td>
        <td><?php if ($status === 'pending'): ?>
          <?php if ($hasProof): ?><div class="p9-actions">
           <form method="post" action="<?= $e(site_url('admin/verifikasi-pembayaran/proses')) ?>" onsubmit="return confirm('Terima pembayaran <?= $e($b['kode_bayar']) ?>?')"><input type="hidden" name="_token" value="<?= $e($token) ?>"><input type="hidden" name="kode_bayar" value="<?= $e($b['kode_bayar']) ?>"><input type="hidden" name="keputusan" value="terima"><button type="submit" class="btn btn-success btn-xs"><i class="fa fa-check"></i> Terima</button></form>
           <form method="post" action="<?= $e(site_url('admin/verifikasi-pembayaran/proses')) ?>" onsubmit="return confirm('Tolak pembayaran <?= $e($b['kode_bayar']) ?>?')"><input type="hidden" name="_token" value="<?= $e($token) ?>"><input type="hidden" name="kode_bayar" value="<?= $e($b['kode_bayar']) ?>"><input type="hidden" name="keputusan" value="tolak"><button type="submit" class="btn btn-danger btn-xs"><i class="fa fa-times"></i> Tolak</button></form>
          </div><?php else: ?><button class="btn btn-default btn-xs" disabled>Menunggu bukti</button><?php endif; ?>
         <?php else: ?><span class="text-muted"><i class="fa fa-lock"></i> Selesai</span><?php endif; ?></td>
       </tr>
      <?php endforeach; endif; ?>
      </tbody>
     </table>
    </div>
    <div class="p9-footer"><span id="p9-info">Menampilkan data</span><div class="p9-pagination" id="p9-pagination"></div></div>
   </div>
  </div>
 </section>
</div>
<script>
(function(){
 const table=document.getElementById('p9-table'); if(!table)return;
 const body=table.querySelector('tbody'), rows=Array.from(body.querySelectorAll('tr[data-status]'));
 const search=document.getElementById('p9-search'), limit=document.getElementById('p9-limit'), info=document.getElementById('p9-info'), pager=document.getElementById('p9-pagination');
 let filter='semua', page=1;
 function render(){
  const q=(search.value||'').toLowerCase().trim();
  const visible=rows.filter(r=>(filter==='semua'||r.dataset.status===filter)&&(!q||(r.dataset.search||'').includes(q)));
  const size=limit.value==='all'?Math.max(visible.length,1):parseInt(limit.value,10); const pages=Math.max(1,Math.ceil(visible.length/size)); page=Math.min(page,pages);
  rows.forEach(r=>r.style.display='none');
  visible.slice((page-1)*size,page*size).forEach(r=>r.style.display='');
  info.textContent=visible.length?`Menampilkan ${((page-1)*size)+1}–${Math.min(page*size,visible.length)} dari ${visible.length} data`:'Tidak ada data yang cocok';
  pager.innerHTML='';
  if(pages>1){for(let n=1;n<=pages;n++){const b=document.createElement('button');b.type='button';b.textContent=n;if(n===page)b.className='active';b.addEventListener('click',()=>{page=n;render()});pager.appendChild(b)}}
 }
 document.querySelectorAll('.p9-tabs a').forEach(a=>a.addEventListener('click',e=>{e.preventDefault();filter=a.dataset.filter;page=1;document.querySelectorAll('.p9-tabs a').forEach(x=>x.classList.toggle('active',x===a));render()}));
 search.addEventListener('input',()=>{page=1;render()});limit.addEventListener('change',()=>{page=1;render()});render();
})();
</script>
<?php require APPPATH.'/views/templates/footer.php'; ?>
