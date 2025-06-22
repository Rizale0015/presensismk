<?php
require '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
include 'database.php';

if (isset($_GET['kelas'])) {
    $kelas = $_GET['kelas'];

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Judul kolom
    $sheet->setCellValue('A1', 'NIS');
    $sheet->setCellValue('B1', 'Nama');
    $sheet->setCellValue('C1', 'Hadir');
    $sheet->setCellValue('D1', 'Izin');
    $sheet->setCellValue('E1', 'Sakit');
    $sheet->setCellValue('F1', 'Terlambat');

    $query = "
        SELECT s.nis, s.nama_siswa,
            SUM(CASE WHEN a.keterangan = 'Hadir' THEN 1 ELSE 0 END) AS hadir,
            SUM(CASE WHEN a.keterangan = 'Izin' THEN 1 ELSE 0 END) AS izin,
            SUM(CASE WHEN a.keterangan = 'Sakit' THEN 1 ELSE 0 END) AS sakit,
            SUM(CASE WHEN a.keterangan = 'Terlambat' THEN 1 ELSE 0 END) AS terlambat
        FROM siswa s
        LEFT JOIN absen_siswa a ON s.nis = a.nis
        WHERE s.kelas = '$kelas'
        GROUP BY s.nis
    ";

    $result = mysqli_query($conn, $query);
    $row = 2;

    while ($data = mysqli_fetch_assoc($result)) {
        $sheet->setCellValue("A$row", $data['nis']);
        $sheet->setCellValue("B$row", $data['nama_siswa']);
        $sheet->setCellValue("C$row", $data['hadir']);
        $sheet->setCellValue("D$row", $data['izin']);
        $sheet->setCellValue("E$row", $data['sakit']);
        $sheet->setCellValue("F$row", $data['terlambat']);
        $row++;
    }

    $filename = "Laporan_Absensi_$kelas.xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment;filename=\"$filename\"");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} else {
    echo "Kelas tidak ditemukan.";
}
