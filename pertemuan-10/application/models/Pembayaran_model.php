<?php
/** Model pembayaran P9. Seluruh parameter dinamis memakai prepared statement. */
class Pembayaran_model
{
    private mysqli $db;
    public function __construct() { global $db; $this->db = $db; }

    /** Riwayat pembayaran DP milik satu akun penyewa untuk menu navigasi. */
    public function daftarMilikPenyewa(string $userPlg): array
    {
        $sql = "SELECT b.kode_bayar,b.kode_sewa,b.nominal,b.mtode_byr,b.sttus_byr,b.ft_byr,b.tgl_wkt_byr,b.tgl_wkt_ver,
                       s.status_sewa,s.tgl_mulai,s.tgl_selesai
                FROM t_bayar b INNER JOIN t_sewa s ON s.kode_sewa=b.kode_sewa
                WHERE s.user_plg=? AND b.jns_byr='dp'
                ORDER BY b.tgl_wkt_byr DESC, b.kode_bayar DESC";
        $st = $this->db->prepare($sql);
        if (!$st) throw new RuntimeException('Riwayat pembayaran gagal disiapkan.');
        $st->bind_param('s', $userPlg);
        if (!$st->execute()) { $st->close(); throw new RuntimeException('Riwayat pembayaran gagal diambil.'); }
        $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
        return $rows;
    }

    public function getMilikPenyewa(string $kodeSewa, string $userPlg): ?array
    {
        $sql = "SELECT b.kode_bayar,b.tgl_wkt_byr,b.ft_byr,b.mtode_byr,b.jns_byr,b.nominal,b.sttus_byr,b.tgl_wkt_ver,b.kode_sewa,
                       s.status_sewa,s.tgl_mulai,s.tgl_selesai,s.wkt_ambil,s.user_plg,p.nm_lngkp
                FROM t_bayar b INNER JOIN t_sewa s ON s.kode_sewa=b.kode_sewa
                INNER JOIN t_penyewa p ON p.user_plg=s.user_plg
                WHERE b.kode_sewa=? AND s.user_plg=? AND b.jns_byr='dp' LIMIT 1";
        $st=$this->db->prepare($sql); if(!$st) throw new RuntimeException('Query pembayaran gagal disiapkan.');
        $st->bind_param('ss',$kodeSewa,$userPlg); if(!$st->execute()){ $st->close(); throw new RuntimeException('Data pembayaran gagal diambil.'); }
        $row=$st->get_result()->fetch_assoc(); $st->close(); return $row ?: null;
    }

    public function getByKode(string $kodeBayar): ?array
    {
        $sql="SELECT b.*,s.user_plg,s.status_sewa,p.nm_lngkp,p.no_hp
              FROM t_bayar b INNER JOIN t_sewa s ON s.kode_sewa=b.kode_sewa
              INNER JOIN t_penyewa p ON p.user_plg=s.user_plg WHERE b.kode_bayar=? LIMIT 1";
        $st=$this->db->prepare($sql); if(!$st) throw new RuntimeException('Query detail pembayaran gagal disiapkan.');
        $st->bind_param('s',$kodeBayar); if(!$st->execute()){ $st->close(); throw new RuntimeException('Detail pembayaran gagal diambil.'); }
        $row=$st->get_result()->fetch_assoc(); $st->close(); return $row ?: null;
    }

