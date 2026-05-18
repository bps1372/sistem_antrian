<?php
header('Content-Type: application/json');
include 'koneksi.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action == 'ambil_antrian') {
    // Dipanggil dari user.html
    $data = json_decode(file_get_contents('php://input'), true);
    $nama = $conn->real_escape_string($data['nama']);
    
    // Jika data kontak kosong dari frontend, berikan default tanda hubung agar lolos NOT NULL constraint database
    //$kontak = (!empty($data['kontak'])) ? $conn->real_escape_string($data['kontak']) : '-';
    
    $kode = $conn->real_escape_string($data['kode']);
    $nama_loket = $conn->real_escape_string($data['nama_loket']);
    $tanggal_hari_ini = date('Y-m-d');

    // Hitung antrian hari ini untuk loket tersebut (Otomatis reset tiap hari)
    $q_count = $conn->query("SELECT COUNT(*) as total FROM data_antrian WHERE tanggal = '$tanggal_hari_ini' AND kode_loket = '$kode'");
    $row = $q_count->fetch_assoc();
    $urutan = $row['total'] + 1;
    $nomor_antrian = $kode . '-' . str_pad($urutan, 3, '0', STR_PAD_LEFT);

    $sql = "INSERT INTO data_antrian (tanggal, nomor_antrian, nama, kode_loket, nama_loket) 
            VALUES ('$tanggal_hari_ini', '$nomor_antrian', '$nama', '$kode', '$nama_loket')";
    
    if($conn->query($sql)) {
        echo json_encode(["status" => "success", "nomor" => $nomor_antrian]);
    } else {
        echo json_encode(["status" => "error", "pesan" => $conn->error]);
    }
}

elseif ($action == 'get_admin_data') {
    // Dipanggil dari admin.html & panel.html
    $tanggal_hari_ini = date('Y-m-d');
    
    // Ambil semua antrian hari ini
    $q_antrian = $conn->query("SELECT id, nomor_antrian, nama, kode_loket, nama_loket, status FROM data_antrian WHERE tanggal = '$tanggal_hari_ini' ORDER BY id ASC");
    $daftar = [];
    $menunggu = ['A'=>0, 'B'=>0, 'C'=>0, 'P'=>0];
    $selesai = ['A'=>0, 'B'=>0, 'C'=>0, 'P'=>0];

    while($r = $q_antrian->fetch_assoc()) {
        $daftar[] = $r;
        if($r['status'] == 'Menunggu') {
            $menunggu[$r['kode_loket']]++;
        } else {
            $selesai[$r['kode_loket']]++;
        }
    }

    // Ambil status panel layar
    $q_layar = $conn->query("SELECT * FROM state_layar WHERE id = 1");
    $layar = $q_layar->fetch_assoc();

    echo json_encode([
        "daftar" => $daftar,
        "menunggu" => $menunggu,
        "selesai" => $selesai,
        "layar" => $layar
    ]);
}

elseif ($action == 'panggil_antrian') {
    // Dipanggil dari admin.html saat klik tombol panggil
    $data = json_decode(file_get_contents('php://input'), true);
    $kode = $conn->real_escape_string($data['kode']);
    $suara_loket = $conn->real_escape_string($data['suara_loket']);
    $id_layar = $conn->real_escape_string($data['id_layar']); // PST, PPID, Pengaduan
    $tanggal_hari_ini = date('Y-m-d');

    // Cari 1 orang pertama yang statusnya 'Menunggu' di loket tersebut hari ini
    $q_cari = $conn->query("SELECT id, nomor_antrian FROM data_antrian WHERE tanggal = '$tanggal_hari_ini' AND kode_loket = '$kode' AND status = 'Menunggu' ORDER BY id ASC LIMIT 1");
    
    if($q_cari->num_rows > 0) {
        $r = $q_cari->fetch_assoc();
        $id_antrian = $r['id'];
        $nomor = $r['nomor_antrian'];
        $waktu_sekarang = time();
        $waktu_db = date('Y-m-d H:i:s');

        // Update status orang tersebut jadi selesai
        $conn->query("UPDATE data_antrian SET status = 'Selesai', waktu_panggil = '$waktu_db' WHERE id = $id_antrian");

        // Update layar panel
        $update_layar = "UPDATE state_layar SET panggilan_nomor = '$nomor', panggilan_loket = '$suara_loket', waktu_update = $waktu_sekarang";
        // Tambahan dinamis untuk mengubah angka di kotak kecil layar
        if($id_layar == 'PST') { $update_layar .= ", layar_pst = '$nomor'"; }
        elseif($id_layar == 'PPID') { $update_layar .= ", layar_ppid = '$nomor'"; }
        elseif($id_layar == 'Pengaduan') { $update_layar .= ", layar_pengaduan = '$nomor'"; }
        
        $conn->query("$update_layar WHERE id = 1");

        echo json_encode(["status" => "success", "nomor" => $nomor]);
    } else {
        echo json_encode(["status" => "kosong", "pesan" => "Tidak ada antrian menunggu"]);
    }
}

elseif ($action == 'reset_layar') {
    // Dipanggil dari tombol Reset Admin (Hanya mereset layar/panel, BUKAN menghapus database)
    $conn->query("UPDATE state_layar SET layar_pst='---', layar_ppid='---', layar_pengaduan='---', panggilan_nomor='---', panggilan_loket='---' WHERE id=1");
    echo json_encode(["status" => "success"]);
}

elseif ($action == 'login_admin') {
    // Dipanggil dari admin1372.html saat login
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Mencegah SQL Injection
    $username = $conn->real_escape_string($data['username']);
    
    // Mengenkripsi password input dengan MD5 agar cocok dengan yang ada di database
    $password = md5($data['password']);

    // Cek apakah username dan password cocok di database
    $query = $conn->query("SELECT * FROM admin_users WHERE username = '$username' AND password = '$password'");

    if ($query->num_rows > 0) {
        // Login berhasil
        echo json_encode(["status" => "success"]);
    } else {
        // Login gagal
        echo json_encode(["status" => "error", "pesan" => "Username atau Password salah!"]);
    }
}
?>

