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
}
