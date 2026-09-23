# pertemuan-01

soal1. kesinambungan PWD–DPW–DPWL;
soal2. perbedaan PHP terstruktur dan MVC;
soal3. fungsi Model, View, dan Controller;
soal4. alur request–response MVC;
soal5. pemetaan satu atau beberapa bagian/fitur aplikasi DPW ke Model, Controller, dan View disertai alasan; dan
soal6. kesimpulan P1.

Jawaban :

1. Bedanya PWD, DPW, dan DPWL

-PWD (Pemrograman Web Dasar): Belajar pondasi paling awal, yaitu bikin tampilan web statis pakai HTML, ngerapiin pake CSS, dan nambahin interaksi kecil pake JavaScript.

-DPW (Desain dan Pemrograman Web): Mulai masuk ke backend pakai PHP native. Di sini kita belajar bikin web dinamis yang udah connect ke database MySQL (CRUD standar).

-DPWL (Desain & Pemrograman Web Lanjutan): Tahap lanjutannya. Kita nggak pakai PHP acak-acakan lagi, tapi udah pakai pola MVC dan framework modern biar kodenya rapi dan siap buat standar industri.

2. Perbedaan PHP Terstruktur (Native) vs MVC

-PHP Terstruktur: Kodenya dicampur aduk dalam satu file. Bikin HTML, kueri database, sama logika PHP numpuk jadi satu. Kalau kodingannya udah banyak, biasanya pusing sendiri nyarinya (istilahnya spaghetti code).

-MVC: Kodenya dipisah-pisah sesuai tugasnya masing-masing. Lebih rapi, gampang diperbaiki kalau ada error, dan bikin kerja tim jadi jauh lebih gampang.

3. Tugas Model, View, dan Controller (Gampangnya)

-Model: Bagian yang ngurusin data dan komunikasi langsung ke database (query SQL).

-View: Bagian yang ngurusin tampilan layar yang dilihat pengguna (HTML/CSS).

-Controller: "Otak" atau perantaranya. Dia yang nerima permintaan user, ngambil data dari Model, terus ngirim datanya ke View buat ditampilkan.

4. Alur Kerja MVC (Request–Response)
Gampangnya kayak pesan makanan di restoran:

-User (Pelanggan) ngeklik link/tombol di browser.

-Controller (Pelayan) nerima pesanan tersebut.

-Controller minta data ke Model (Koki).

-Model ngambil bahan di Database (Kulkas), lalu kasih datanya ke Controller.

-Controller ngirim data itu ke View (Piring Tampilan).

-View nampilain hasil akhirnya ke layar User.

5. Contoh Penerapan Fitur "Data Mahasiswa" ke MVC

-Model: Isinya cuma fungsi buat ambil atau simpan data ke database. Contoh: get_semua_mahasiswa().

-Controller: Tempat ngecek data (misal: "apakah NIM udah diisi?"). Kalau aman, dia suruh Model buat simpan, terus lempar tampilannya ke View.

-View: Cuma file berisi tabel HTML atau form input data. Nggak ada kueri database sama sekali di sini.

-Alasannya dipisah: Biar kalau mau ubah tampilan desain, kita cukup ngutak-ngatik View tanpa takut merusak kueri database yang ada di Model.

6. Kesimpulan P1
Di mata kuliah DPWL ini, kita geser cara pikir dari yang tadinya cuma sekadar "yang penting web-nya jalan" (pakai PHP terstruktur) jadi "bikin web yang rapi, terstruktur, dan gampang dikembangkan" pakai konsep MVC. Ini modal utama sebelum kita masuk ke framework PHP modern.