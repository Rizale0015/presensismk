<?php
require '../vendor/autoload.php'; // arahkan sesuai lokasi composer autoload

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

include '../database.php'; // koneksi ke DB kamu

// Ambil semua kelas
$kelasQuery = mysqli_query($conn, "SELECT * FROM kelas");
while ($kelas = mysqli_fetch_assoc($kelasQuery)) {
    $id_kelas = $kelas['id_kelas'];
    $nama_kelas = $kelas['nama_kelas'];

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle($nama_kelas);

    // Header kolom
    $sheet->setCellValue('A1', 'NIS');
    $sheet->setCellValue('B1', 'Nama');
    $sheet->setCellValue('C1', 'Tanggal');
    $sheet->setCellValue('D1', 'Jam Hadir');
    $sheet->setCellValue('E1', 'Keterangan');

    // Query data absensi per kelas
    $sql = "SELECT s.nis, s.nama, a.id_tanggal, a.jam_hadir, a.keterangan 
            FROM absen_siswa a
            JOIN siswa s ON a.nis = s.nis
            WHERE s.id_kelas = '$id_kelas'";
    $result = mysqli_query($conn, $sql);

    $row = 2;
    while ($data = mysqli_fetch_assoc($result)) {
        $sheet->setCellValue("A$row", $data['nis']);
        $sheet->setCellValue("B$row", $data['nama']);
        $sheet->setCellValue("C$row", $data['id_tanggal']);
        $sheet->setCellValue("D$row", $data['jam_hadir']);
        $sheet->setCellValue("E$row", $data['keterangan']);
        $row++;
    }

    // Simpan file Excel untuk kelas ini
    $writer = new Xlsx($spreadsheet);
    $filename = "Laporan_Absensi_$nama_kelas.xlsx";
    $writer->save($filename);
}

echo "Laporan berhasil diekspor per kelas.";
?>
