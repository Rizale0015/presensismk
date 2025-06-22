<?php
require_once 'session.php';
require_once 'database.php';
checkAdminAccess();

$database = new Database();
$db = $database->getConnection();

// Fetch guru for wali kelas dropdown
$queryGuru = "SELECT nik, namaptk FROM guru ORDER BY namaptk";
$stmtGuru = $db->prepare($queryGuru);
$stmtGuru->execute();
$guruList = $stmtGuru->fetchAll(PDO::FETCH_ASSOC);

// Handle form submissions
if ($_POST) {
    $errors = [];


    // Jika ada error, tampilkan pesan
    if ($errors) {
        foreach ($errors as $err) {
            echo '<div class="alert alert-danger">'.htmlspecialchars($err).'</div>';
        }
    } else {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    // Check if id_kelas already exists
                    $checkQuery = "SELECT COUNT(*) FROM kelas WHERE id_kelas = :id_kelas";
                    $checkStmt = $db->prepare($checkQuery);
                    $checkStmt->bindParam(':id_kelas', $_POST['id_kelas']);
                    $checkStmt->execute();
                    if ($checkStmt->fetchColumn() > 0) {
                        $error = "ID Kelas sudah terdaftar!";
                        break;
                    }

                    $query = "INSERT INTO kelas (id_kelas, nama_kelas, tingkat, nik_wali) VALUES (:id_kelas, :nama_kelas, :tingkat, :nik_wali)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id_kelas', $_POST['id_kelas']);
                    $stmt->bindParam(':nama_kelas', $_POST['nama_kelas']);
                    $stmt->bindParam(':tingkat', $_POST['tingkat']);
                    $stmt->bindParam(':nik_wali', $_POST['nik_wali']);
                    if ($stmt->execute()) {
                        header("Location: admin_kelas.php?feedback=success&msg=Data berhasil disimpan");
                        exit();
                    } else {
                        header("Location: admin_kelas.php?feedback=error&msg=Gagal menyimpan data");
                        exit();
                    }
                    break;

                case 'edit':
                    $query = "UPDATE kelas SET nama_kelas=:nama_kelas, tingkat=:tingkat, nik_wali=:nik_wali WHERE id_kelas=:id_kelas";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id_kelas', $_POST['id_kelas']);
                    $stmt->bindParam(':nama_kelas', $_POST['nama_kelas']);
                    $stmt->bindParam(':tingkat', $_POST['tingkat']);
                    $stmt->bindParam(':nik_wali', $_POST['nik_wali']);
                    if ($stmt->execute()) {
                        header("Location: admin_kelas.php?feedback=success&msg=Data berhasil disimpan");
                        exit();
                    } else {
                        header("Location: admin_kelas.php?feedback=error&msg=Gagal menyimpan data");
                        exit();
                    }
                    break;

                case 'delete':
                    $query = "DELETE FROM kelas WHERE id_kelas = :id_kelas";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id_kelas', $_POST['id_kelas']);
                    if ($stmt->execute()) {
                        header("Location: admin_kelas.php?feedback=success&msg=Data berhasil dihapus");
                        exit();
                    } else {
                        header("Location: admin_kelas.php?feedback=error&msg=Gagal menghapus data");
                        exit();
                    }
                    break;
            }
        }
    }
}

