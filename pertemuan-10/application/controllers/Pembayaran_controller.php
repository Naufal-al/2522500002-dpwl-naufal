<?php
require_once APPPATH.'models/Pembayaran_model.php';
require_once APPPATH.'helpers/auth_helper.php';
require_once APPPATH.'helpers/flash_helper.php';

class Pembayaran_controller extends Controller
{
    private Pembayaran_model $model;
    public function __construct(){ $this->model=new Pembayaran_model(); }
    private function method(string $m): void { if(strtoupper($_SERVER['REQUEST_METHOD']??'GET')!==$m){http_response_code(405);header('Allow: '.$m);exit('Method Not Allowed');} }
    private function csrf(): string { if(empty($_SESSION['_csrf_p9'])) $_SESSION['_csrf_p9']=bin2hex(random_bytes(32)); return $_SESSION['_csrf_p9']; }
    private function checkCsrf(): void { $sent=(string)($_POST['_token']??''); if($sent==='' || !hash_equals($this->csrf(),$sent)){http_response_code(419);exit('Token keamanan tidak valid. Muat ulang halaman.');} }
    private function penyewa(): string
    {
        $u = trim((string) ($_SESSION['penyewa_user_plg'] ?? ''));

        // Satu sesi hanya boleh mewakili satu jenis akun. Jika penyewa sudah
        // berhasil login tetapi masih ada penanda sesi admin lama, utamakan
        // identitas penyewa yang aktif dan bersihkan penanda admin yang usang.
        if ($u !== '') {
            unset($_SESSION['admin_username'], $_SESSION['admin_status']);
            return $u;
        }

        if (is_admin_logged_in()) {
            http_response_code(403);
            exit('Admin tidak dapat mengakses proses pembayaran penyewa. Silakan logout admin dan login sebagai penyewa.');
        }

        $_SESSION['flash_penyewa_nav'] = 'Silakan login untuk mengakses pembayaran.';
        header('Location: ' . site_url('auth/login-penyewa'));
        exit;
    }
    private function pesan(string $type,string $message,string $url): never { set_flash_message($type,$message); header('Location: '.site_url($url)); exit; }

    public function index(string $kodeSewa=''): void
    {
        $user=$this->penyewa(); $this->method('GET'); $kodeSewa=trim($kodeSewa!==''?$kodeSewa:(string)($_GET['kode_sewa']??''));
        if($kodeSewa===''){
            $this->view('transaksi/daftar_pembayaran',['daftar'=>$this->model->daftarMilikPenyewa($user),'flash'=>get_flash_message()]);
            return;
        }
        $bayar=$this->model->getMilikPenyewa($kodeSewa,$user);
        if(!$bayar){http_response_code(404);exit('Pembayaran tidak ditemukan atau bukan milik akun Anda.');}
        $this->view('transaksi/pembayaran',['bayar'=>$bayar,'flash'=>get_flash_message(),'token'=>$this->csrf()]);
    }

