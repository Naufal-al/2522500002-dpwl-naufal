<?php
require APPPATH . '/views/templates/header.php';
require APPPATH . '/views/templates/sidebar.php';
$penyewa = $penyewa ?? [];
$item = $item ?? [];
$mode = $mode ?? null;
$isForm = in_array($mode, ['tambah', 'ubah'], true);
$edit = $mode === 'ubah';
$esc = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$action = $edit
    ? site_url('admin/penyewa/ubah/' . rawurlencode((string)($item['user_plg'] ?? '')))
    : site_url('admin/penyewa/tambah');
$totalPenyewa = count($penyewa);
?>
<style>
/* Gaya khusus halaman Manajemen Penyewa; tidak memengaruhi halaman lain. */
.content-wrapper .tenant-page { color:#17243b; }
.tenant-page .tenant-hero{position:relative;overflow:hidden;border-radius:16px;padding:25px 28px;margin:0 0 20px;background:linear-gradient(115deg,#123f91 0%,#1769d2 62%,#65b5f7 100%);color:#fff;box-shadow:0 10px 25px rgba(25,91,180,.16)}
.tenant-page .tenant-hero:after{content:' ';position:absolute;right:-55px;top:-100px;width:330px;height:330px;border:38px solid rgba(255,255,255,.10);border-radius:50%}
.tenant-page .tenant-hero h1{font-size:27px;font-weight:700;margin:0 0 7px;color:#fff}.tenant-page .tenant-hero p{margin:0;color:rgba(255,255,255,.88);font-size:14px}.tenant-page .tenant-hero i{font-size:43px;float:left;margin:5px 20px 0 0;color:#fff}
.tenant-page .stat-card{background:#fff;border:1px solid #e8eef8;border-radius:13px;padding:17px 18px;margin-bottom:16px;box-shadow:0 5px 18px rgba(27,55,100,.055);min-height:91px}
.tenant-page .stat-card .stat-icon{width:49px;height:49px;line-height:49px;text-align:center;border-radius:12px;color:#fff;font-size:20px;float:left;margin-right:13px}
.tenant-page .stat-card .stat-number{font-size:25px;font-weight:700;line-height:27px;color:#17243b}.tenant-page .stat-card .stat-label{font-size:12px;color:#77839a;margin-top:5px}
.tenant-page .stat-blue .stat-icon{background:linear-gradient(135deg,#2385f6,#1761d5)}.tenant-page .stat-green .stat-icon{background:linear-gradient(135deg,#37c98a,#159b68)}.tenant-page .stat-orange .stat-icon{background:linear-gradient(135deg,#ffbd4a,#f28a16)}.tenant-page .stat-purple .stat-icon{background:linear-gradient(135deg,#a879f5,#7950d9)}
.tenant-page .tenant-panel{border:1px solid #e6ecf5;border-radius:14px;box-shadow:0 6px 22px rgba(27,55,100,.055);overflow:hidden;background:#fff}.tenant-page .tenant-panel>.box-header{padding:17px 20px;border-bottom:1px solid #edf1f7;background:#fff}.tenant-page .tenant-panel .box-title{font-size:16px;font-weight:700;color:#1b2b46}.tenant-page .tenant-panel>.box-body{padding:18px 20px}
.tenant-page .toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px}.tenant-page .btn-add{border:0;border-radius:9px;padding:10px 16px;font-weight:600;background:linear-gradient(135deg,#247ff0,#155ac6);box-shadow:0 4px 10px rgba(28,105,213,.18)}
.tenant-page .tenant-search{display:flex;gap:8px;max-width:390px;flex:1}.tenant-page .tenant-search input{height:39px;border:1px solid #dce5f1;border-radius:9px;box-shadow:none}.tenant-page .tenant-search .btn{border-radius:9px;padding:8px 15px}
.tenant-page table{margin-bottom:0!important;border:1px solid #e7edf6!important}.tenant-page table thead th{background:#f3f7fd;color:#33435d;font-weight:700;border-color:#e5ebf4!important;padding:13px 10px!important;white-space:nowrap}.tenant-page table tbody td{padding:12px 10px!important;vertical-align:middle!important;border-color:#edf1f7!important;color:#40506a}.tenant-page table tbody tr:hover{background:#f8fbff}.tenant-page .doc-link{display:inline-flex;align-items:center;gap:5px;padding:5px 8px;background:#eef5ff;border-radius:7px;color:#1767ca;font-size:12px;font-weight:600}.tenant-page .doc-empty{color:#9aa6b7;font-size:12px}.tenant-page .action-group{display:flex;gap:5px;white-space:nowrap}.tenant-page .action-group .btn{border-radius:7px;padding:5px 9px}.tenant-page .form-modern{border:1px solid #e6ecf5;border-radius:14px;overflow:hidden;box-shadow:0 6px 22px rgba(27,55,100,.055)}.tenant-page .form-modern>.box-header{padding:16px 20px;background:#f7faff;border-bottom:1px solid #e9eff8}.tenant-page .form-modern .box-body{padding:20px}.tenant-page .form-modern .form-control{border-radius:8px;border-color:#dce5f1;box-shadow:none;height:39px}.tenant-page .form-modern textarea.form-control{height:auto}.tenant-page .form-modern .form-control:focus{border-color:#5b9df0;box-shadow:0 0 0 2px rgba(45,126,225,.1)}.tenant-page .form-modern .box-footer{padding:14px 20px;background:#fafcff;border-top:1px solid #edf1f7}
@media(max-width:767px){.tenant-page .tenant-hero{padding:20px}.tenant-page .tenant-hero h1{font-size:22px}.tenant-page .tenant-search{max-width:none;width:100%}.tenant-page .stat-card{min-height:80px}}
</style>
<div class="content-wrapper tenant-page">
<section class="content-header"><h1>Manajemen Penyewa <small>Kelola data penyewa</small></h1><ol class="breadcrumb"><li><a href="<?= $esc(site_url('admin')) ?>">Dashboard</a></li><li class="active">Manajemen Penyewa</li></ol></section>
<section class="content">
<?php if (!empty($flash)): ?><div class="alert <?= ($flash['type'] ?? '') === 'success' ? 'alert-success' : 'alert-danger' ?> alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= $esc($flash['message'] ?? '') ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= $esc($error) ?></div><?php endif; ?>
<div class="tenant-hero"><i class="fa fa-users"></i><h1>Manajemen Penyewa</h1><p>Kelola informasi dan dokumen penyewa dengan lebih mudah dan cepat.</p></div>
<div class="row">
 <div class="col-sm-6 col-lg-3"><div class="stat-card stat-blue"><div class="stat-icon"><i class="fa fa-users"></i></div><div class="stat-number"><?= $totalPenyewa ?></div><div class="stat-label">Total Penyewa</div></div></div>
 <div class="col-sm-6 col-lg-3"><div class="stat-card stat-green"><div class="stat-icon"><i class="fa fa-id-card"></i></div><div class="stat-number"><?= count(array_filter($penyewa, static fn($p) => !empty($p['ft_ktp']))) ?></div><div class="stat-label">Dokumen KTP Tersedia</div></div></div>
 <div class="col-sm-6 col-lg-3"><div class="stat-card stat-orange"><div class="stat-icon"><i class="fa fa-address-card"></i></div><div class="stat-number"><?= count(array_filter($penyewa, static fn($p) => !empty($p['ft_sim_a']))) ?></div><div class="stat-label">Dokumen SIM A Tersedia</div></div></div>
 <div class="col-sm-6 col-lg-3"><div class="stat-card stat-purple"><div class="stat-icon"><i class="fa fa-shield"></i></div><div class="stat-number">Aktif</div><div class="stat-label">Pengelolaan Data</div></div></div>
</div>
<div class="box tenant-panel"><div class="box-header"><h3 class="box-title"><i class="fa fa-list-ul text-primary"></i> Daftar Data Penyewa</h3></div><div class="box-body">
<div class="toolbar"><a class="btn btn-primary btn-add" href="<?= $esc(site_url('admin/penyewa/tambah')) ?>"><i class="fa fa-plus"></i> Tambah Penyewa</a><div class="tenant-search"><input id="tenantQuickSearch" type="search" class="form-control" placeholder="Cari nama, username, NIK, atau nomor HP..."><button id="tenantSearchBtn" type="button" class="btn btn-primary"><i class="fa fa-search"></i> Cari</button></div></div>
<div class="table-responsive"><table id="tenantTable" class="table table-hover table-bordered js-data-table"><thead><tr><th>No</th><th>Username</th><th>NIK</th><th>Nama Lengkap</th><th>No. HP</th><th>Alamat</th><th>Foto KTP</th><th>Foto SIM A</th><th class="no-sort">Aksi</th></tr></thead><tbody>
<?php foreach ($penyewa as $i => $p): ?><tr><td><?= $i + 1 ?></td><td><strong><?= $esc($p['user_plg'] ?? '') ?></strong></td><td><?= $esc($p['nik'] ?? '') ?></td><td><?= $esc($p['nm_lngkp'] ?? '') ?></td><td><?= $esc($p['no_hp'] ?? '') ?></td><td><?= $esc($p['almt_lngkp'] ?? '') ?></td>
<?php foreach (['ft_ktp' => 'KTP', 'ft_sim_a' => 'SIM A'] as $field => $label): ?><td><?php if (!empty($p[$field])): ?><a class="doc-link" target="_blank" rel="noopener" href="<?= $esc(base_url('uploads/penyewa/' . rawurlencode($p[$field]))) ?>"><i class="fa fa-file-image-o"></i> Lihat <?= $label ?></a><?php else: ?><span class="doc-empty">Belum ada</span><?php endif; ?></td><?php endforeach; ?>
<td><div class="action-group"><a class="btn btn-warning btn-xs" title="Ubah data" href="<?= $esc(site_url('admin/penyewa/ubah/' . rawurlencode($p['user_plg'] ?? ''))) ?>"><i class="fa fa-pencil"></i></a><form method="post" action="<?= $esc(site_url('admin/penyewa/hapus/' . rawurlencode($p['user_plg'] ?? ''))) ?>" onsubmit="return confirm('Hapus data penyewa ini?');"><input type="hidden" name="_token" value="<?= $esc($token ?? '') ?>"><button class="btn btn-danger btn-xs" title="Hapus data" type="submit"><i class="fa fa-trash"></i></button></form></div></td></tr><?php endforeach; ?>
</tbody></table></div>
</div></div>
<?php if ($isForm): ?><div class="box box-primary form-modern"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-<?= $edit ? 'pencil' : 'user-plus' ?>"></i> <?= $edit ? 'Ubah' : 'Tambah' ?> Data Penyewa</h3></div><form method="post" enctype="multipart/form-data" action="<?= $esc($action) ?>"><input type="hidden" name="_token" value="<?= $esc($token ?? '') ?>"><div class="box-body"><div class="row">
<div class="col-md-6"><div class="form-group"><label>Username</label><input class="form-control" name="user_plg" maxlength="50" required value="<?= $esc($item['user_plg'] ?? '') ?>" <?= $edit ? 'readonly' : '' ?>></div></div>
<div class="col-md-6"><div class="form-group"><label>NIK (16 digit)</label><input class="form-control" name="nik" maxlength="16" pattern="[0-9]{16}" required value="<?= $esc($item['nik'] ?? '') ?>"></div></div>
<div class="col-md-6"><div class="form-group"><label>Nama Lengkap</label><input class="form-control" name="nm_lngkp" maxlength="100" required value="<?= $esc($item['nm_lngkp'] ?? '') ?>"></div></div>
<div class="col-md-6"><div class="form-group"><label>Nomor HP</label><input class="form-control" name="no_hp" maxlength="15" required value="<?= $esc($item['no_hp'] ?? '') ?>"></div></div>
<div class="col-md-12"><div class="form-group"><label>Alamat Lengkap</label><textarea class="form-control" name="almt_lngkp" maxlength="255" required><?= $esc($item['almt_lngkp'] ?? '') ?></textarea></div></div>
<div class="col-md-6"><div class="form-group"><label>Password <?= $edit ? '(kosongkan jika tidak diubah)' : '' ?></label><input class="form-control" type="password" name="password" minlength="8" maxlength="72" <?= $edit ? '' : 'required' ?> autocomplete="new-password"></div></div>
<div class="col-md-6"><div class="form-group"><label>Foto KTP (JPG/PNG/WEBP, maks. 3 MB)</label><input type="file" name="ft_ktp" accept="image/jpeg,image/png,image/webp"><?php if (!empty($item['ft_ktp'])): ?><p class="help-block">Foto KTP saat ini: <?= $esc($item['ft_ktp']) ?></p><?php endif; ?></div></div>
<div class="col-md-6"><div class="form-group"><label>Foto SIM A (JPG/PNG/WEBP, maks. 3 MB)</label><input type="file" name="ft_sim_a" accept="image/jpeg,image/png,image/webp"><?php if (!empty($item['ft_sim_a'])): ?><p class="help-block">Foto SIM A saat ini: <?= $esc($item['ft_sim_a']) ?></p><?php endif; ?></div></div>
</div><p class="text-muted"><i class="fa fa-info-circle"></i> Foto lama tetap digunakan jika tidak memilih file baru. Password disimpan dalam bentuk hash.</p></div><div class="box-footer"><button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Simpan</button> <a class="btn btn-default" href="<?= $esc(site_url('admin/penyewa')) ?>">Batal</a></div></form></div><?php endif; ?>
</section></div>
<script>
(function(){
 var input=document.getElementById('tenantQuickSearch'), button=document.getElementById('tenantSearchBtn'), table=document.getElementById('tenantTable');
 if(!input||!button||!table)return;
 function searchRows(){var q=input.value.toLowerCase().trim();Array.prototype.forEach.call(table.tBodies[0].rows,function(row){row.style.display=row.textContent.toLowerCase().indexOf(q)!==-1?'':'none';});}
 button.addEventListener('click',searchRows);input.addEventListener('keyup',function(e){if(e.key==='Enter')searchRows();});
})();
</script>
<?php require APPPATH . '/views/templates/footer.php'; ?>