// Get all kelas with wali kelas name
$query = "SELECT k.*, g.namaptk AS nama_wali FROM kelas k LEFT JOIN guru g ON k.nik_wali = g.nik ORDER BY k.id_kelas";
$stmt = $db->prepare($query);
$stmt->execute();
$kelasList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kelola Data Kelas - Admin</title>
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
                    <h5>Data Kelas</h5>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addKelasModal">
                        Tambah Kelas
                    </button>
                </div>
                <div class="card-body">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID Kelas</th>
                                    <th>Nama Kelas</th>
                                    <th>Tingkat</th>
                                    <th>Wali Kelas</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kelasList as $kelas): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($kelas['id_kelas']); ?></td>
                                    <td><?php echo htmlspecialchars($kelas['nama_kelas']); ?></td>
                                    <td><?php echo htmlspecialchars($kelas['tingkat']); ?></td>
                                    <td><?php echo htmlspecialchars($kelas['nama_wali']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick="editKelas('<?php echo $kelas['id_kelas']; ?>')">
                                            Edit
                                        </button>
                                            <button class="btn btn-sm btn-danger" onclick="confirmDeleteKelas('<?php echo $kelas['id_kelas']; ?>')">
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

<!-- Add Kelas Modal -->
<div class="modal fade" id="addKelasModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Data Kelas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="admin_kelas.php" onsubmit="return validateForm();" id="kelasForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add" id="formAction">
                    <div class="mb-3">
                        <label class="form-label">ID Kelas</label>
                        <input type="text" id="id_kelas" name="id_kelas" class="form-control" required placeholder="ID Kelas">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Kelas</label>
                        <input type="text" id="nama_kelas" name="nama_kelas" class="form-control" required placeholder="Nama Kelas">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tingkat</label>
                        <input type="number" id="tingkat" name="tingkat" class="form-control" required min="1" max="12" placeholder="Tingkat">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Wali Kelas</label>
                        <select id="nik_wali" name="nik_wali" class="form-select" required>
                            <option value="">Pilih Wali Kelas</option>
                            <?php foreach ($guruList as $guru): ?>
                                <option value="<?php echo $guru['nik']; ?>"><?php echo htmlspecialchars($guru['namaptk']); ?></option>
                            <?php endforeach; ?>
                        </select>
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
    function editKelas(id_kelas) {
        const kelasList = <?php echo json_encode($kelasList); ?>;
        const kelas = kelasList.find(k => k.id_kelas == id_kelas);
        if (!kelas) return;

        document.getElementById('formAction').value = 'edit';
        document.getElementById('id_kelas').value = kelas.id_kelas;
        document.getElementById('id_kelas').readOnly = true;
        document.getElementById('nama_kelas').value = kelas.nama_kelas;
        document.getElementById('tingkat').value = kelas.tingkat;
        document.getElementById('nik_wali').value = kelas.nik_wali;

        var myModal = new bootstrap.Modal(document.getElementById('addKelasModal'));
        myModal.show();
    }

        function deleteKelas(id_kelas) {
            if (confirm('Apakah Anda yakin ingin menghapus data kelas ini?')) {
                let form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id_kelas" value="${id_kelas}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        let deleteIdKelas = null;
        function confirmDeleteKelas(id_kelas) {
            deleteIdKelas = id_kelas;
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteKelasModal'));
            deleteModal.show();
        }

        function performDeleteKelas() {
            if (!deleteIdKelas) return;
            let form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id_kelas" value="${deleteIdKelas}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

    var addKelasModal = document.getElementById('addKelasModal');
    addKelasModal.addEventListener('hidden.bs.modal', function () {
        document.getElementById('kelasForm').reset();
        document.getElementById('id_kelas').readOnly = false;
        document.getElementById('formAction').value = 'add';
    });

    function validateForm() {
        // Contoh validasi tambahan jika perlu
        // return false jika tidak valid
        return true;
    }

    <?php if (isset($_GET['feedback'])): ?>
    document.addEventListener('DOMContentLoaded', function() {
        showFeedbackModal(
            '<?= $_GET['feedback'] === 'success' ? 'success' : 'error' ?>',
            "<?= htmlspecialchars($_GET['msg']) ?>"
        );
    });
    <?php endif; ?>
</script>
<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteKelasModal" tabindex="-1" aria-labelledby="deleteKelasModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteKelasModalLabel">Konfirmasi Hapus Data Kelas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Apakah Anda yakin ingin menghapus data kelas ini?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-danger" onclick="performDeleteKelas()">Hapus</button>
      </div>
    </div>
  </div>
</div>

</body>
</html>