    public function unggah(): void
    {
        $user=$this->penyewa(); $this->method('POST'); $this->checkCsrf();
        $kodeSewa=trim((string)($_POST['kode_sewa']??''));
        $bayar=$this->model->getMilikPenyewa($kodeSewa,$user);
        if(!$bayar){http_response_code(404);exit('Pembayaran tidak ditemukan atau bukan milik akun Anda.');}
        if(($bayar['sttus_byr']??'')!=='pending') $this->pesan('error','Pembayaran sudah diverifikasi dan tidak dapat diubah.','transaksi/pembayaran?kode_sewa='.rawurlencode($kodeSewa));
        $f=$_FILES['ft_byr']??null;
        if(!$f || ($f['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) $this->pesan('error','Pilih bukti transfer terlebih dahulu.','transaksi/pembayaran?kode_sewa='.rawurlencode($kodeSewa));
        if(($f['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name']??'')) $this->pesan('error','Berkas gagal diunggah. Periksa ukuran dan konfigurasi PHP.','transaksi/pembayaran?kode_sewa='.rawurlencode($kodeSewa));
        if((int)$f['size']<1 || (int)$f['size']>3*1024*1024) $this->pesan('error','Ukuran bukti harus lebih dari 0 byte dan maksimal 3 MB.','transaksi/pembayaran?kode_sewa='.rawurlencode($kodeSewa));
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        if(!isset($allowed[$mime]) || @getimagesize($f['tmp_name'])===false) $this->pesan('error','Bukti harus berupa gambar JPG, PNG, atau WEBP yang valid.','transaksi/pembayaran?kode_sewa='.rawurlencode($kodeSewa));
        $dir=FCPATH.'uploads'.DIRECTORY_SEPARATOR.'bayar'.DIRECTORY_SEPARATOR;
        if(!is_dir($dir) && !mkdir($dir,0755,true) && !is_dir($dir)) $this->pesan('error','Folder penyimpanan bukti tidak dapat dibuat.','transaksi/pembayaran?kode_sewa='.rawurlencode($kodeSewa));
        if(!is_writable($dir)) $this->pesan('error','Folder penyimpanan bukti tidak dapat ditulis.','transaksi/pembayaran?kode_sewa='.rawurlencode($kodeSewa));
        $filename='bukti_'.bin2hex(random_bytes(16)).'.'.$allowed[$mime];
        if(!move_uploaded_file($f['tmp_name'],$dir.$filename)) $this->pesan('error','Bukti transfer gagal disimpan.','transaksi/pembayaran?kode_sewa='.rawurlencode($kodeSewa));
        try {
            $result=$this->model->simpanBukti((string)$bayar['kode_bayar'],$filename);
            if(!$result['ok']) { @unlink($dir.$filename); $this->pesan('error','Pembayaran sudah diproses atau tidak dapat diperbarui.','transaksi/pembayaran?kode_sewa='.rawurlencode($kodeSewa)); }
            if(!empty($result['old']) && preg_match('/^bukti_[a-f0-9]{32}\.(jpg|png|webp)$/',(string)$result['old'])) @unlink($dir.$result['old']);
            $this->pesan('success','Bukti pembayaran berhasil diunggah. Status tetap pending sampai diverifikasi admin.','transaksi/pembayaran?kode_sewa='.rawurlencode($kodeSewa));
        } catch(Throwable $e){ @unlink($dir.$filename); error_log('Upload bukti P9: '.$e->getMessage()); $this->pesan('error','Bukti belum berhasil disimpan ke database. Silakan coba kembali.','transaksi/pembayaran?kode_sewa='.rawurlencode($kodeSewa)); }
    }

    public function verifikasi(): void
    {
        require_admin_login(); $this->method('GET');
        $this->view('admin/verifikasi_pembayaran',['username'=>$_SESSION['admin_username'],'daftar'=>$this->model->daftarSemua(),'flash'=>get_flash_message(),'token'=>$this->csrf()]);
    }
    public function prosesVerifikasi(): void
    {
        require_admin_login(); $this->method('POST'); $this->checkCsrf();
        $kode=trim((string)($_POST['kode_bayar']??'')); $keputusan=(string)($_POST['keputusan']??'');
        if($kode==='' || !in_array($keputusan,['terima','tolak'],true)) $this->pesan('error','Data keputusan verifikasi tidak valid.','admin/verifikasi-pembayaran');
        try { $this->model->verifikasi($kode,$keputusan); $msg=$keputusan==='terima'?'Pembayaran diterima. Status transaksi menjadi disetujui.':'Pembayaran ditolak. Status transaksi menjadi ditolak.'; $this->pesan('success',$msg,'admin/verifikasi-pembayaran'); }
        catch(Throwable $e){ error_log('Verifikasi pembayaran P9: '.$e->getMessage()); $this->pesan('error',$e instanceof RuntimeException?$e->getMessage():'Verifikasi gagal diproses.','admin/verifikasi-pembayaran'); }
    }
    public function bukti(string $kodeBayar=''): void
    {
        $kodeBayar=trim($kodeBayar!==''?$kodeBayar:(string)($_GET['kode_bayar']??''));
        $isAdmin=is_admin_logged_in(); $user=trim((string)($_SESSION['penyewa_user_plg']??''));
        if(!$isAdmin && $user===''){http_response_code(403);exit('Akses ditolak.');}
        $row=$this->model->getByKode(trim($kodeBayar));
        if(!$row || (!$isAdmin && $row['user_plg']!==$user)){http_response_code(404);exit('Bukti tidak ditemukan.');}
        $name=(string)($row['ft_byr']??'');
        if(!preg_match('/^bukti_[a-f0-9]{32}\.(jpg|png|webp)$/',$name)){http_response_code(404);exit('Bukti belum tersedia.');}
        $path=FCPATH.'uploads'.DIRECTORY_SEPARATOR.'bayar'.DIRECTORY_SEPARATOR.$name;
        if(!is_file($path)){http_response_code(404);exit('Berkas bukti tidak ditemukan.');}
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($path); if(!in_array($mime,['image/jpeg','image/png','image/webp'],true)){http_response_code(415);exit('Jenis berkas tidak didukung.');}
        header('Content-Type: '.$mime); header('Content-Length: '.filesize($path)); header('X-Content-Type-Options: nosniff'); header('Cache-Control: private, no-store'); readfile($path); exit;
    }


    /** P10: daftar transaksi yang dapat dilunasi. */
    public function pelunasanIndex(): void
    {
        require_admin_login(); $this->method('GET');
        try {
            $this->view('admin/pelunasan', [
                'username'=>$_SESSION['admin_username'],
                'daftarPelunasan'=>$this->model->getDaftarPelunasan(),
                'flash'=>get_flash_message()
            ]);
        } catch (Throwable $e) {
            error_log('Daftar pelunasan P10: '.$e->getMessage());
            http_response_code(500); exit('Daftar transaksi pelunasan belum dapat ditampilkan. Silakan coba kembali.');
        }
    }

    /** P10: detail transaksi, riwayat pembayaran dan sisa kewajiban. */
    public function pelunasanDetail(string $kodeSewa=''): void
    {
        require_admin_login(); $this->method('GET');
        $kodeSewa=trim(str_replace('~','/',$kodeSewa));
        if ($kodeSewa==='') { http_response_code(400); exit('Kode transaksi sewa wajib tersedia.'); }
        try {
            $detail=$this->model->getDetailPelunasan($kodeSewa);
            if (!$detail) { http_response_code(404); exit('Transaksi sewa tidak ditemukan.'); }
            $this->view('admin/pelunasan_detail', [
                'username'=>$_SESSION['admin_username'], 'detailPelunasan'=>$detail,
                'flash'=>get_flash_message(), 'token'=>$this->csrf()
            ]);
        } catch (Throwable $e) {
            error_log('Detail pelunasan P10: '.$e->getMessage());
            http_response_code(500); exit('Detail transaksi pelunasan belum dapat ditampilkan. Silakan coba kembali.');
        }
    }

    /** P10: upload bukti transfer oleh admin, maksimum 5 MB. */
    private function simpanBuktiPelunasanAdmin(): array
    {
        $f=$_FILES['ft_byr']??null;
        if (!$f || ($f['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) return ['success'=>false,'error'=>'Bukti transfer wajib diunggah.'];
        if (($f['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name']??'')) return ['success'=>false,'error'=>'Berkas gagal diunggah. Periksa ukuran dan konfigurasi PHP.'];
        if ((int)($f['size']??0)<1 || (int)$f['size']>5*1024*1024) return ['success'=>false,'error'=>'Ukuran bukti harus lebih dari 0 byte dan maksimal 5 MB.'];
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        if (!isset($allowed[$mime]) || @getimagesize($f['tmp_name'])===false) return ['success'=>false,'error'=>'Bukti harus berupa gambar JPEG, PNG, atau WEBP yang valid.'];
        $dir=FCPATH.'uploads'.DIRECTORY_SEPARATOR.'bayar'.DIRECTORY_SEPARATOR;
        if (!is_dir($dir) && !mkdir($dir,0755,true) && !is_dir($dir)) return ['success'=>false,'error'=>'Folder penyimpanan bukti tidak dapat dibuat.'];
        if (!is_writable($dir)) return ['success'=>false,'error'=>'Folder penyimpanan bukti tidak dapat ditulis.'];
        $filename='bukti_'.bin2hex(random_bytes(16)).'.'.$allowed[$mime];
        if (!move_uploaded_file($f['tmp_name'],$dir.$filename)) return ['success'=>false,'error'=>'Bukti transfer gagal disimpan.'];
        return ['success'=>true,'filename'=>$filename];
    }

    /** P10: catat pelunasan admin dengan PRG dan CSRF. */
    public function prosesPelunasan(string $kodeSewa=''): void
    {
        require_admin_login(); $this->method('POST'); $this->checkCsrf();
        $kodeSewa=trim(str_replace('~','/',$kodeSewa));
        $metode=trim((string)($_POST['mtode_byr']??''));
        $url='admin/pembayaran/pelunasan/'.str_replace('/','~',$kodeSewa);
        if ($kodeSewa==='') { http_response_code(400); exit('Kode transaksi sewa wajib tersedia.'); }
        if (!in_array($metode,['tunai','transfer'],true)) $this->pesan('error','Metode pembayaran harus tunai atau transfer.',$url);
        $filename=null;
        if ($metode==='transfer') {
            $upload=$this->simpanBuktiPelunasanAdmin();
            if (!$upload['success']) $this->pesan('error',$upload['error'],$url);
            $filename=(string)$upload['filename'];
        }
        try {
            $kodeBayar=$this->model->simpanPelunasan($kodeSewa,$metode,$filename);
            $this->pesan('success','Pelunasan berhasil dicatat dengan kode pembayaran '.$kodeBayar.'. Status pembayaran: valid; status transaksi: lunas.',$url);
        } catch (InvalidArgumentException $e) {
            if ($filename!==null) @unlink(FCPATH.'uploads'.DIRECTORY_SEPARATOR.'bayar'.DIRECTORY_SEPARATOR.$filename);
            $this->pesan('error',$e->getMessage(),$url);
        } catch (Throwable $e) {
            error_log('Proses pelunasan P10: '.$e->getMessage());
            if ($filename!==null) @unlink(FCPATH.'uploads'.DIRECTORY_SEPARATOR.'bayar'.DIRECTORY_SEPARATOR.$filename);
            $message=$e instanceof RuntimeException?$e->getMessage():'Pelunasan belum berhasil dicatat. Pastikan transaksi masih memiliki sisa kewajiban dan belum pernah dilunasi.';
            $this->pesan('error',$message,$url);
        }
    }

    /** P10: dokumen transaksi siap cetak. */
    public function dokumenTransaksi(string $kodeSewa=''): void
    {
        require_admin_login(); $this->method('GET');
        $kodeSewa=trim(str_replace('~','/',$kodeSewa));
        if ($kodeSewa==='') { http_response_code(400); exit('Kode transaksi sewa wajib tersedia.'); }
        try {
            $detail=$this->model->getDetailPelunasan($kodeSewa);
            if (!$detail) { http_response_code(404); exit('Data transaksi tidak ditemukan.'); }
            $this->view('admin/dokumen_transaksi',['detailPelunasan'=>$detail]);
        } catch (Throwable $e) {
            error_log('Dokumen transaksi P10: '.$e->getMessage());
            http_response_code(500); exit('Dokumen transaksi belum dapat ditampilkan. Silakan coba kembali.');
        }
    }

}
