<?php
require_once 'session.php';
require_once 'database.php';
date_default_timezone_set('Asia/Jakarta');

checkAdminAccess();

$database = new Database();
$db = $database->getConnection();

// Fetch kelas for dropdown with nama_kelas
$queryKelas = "SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas";
$stmtKelas = $db->prepare($queryKelas);
$stmtKelas->execute();
$kelasList = $stmtKelas->fetchAll(PDO::FETCH_ASSOC);

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Check if nis already exists
                $checkQuery = "SELECT COUNT(*) FROM siswa WHERE nis = :nis";
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->bindParam(':nis', $_POST['nis']);
                $checkStmt->execute();
                if ($checkStmt->fetchColumn() > 0) {
                    $error = "NIS sudah terdaftar!";
                    break;
                }

                $query = "INSERT INTO siswa (nis, nama, id_kelas, telp_ortu) VALUES (:nis, :nama, :id_kelas, :telp_ortu)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nis', $_POST['nis']);
                $stmt->bindParam(':nama', $_POST['nama']);
                $stmt->bindParam(':id_kelas', $_POST['id_kelas']);
                $stmt->bindParam(':telp_ortu', $_POST['telp_ortu']);
            // Map selected nama_kelas to id_kelas before saving
            /*
            $selectedNamaKelas = $_POST['id_kelas'];
            $idKelas = null;
            foreach ($kelasList as $kelas) {
                if ($kelas['nama_kelas'] === $selectedNamaKelas) {
                    $idKelas = $kelas['id_kelas'];
                    break;
                }
            }
            if ($idKelas === null) {
                $error = "Kelas tidak valid!";
                break;
            }
            $stmt->bindParam(':id_kelas', $idKelas);
            */
            // Format telp_ortu to Indonesian regional format (+62)
            $telp_ortu = $_POST['telp_ortu'];
            $telp_ortu = preg_replace('/^0/', '62', $telp_ortu);
            $stmt->bindParam(':telp_ortu', $telp_ortu);
            // Directly bind posted id_kelas as it is now the id
            $stmt->bindParam(':id_kelas', $_POST['id_kelas']);

            if ($stmt->execute()) {
                // Create verification account
                // Disabled due to foreign key constraint with guru.nik
                /*
                $query_verify = "INSERT INTO verifikasi_absen (nik, password, status) VALUES (:nis, :password, 'aktif')";
                $stmt_verify = $db->prepare($query_verify);
                $stmt_verify->bindParam(':nis', $_POST['nis']);
                $default_password = '123456';
                $stmt_verify->bindParam(':password', $default_password);
                $stmt_verify->execute();
                */

                $success = "Data siswa berhasil ditambahkan!";
            } else {
                $error = "Gagal menambahkan data siswa!";
            }
            break;

        case 'edit':
                $query = "UPDATE siswa SET nis=:nis, nama=:nama, id_kelas=:id_kelas, telp_ortu=:telp_ortu WHERE nis=:old_nis";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nis', $_POST['nis']);
                $stmt->bindParam(':nama', $_POST['nama']);
                $stmt->bindParam(':id_kelas', $_POST['id_kelas']);
                // Format telp_ortu to Indonesian regional format (+62) on edit as well
                $telp_ortu = $_POST['telp_ortu'];
                $telp_ortu = preg_replace('/^0/', '62', $telp_ortu);
                $stmt->bindParam(':telp_ortu', $telp_ortu);
                $stmt->bindParam(':old_nis', $_POST['old_nis']);

            if ($stmt->execute()) {
                $success = "Data siswa berhasil diupdate!";
            } else {
                $error = "Gagal mengupdate data siswa!";
            }
            break;

            case 'delete':
                $query = "DELETE FROM siswa WHERE nis = :nis";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nis', $_POST['nis']);
                if ($stmt->execute()) {
                    $success = "Data siswa berhasil dihapus!";
                } else {
                    $error = "Gagal menghapus data siswa!";
                }
                break;
        }
    }
}

$id_kelas_filter = isset($_GET['id_kelas']) ? $_GET['id_kelas'] : null;

if ($id_kelas_filter) {
    $query = "SELECT s.*, k.nama_kelas FROM siswa s 
              LEFT JOIN kelas k ON s.id_kelas = k.id_kelas 
              WHERE s.id_kelas = :id_kelas 
              ORDER BY s.nama";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_kelas', $id_kelas_filter);
    $stmt->execute();
} else {
    $query = "SELECT s.*, k.nama_kelas FROM siswa s 
              LEFT JOIN kelas k ON s.id_kelas = k.id_kelas 
              ORDER BY s.nama";
    $stmt = $db->prepare($query);
    $stmt->execute();
}
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kelola Data Siswa - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/sidebar.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include 'admin_header.php'; ?>

