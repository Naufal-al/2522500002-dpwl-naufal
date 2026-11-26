<?php
// Nama Berkas - application\config\routes.php
$route = [];
// Route P2/P3 - dipertahankan, penyesuaian terhadap penulisan nama Controller.
// $route['default_controller'] = 'Home'; // dimatikan pada P6 karena penulisan index.php dihilangkan pada URL aplikasi
$route['default_controller'] = 'Katalog_controller'; // karena penulisan index.php dihilangkan padaURL aplikasi
$route['info/(:any)'] = 'Home/info/$1';
$route['login'] = 'Auth/login';
$route['auth/login'] = 'Auth/login';
$route['auth/logout'] = 'Auth/logout';
$route['admin'] = 'Admin/index';
// Route P4: semua proses CRUD admin dipindahkan di URL admin/madmin.
$route['admin/madmin'] = 'Madmin_controller/index';
$route['admin/madmin/tambah'] = 'Madmin_controller/tambah';
$route['admin/madmin/ubah/(:any)'] = 'Madmin_controller/ubah/$1';
$route['admin/madmin/hapus/(:any)'] = 'Madmin_controller/hapus/$1';
// Route P5
// dari href(menu), diproses ke Mobil_controller dengan mengerjakan method index() yang memanggil
// admin\mobil_view
$route['admin/mobil'] = 'Mobil_controller/index';
$route['admin/mobil/tambah'] = 'Mobil_controller/tambah';
$route['admin/mobil/ubah/(:any)'] = 'Mobil_controller/ubah/$1';
$route['admin/mobil/hapus/(:any)'] = 'Mobil_controller/hapus/$1';
// UTS: CRUD Manajemen Data Penyewa pada sisi Admin.
$route['admin/penyewa'] = 'Manajemen_penyewa_controller/index';
$route['admin/penyewa/tambah'] = 'Manajemen_penyewa_controller/tambah';
$route['admin/penyewa/ubah/(:any)'] = 'Manajemen_penyewa_controller/ubah/$1';
$route['admin/penyewa/hapus/(:any)'] = 'Manajemen_penyewa_controller/hapus/$1';
// Route P6
$route['katalog'] = 'Katalog_controller/index';
$route['katalog/detail/(:any)'] = 'Katalog_controller/detail/$1';
$route['auth/register'] = 'Auth/register';
$route['auth/login-penyewa'] = 'Auth/loginPenyewa';
$route['auth/logout-penyewa'] = 'Auth/logoutPenyewa';
$route['penyewa/profil'] = 'Penyewa_controller/profil';
// Route P7 - Transaksi, Keranjang, dan Checkout
$route['transaksi/tambah'] = 'Transaksi_controller/tambah';
$route['transaksi/keranjang'] = 'Transaksi_controller/keranjang';
$route['transaksi/perbarui-periode'] = 'Transaksi_controller/perbaruiPeriode';
$route['transaksi/hapus'] = 'Transaksi_controller/hapus';
$route['transaksi/hapus/(:any)'] = 'Transaksi_controller/hapus/$1';
$route['transaksi/checkout'] = 'Transaksi_controller/checkout';
// Route P9 - Pembayaran DP penyewa dan verifikasi admin
$route['transaksi/pembayaran'] = 'Pembayaran_controller/index';
$route['transaksi/unggah-bukti'] = 'Pembayaran_controller/unggah';
$route['transaksi/bukti'] = 'Pembayaran_controller/bukti';
$route['admin/verifikasi-pembayaran'] = 'Pembayaran_controller/verifikasi';
$route['admin/verifikasi-pembayaran/proses'] = 'Pembayaran_controller/prosesVerifikasi';


// Route P10 - Pelunasan dan dokumen transaksi (route spesifik lebih dahulu)
$route['admin/pembayaran/pelunasan'] = 'Pembayaran_controller/pelunasanIndex';
$route['admin/pembayaran/pelunasan/proses/(:any)'] = 'Pembayaran_controller/prosesPelunasan/$1';
$route['admin/pembayaran/dokumen/(:any)'] = 'Pembayaran_controller/dokumenTransaksi/$1';
$route['admin/pembayaran/pelunasan/(:any)'] = 'Pembayaran_controller/pelunasanDetail/$1';