    /** Seluruh riwayat DP untuk tab Semua, Menunggu, Valid, dan Ditolak. */
    public function daftarSemua(): array
    {
        $sql="SELECT b.kode_bayar,b.tgl_wkt_byr,b.ft_byr,b.mtode_byr,b.jns_byr,b.nominal,b.sttus_byr,b.tgl_wkt_ver,b.kode_sewa,
                     s.status_sewa,s.user_plg,p.nm_lngkp,p.no_hp
              FROM t_bayar b INNER JOIN t_sewa s ON s.kode_sewa=b.kode_sewa
              INNER JOIN t_penyewa p ON p.user_plg=s.user_plg
              WHERE b.jns_byr='dp'
              ORDER BY CASE b.sttus_byr WHEN 'pending' THEN 0 WHEN 'valid' THEN 1 WHEN 'invalid' THEN 2 ELSE 3 END,
                       b.tgl_wkt_byr DESC";
        $res=$this->db->query($sql); if(!$res) throw new RuntimeException('Daftar pembayaran gagal diambil.');
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    /** Kompatibilitas untuk pemanggil lama. */
    public function daftarPending(): array
    {
        return array_values(array_filter($this->daftarSemua(), static fn($r) => ($r['sttus_byr'] ?? '') === 'pending'));
    }

    public function simpanBukti(string $kodeBayar,string $filename): array
    {
        $this->db->begin_transaction();
        try {
            $st=$this->db->prepare("SELECT ft_byr FROM t_bayar WHERE kode_bayar=? AND jns_byr='dp' AND sttus_byr='pending' FOR UPDATE");
            if(!$st) throw new RuntimeException('Pemeriksaan pembayaran gagal disiapkan.');
            $st->bind_param('s',$kodeBayar);
            if(!$st->execute()) throw new RuntimeException('Pemeriksaan pembayaran gagal dijalankan.');
            $row=$st->get_result()->fetch_assoc(); $st->close();
            if(!$row) { $this->db->commit(); return ['ok'=>false,'old'=>null]; }
            $old=$row['ft_byr'];
            $up=$this->db->prepare("UPDATE t_bayar SET ft_byr=?,tgl_wkt_byr=NOW() WHERE kode_bayar=? AND jns_byr='dp' AND sttus_byr='pending'");
            if(!$up) throw new RuntimeException('Penyimpanan bukti gagal disiapkan.');
            $up->bind_param('ss',$filename,$kodeBayar);
            if(!$up->execute()) throw new RuntimeException('Penyimpanan bukti gagal dijalankan.');
            $ok=$up->affected_rows===1; $up->close();
            $this->db->commit();
            return ['ok'=>$ok,'old'=>$old];
        } catch(Throwable $e) { $this->db->rollback(); throw $e; }
    }

    public function verifikasi(string $kodeBayar,string $keputusan): bool
    {
        if(!in_array($keputusan,['terima','tolak'],true)) throw new InvalidArgumentException('Keputusan verifikasi tidak valid.');
        $statusBayar=$keputusan==='terima'?'valid':'invalid';
        $statusSewa=$keputusan==='terima'?'disetujui':'ditolak';
        $this->db->begin_transaction();
        try {
            $st=$this->db->prepare("SELECT b.kode_sewa,b.ft_byr,b.sttus_byr,s.status_sewa FROM t_bayar b INNER JOIN t_sewa s ON s.kode_sewa=b.kode_sewa WHERE b.kode_bayar=? AND b.jns_byr='dp' FOR UPDATE");
            if(!$st) throw new RuntimeException('Penguncian pembayaran gagal disiapkan.');
            $st->bind_param('s',$kodeBayar); if(!$st->execute()) throw new RuntimeException('Pembayaran gagal diperiksa.');
            $row=$st->get_result()->fetch_assoc(); $st->close();
            if(!$row || $row['sttus_byr']!=='pending' || empty($row['ft_byr'])) throw new RuntimeException('Pembayaran tidak ditemukan, belum memiliki bukti, atau sudah diverifikasi.');
            $kodeSewa=(string)$row['kode_sewa'];
            $up=$this->db->prepare("UPDATE t_bayar SET sttus_byr=?,tgl_wkt_ver=NOW() WHERE kode_bayar=? AND sttus_byr='pending' AND ft_byr IS NOT NULL");
            if(!$up) throw new RuntimeException('Update status pembayaran gagal disiapkan.');
            $up->bind_param('ss',$statusBayar,$kodeBayar); if(!$up->execute() || $up->affected_rows!==1) throw new RuntimeException('Status pembayaran tidak berhasil diperbarui.'); $up->close();
            $us=$this->db->prepare("UPDATE t_sewa SET status_sewa=? WHERE kode_sewa=? AND status_sewa='diajukan'");
            if(!$us) throw new RuntimeException('Update status transaksi gagal disiapkan.');
            $us->bind_param('ss',$statusSewa,$kodeSewa); if(!$us->execute() || $us->affected_rows!==1) throw new RuntimeException('Status transaksi tidak sesuai atau gagal diperbarui.'); $us->close();
            $this->db->commit(); return true;
        } catch(Throwable $e) { $this->db->rollback(); throw $e; }
    }


    /** P10: Daftar transaksi yang masih dapat dilunasi admin. */
    public function getDaftarPelunasan(): array
    {
        $sql = "SELECT s.kode_sewa, s.tgl_mulai, s.tgl_selesai, s.status_sewa,
                       p.user_plg, p.nm_lngkp, p.no_hp,
                       COALESCE((SELECT SUM(tp.hrg_sewa * tp.n_hari)
                                 FROM t_pilih tp WHERE tp.kode_sewa=s.kode_sewa),0) AS total_transaksi,
                       COALESCE((SELECT SUM(b.nominal) FROM t_bayar b
                                 WHERE b.kode_sewa=s.kode_sewa AND b.sttus_byr='valid'),0) AS total_terbayar
                FROM t_sewa s INNER JOIN t_penyewa p ON p.user_plg=s.user_plg
                WHERE s.status_sewa IN ('disetujui','lunas')
                  AND EXISTS (SELECT 1 FROM t_pilih tp WHERE tp.kode_sewa=s.kode_sewa)
                ORDER BY s.tgl_mulai DESC, s.kode_sewa DESC";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new RuntimeException('Query daftar pelunasan gagal disiapkan.');
        if (!$stmt->execute()) { $stmt->close(); throw new RuntimeException('Daftar pelunasan gagal diambil.'); }
        $daftar = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        foreach ($daftar as &$item) {
            $item['total_transaksi'] = (int)$item['total_transaksi'];
            $item['total_terbayar'] = (int)$item['total_terbayar'];
            $item['sisa_kewajiban'] = max(0, $item['total_transaksi'] - $item['total_terbayar']);
        }
        unset($item);
        return array_values(array_filter($daftar, static fn(array $item): bool =>
            $item['sisa_kewajiban'] > 0 || $item['status_sewa'] === 'lunas'));
    }

    /** P10: Detail penyewa, transaksi, pembayaran dan kendaraan. */
    public function getDetailPelunasan(string $kodeSewa): ?array
    {
        $kodeSewa = trim($kodeSewa);
        if ($kodeSewa === '') throw new InvalidArgumentException('Kode transaksi sewa wajib diisi.');
        $sql = "SELECT s.kode_sewa,s.tgl_mulai,s.tgl_selesai,s.wkt_ambil,s.status_sewa,
                       p.user_plg,p.nm_lngkp,p.no_hp,p.nik,p.almt_lngkp,
                       COALESCE((SELECT SUM(tp.hrg_sewa*tp.n_hari) FROM t_pilih tp WHERE tp.kode_sewa=s.kode_sewa),0) AS total_transaksi,
                       COALESCE((SELECT SUM(b.nominal) FROM t_bayar b WHERE b.kode_sewa=s.kode_sewa AND b.sttus_byr='valid'),0) AS total_terbayar
                FROM t_sewa s INNER JOIN t_penyewa p ON p.user_plg=s.user_plg
                WHERE s.kode_sewa=? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new RuntimeException('Query detail pelunasan gagal disiapkan.');
        $stmt->bind_param('s', $kodeSewa);
        if (!$stmt->execute()) { $stmt->close(); throw new RuntimeException('Detail transaksi pelunasan gagal diambil.'); }
        $detail = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$detail) return null;
        $detail['total_transaksi'] = (int)$detail['total_transaksi'];
        $detail['total_terbayar'] = (int)$detail['total_terbayar'];
        $detail['sisa_kewajiban'] = max(0, $detail['total_transaksi'] - $detail['total_terbayar']);

        $stmt = $this->db->prepare("SELECT kode_bayar,tgl_wkt_byr,mtode_byr,jns_byr,nominal,sttus_byr,ft_byr,tgl_wkt_ver
                                    FROM t_bayar WHERE kode_sewa=? ORDER BY tgl_wkt_byr ASC,kode_bayar ASC");
        if (!$stmt) throw new RuntimeException('Query riwayat pembayaran gagal disiapkan.');
        $stmt->bind_param('s', $kodeSewa);
        if (!$stmt->execute()) { $stmt->close(); throw new RuntimeException('Riwayat pembayaran gagal diambil.'); }
        $detail['riwayat_pembayaran'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $stmt = $this->db->prepare("SELECT m.no_plat,m.nm_mobil,tp.hrg_sewa,tp.n_hari,(tp.hrg_sewa*tp.n_hari) AS subtotal
                                    FROM t_pilih tp INNER JOIN t_mobil m ON m.no_plat=tp.no_plat
                                    WHERE tp.kode_sewa=? ORDER BY m.nm_mobil ASC,m.no_plat ASC");
        if (!$stmt) throw new RuntimeException('Query kendaraan pelunasan gagal disiapkan.');
        $stmt->bind_param('s', $kodeSewa);
        if (!$stmt->execute()) { $stmt->close(); throw new RuntimeException('Rincian kendaraan pelunasan gagal diambil.'); }
        $detail['daftar_kendaraan'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $detail;
    }

    /** P10: Nomor PAY/YYYYMMDD/NN. Panggil saat named lock sudah dimiliki. */
    private function buatKodeBayarPelunasan(string $tanggalKode): string
    {
        $pola = 'PAY/' . $tanggalKode . '/%';
        $stmt = $this->db->prepare("SELECT kode_bayar FROM t_bayar WHERE kode_bayar LIKE ? ORDER BY kode_bayar DESC LIMIT 1");
        if (!$stmt) throw new RuntimeException('Pembuatan kode pembayaran gagal disiapkan.');
        $stmt->bind_param('s', $pola);
        if (!$stmt->execute()) { $stmt->close(); throw new RuntimeException('Pembuatan kode pembayaran gagal dijalankan.'); }
        $terakhir = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $nomor = 1;
        if ($terakhir) { $bagian = explode('/', (string)$terakhir['kode_bayar']); $nomor = ((int)end($bagian)) + 1; }
        if ($nomor > 99) throw new RuntimeException('Nomor pembayaran harian telah mencapai batas 99.');
        return sprintf('PAY/%s/%02d', $tanggalKode, $nomor);
    }

    /** P10: Catat pelunasan penuh, valid, dan ubah status sewa secara atomik. */
    public function simpanPelunasan(string $kodeSewa, string $metodeBayar, ?string $namaFileBukti = null): string
    {
        $kodeSewa = trim($kodeSewa); $metodeBayar = trim($metodeBayar);
        if ($kodeSewa === '') throw new InvalidArgumentException('Kode transaksi sewa wajib diisi.');
        if (!in_array($metodeBayar, ['tunai','transfer'], true)) throw new InvalidArgumentException('Metode pembayaran tidak valid.');
        if ($metodeBayar === 'transfer') {
            $namaFileBukti = trim((string)$namaFileBukti);
            if ($namaFileBukti === '' || !preg_match('/^bukti_[a-f0-9]{32}\.(jpg|png|webp)$/', $namaFileBukti))
                throw new InvalidArgumentException('Bukti transfer wajib tersedia.');
        } else { $namaFileBukti = null; }

        $namaKunci = 'dpwl_payment_code_sequence'; $lock = false; $started = false;
        try {
            $stmt = $this->db->prepare('SELECT GET_LOCK(?, 10) AS lock_status');
            if (!$stmt) throw new RuntimeException('Kunci pembuatan kode pembayaran gagal disiapkan.');
            $stmt->bind_param('s', $namaKunci);
            if (!$stmt->execute()) { $stmt->close(); throw new RuntimeException('Kunci pembuatan kode pembayaran gagal dijalankan.'); }
            $hasil = $stmt->get_result()->fetch_assoc(); $stmt->close();
            if ((int)($hasil['lock_status'] ?? 0) !== 1) throw new RuntimeException('Proses pembayaran sedang digunakan. Silakan coba kembali.');
            $lock = true; $this->db->begin_transaction(); $started = true;

            $sql = "SELECT s.kode_sewa,s.status_sewa,
                           COALESCE((SELECT SUM(tp.hrg_sewa*tp.n_hari) FROM t_pilih tp WHERE tp.kode_sewa=s.kode_sewa),0) AS total_transaksi,
                           COALESCE((SELECT SUM(b.nominal) FROM t_bayar b WHERE b.kode_sewa=s.kode_sewa AND b.sttus_byr='valid'),0) AS total_terbayar
                    FROM t_sewa s WHERE s.kode_sewa=? LIMIT 1 FOR UPDATE";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) throw new RuntimeException('Pemeriksaan transaksi pelunasan gagal disiapkan.');
            $stmt->bind_param('s', $kodeSewa);
            if (!$stmt->execute()) { $stmt->close(); throw new RuntimeException('Pemeriksaan transaksi pelunasan gagal dijalankan.'); }
            $transaksi = $stmt->get_result()->fetch_assoc(); $stmt->close();
            if (!$transaksi) throw new RuntimeException('Transaksi sewa tidak ditemukan.');
            if (!in_array($transaksi['status_sewa'], ['disetujui','lunas'], true)) throw new RuntimeException('Status transaksi belum memenuhi syarat pelunasan.');
            $total = (int)$transaksi['total_transaksi']; $paid = (int)$transaksi['total_terbayar']; $sisa = $total - $paid;
            if ($total <= 0 || $sisa <= 0) throw new RuntimeException('Transaksi tidak memiliki sisa kewajiban untuk dilunasi.');
            if ($sisa > 8388607) throw new RuntimeException('Sisa kewajiban melebihi kapasitas kolom nominal pada database.');

            $stmt = $this->db->prepare("SELECT kode_bayar FROM t_bayar WHERE kode_sewa=? AND jns_byr='pelunasan' LIMIT 1 FOR UPDATE");
            if (!$stmt) throw new RuntimeException('Pemeriksaan pelunasan sebelumnya gagal disiapkan.');
            $stmt->bind_param('s', $kodeSewa);
            if (!$stmt->execute()) { $stmt->close(); throw new RuntimeException('Pemeriksaan pelunasan sebelumnya gagal dijalankan.'); }
            $exists = $stmt->get_result()->fetch_assoc() !== null; $stmt->close();
            if ($exists) throw new RuntimeException('Transaksi ini sudah memiliki record pelunasan.');

            $res = $this->db->query("SELECT DATE_FORMAT(NOW(), '%Y%m%d') AS tanggal_kode,NOW() AS waktu_pembayaran");
            if (!$res) throw new RuntimeException('Waktu pembayaran gagal dibaca.');
            $waktu = $res->fetch_assoc(); $res->free();
            $tanggal = (string)($waktu['tanggal_kode'] ?? ''); $waktuBayar = (string)($waktu['waktu_pembayaran'] ?? '');
            if (!preg_match('/^\d{8}$/', $tanggal) || $waktuBayar === '') throw new RuntimeException('Waktu pembayaran tidak valid.');
            $kodeBayar = $this->buatKodeBayarPelunasan($tanggal);
            $stmt = $this->db->prepare("INSERT INTO t_bayar (kode_bayar,tgl_wkt_byr,ft_byr,mtode_byr,jns_byr,nominal,sttus_byr,tgl_wkt_ver,kode_sewa)
                                        VALUES (?,?,?,?,'pelunasan',?,'valid',NOW(),?)");
            if (!$stmt) throw new RuntimeException('Pencatatan pelunasan gagal disiapkan.');
            $stmt->bind_param('ssssis', $kodeBayar, $waktuBayar, $namaFileBukti, $metodeBayar, $sisa, $kodeSewa);
            if (!$stmt->execute()) { $stmt->close(); throw new RuntimeException('Pelunasan gagal disimpan.'); }
            $stmt->close();
            $stmt = $this->db->prepare("UPDATE t_sewa SET status_sewa='lunas' WHERE kode_sewa=? AND status_sewa IN ('disetujui','lunas')");
            if (!$stmt) throw new RuntimeException('Pembaruan status transaksi menjadi lunas gagal disiapkan.');
            $stmt->bind_param('s', $kodeSewa);
            if (!$stmt->execute() || $stmt->affected_rows !== 1) { $stmt->close(); throw new RuntimeException('Status transaksi gagal diperbarui menjadi lunas.'); }
            $stmt->close(); $this->db->commit(); $started = false;
            return $kodeBayar;
        } catch (Throwable $e) {
            if ($started) $this->db->rollback();
            throw $e;
        } finally {
            if ($lock) { $stmt = $this->db->prepare('SELECT RELEASE_LOCK(?)'); if ($stmt) { $stmt->bind_param('s', $namaKunci); $stmt->execute(); $stmt->close(); } }
        }
    }

}