<div class="container mt-4">
    <div class="row">
            <div class="col-md-3">
                <?php include 'admin_sidebar.php'; ?>
            </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Data Siswa</h5>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        Tambah Siswa
                    </button>
                </div>
                <div class="card-body">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <!-- Class filter buttons -->
                    <div class="mb-3 d-flex flex-wrap gap-2">
                        <button type="button"
                            class="btn btn-outline-secondary rounded-pill px-3 py-1 fw-bold filter-btn active"
                            onclick="filterByKelas('', this)">
                            <i class="fas fa-layer-group me-1"></i> Semua Kelas
                        </button>
                        <?php foreach ($kelasList as $kelas): ?>
                            <button type="button"
                                class="btn btn-outline-primary rounded-pill px-3 py-1 fw-bold filter-btn"
                                onclick="filterByKelas('<?= htmlspecialchars($kelas['nama_kelas']) ?>', this)">
                                <i class="fas fa-chalkboard me-1"></i> <?= htmlspecialchars($kelas['nama_kelas']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="table-responsive" id="tableContainer" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-striped" id="jadwalTable">
                            <thead>
                                <tr>
                                    <th>NIS</th>
                                    <th>Nama</th>
                                    <th>Kelas</th>
                                    <th>Nomor Telepon Orang Tua</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                <tr data-kelas="<?= htmlspecialchars($student['nama_kelas'] ?? ''); ?>">
                                    <td><?php echo htmlspecialchars($student['nis']); ?></td>
                                    <td><?php echo htmlspecialchars($student['nama']); ?></td>
                                    <td><?php echo htmlspecialchars($student['nama_kelas'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($student['telp_ortu']); ?></td>
                                    <td>
                                        <!-- Status removed as per user request -->
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick="editStudent('<?php echo $student['nis']; ?>')">
                                            Edit
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteStudent('<?php echo $student['nis']; ?>')">
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Data Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="studentForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add" id="formAction">
                    <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">NIS</label>
                <input type="hidden" name="old_nis" id="old_nis">
                <input type="text" class="form-control" name="nis" id="nis" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" name="nama" id="nama" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Kelas</label>
                                    <select class="form-control" name="id_kelas" id="id_kelas" required>
                                        <option value="">Pilih...</option>
                                        <?php foreach ($kelasList as $kelas): ?>
                                            <option value="<?= $kelas['id_kelas']; ?>" <?= $siswa['id_kelas'] == $kelas['id_kelas'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($kelas['nama_kelas']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nomor Telepon Orang Tua</label>
                                    <input type="text" class="form-control" name="telp_ortu" id="telp_ortu" required>
                                </div>
                            </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
        function editStudent(nis) {
            // Find student data by nis
            const students = <?php echo json_encode($students); ?>;
            const student = students.find(s => s.nis === nis);
            if (!student) return;

            // Set form values
            document.getElementById('formAction').value = 'edit';
            document.getElementById('old_nis').value = student.nis;
            document.getElementById('nis').value = student.nis;
            document.getElementById('nis').readOnly = true;
            document.getElementById('nama').value = student.nama;
            // Set id_kelas dropdown value by matching id_kelas
            const kelasOptions = document.getElementById('id_kelas').options;
            for (let i = 0; i < kelasOptions.length; i++) {
                if (kelasOptions[i].value === student.id_kelas) {
                    kelasOptions[i].selected = true;
                    break;
                }
            }
            document.getElementById('telp_ortu').value = student.telp_ortu;

            // Show modal
            var myModal = new bootstrap.Modal(document.getElementById('addStudentModal'));
            myModal.show();
        }

    function deleteStudent(nis) {
        if (confirm('Apakah Anda yakin ingin menghapus data siswa ini?')) {
            let form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="nis" value="${nis}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function filterByKelas(kelas, btn) {
        document.querySelectorAll('#jadwalTable tbody tr').forEach(function(row) {
            if (!kelas || row.getAttribute('data-kelas') === kelas) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        document.querySelectorAll('.filter-btn').forEach(function(b) {
            b.classList.remove('active');
            b.classList.remove('btn-primary');
            b.classList.add('btn-outline-primary');
        });
        if (btn) {
            btn.classList.add('active');
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-primary');
        }
    }

    // Infinite scrolling pagination for siswa table
    document.addEventListener('DOMContentLoaded', function() {
        const table = document.getElementById('jadwalTable');
        if (!table) return;

        const rowsPerPage = 10;
        let currentPage = 1;
        const rows = table.querySelectorAll('tbody tr');
        const totalRows = rows.length;

        function showRows() {
            const end = currentPage * rowsPerPage;
            rows.forEach((row, index) => {
                if (index < end) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function onScroll() {
            if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 100) {
                if (currentPage * rowsPerPage < totalRows) {
                    currentPage++;
                    showRows();
                }
            }
        }

        showRows();
        window.addEventListener('scroll', onScroll);
    });

    // Reset form when modal is hidden
    var addStudentModal = document.getElementById('addStudentModal');
    addStudentModal.addEventListener('hidden.bs.modal', function () {
        document.getElementById('studentForm').reset();
        document.getElementById('nis').readOnly = false;
        document.getElementById('formAction').value = 'add';
    });
</script>
</body>

</html>
