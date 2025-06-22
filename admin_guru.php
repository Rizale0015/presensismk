<?php
require_once 'session.php';
require_once 'database.php';
checkAdminAccess();

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Cek NIP sudah ada atau belum
                $stmt = $db->prepare("SELECT COUNT(*) FROM guru WHERE nik = :nik");
                $stmt->bindParam(':nik', $_POST['nik']);
                $stmt->execute();
                if ($stmt->fetchColumn() > 0) {
                    $error = "NIP sudah terdaftar!";
                    break;
                }

                // Ubah ke format ddmmyyyy
                $password_plain = date('dmY', strtotime($_POST['tgl_lahir']));
                // Hash password sebelum simpan ke database
                $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

                $query = "INSERT INTO guru (nik, namaptk, jenis_kelamin, tgl_lahir, alamat, email, hp) 
                         VALUES (:nik, :namaptk, :jenis_kelamin, :tgl_lahir, :alamat, :email, :hp)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nik', $_POST['nik']);
                $stmt->bindParam(':namaptk', $_POST['namaptk']);
                $stmt->bindParam(':jenis_kelamin', $_POST['jenis_kelamin']);
                $stmt->bindParam(':tgl_lahir', $_POST['tgl_lahir']);
                $stmt->bindParam(':alamat', $_POST['alamat']);
                $stmt->bindParam(':email', $_POST['email']);
                $stmt->bindParam(':hp', $_POST['hp']);
                
                if ($stmt->execute()) {
                    // Create verification account
                    $query_verify = "INSERT INTO verifikasi_absen (nik, password, status) VALUES (:nik, :password, 'aktif')";
                    $stmt_verify = $db->prepare($query_verify);
                    $stmt_verify->bindParam(':nik', $_POST['nik']);
                    // Format tgl_lahir as ddmmyy for default password
                    $tgl_lahir = $_POST['tgl_lahir']; // expected format: YYYY-MM-DD
                    $date = DateTime::createFromFormat('Y-m-d', $tgl_lahir);
                    if ($date) {
                        $default_password = $date->format('dmy');
                    } else {
                        $default_password = '123456'; // fallback
                    }
                    $stmt_verify->bindParam(':password', $default_password);
                    $stmt_verify->execute();
                    
                    // Setelah aksi sukses
                    header("Location: admin_guru.php?feedback=success&msg=Data berhasil disimpan");
                    exit();
                } else {
                    // Setelah aksi gagal
                    header("Location: admin_guru.php?feedback=error&msg=Gagal menyimpan data");
                    exit();
                }
                break;
                
            case 'edit':
                $query = "UPDATE guru SET namaptk=:namaptk, jenis_kelamin=:jenis_kelamin,  
                         tgl_lahir=:tgl_lahir, agama=:agama, alamat=:alamat, 
                         email=:email, hp=:hp WHERE nik=:nik";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nik', $_POST['nik']);
                $stmt->bindParam(':namaptk', $_POST['namaptk']);
                $stmt->bindParam(':jenis_kelamin', $_POST['jenis_kelamin']);
                $stmt->bindParam(':tgl_lahir', $_POST['tgl_lahir']);
                $stmt->bindParam(':alamat', $_POST['alamat']);
                $stmt->bindParam(':email', $_POST['email']);
                $stmt->bindParam(':hp', $_POST['hp']);
                if ($stmt->execute()) {
                    $success = "Data guru berhasil diupdate!";
                } else {
                    $error = "Gagal mengupdate data guru!";
                }
                break;
                
            case 'delete':
                // Check if guru has related attendance records, verification records, or teaching schedule
                $checkScheduleQuery = "SELECT COUNT(*) FROM jadwal_pelajaran WHERE nik = :nik";
                $checkScheduleStmt = $db->prepare($checkScheduleQuery);
                $checkScheduleStmt->bindParam(':nik', $_POST['nik']);
                $checkScheduleStmt->execute();
                if ($checkScheduleStmt->fetchColumn() > 0) {
                    $error = "Tidak dapat menghapus guru karena terdapat jadwal mengajar terkait.";
                    break;
                }

                $nik = $_POST['nik'];
                // Hapus absensi guru
                $stmt = $db->prepare("DELETE FROM absen_guru WHERE nik = :nik");
                $stmt->bindParam(':nik', $nik);
                $stmt->execute();

                // Hapus verifikasi absen
                $stmt = $db->prepare("DELETE FROM verifikasi_absen WHERE nik = :nik");
                $stmt->bindParam(':nik', $nik);
                $stmt->execute();

                // Hapus jadwal mengajar (jika ada)
                $stmt = $db->prepare("DELETE FROM jadwal_pelajaran WHERE nik = :nik");
                $stmt->bindParam(':nik', $nik);
                $stmt->execute();

                // Baru hapus guru
                $stmt = $db->prepare("DELETE FROM guru WHERE nik = :nik");
                $stmt->bindParam(':nik', $nik);
                if ($stmt->execute()) {
                    $success = "Data guru berhasil dihapus!";
                } else {
                    $error = "Gagal menghapus data guru!";
                }
                break;
        }
    }

    if (isset($_POST['tambah_guru'])) {
        $nik = $_POST['nik'];
        $namaptk = $_POST['namaptk'];
        $tanggal_lahir = $_POST['tanggal_lahir'];
        
        // Generate plain password from birth date (format: ddmmyyyy)
        $password_plain = date('dmY', strtotime($tanggal_lahir));

        try {
            $db->beginTransaction();

            // Insert into guru table
            $stmt = $db->prepare("INSERT INTO guru (nik, namaptk, tanggal_lahir) VALUES (:nik, :namaptk, :tanggal_lahir)");
            $stmt->bindParam(':nik', $nik);
            $stmt->bindParam(':namaptk', $namaptk);
            $stmt->bindParam(':tanggal_lahir', $tanggal_lahir);
            $stmt->execute();

            // Insert into verifikasi_absen with plain password
            $stmt = $db->prepare("INSERT INTO verifikasi_absen (nik, password, status) VALUES (:nik, :password, 'aktif')");
            $stmt->bindParam(':nik', $nik);
            $stmt->bindParam(':password', $password_plain); // Store plain password
            $stmt->execute();

            $db->commit();
            $success = "Data guru berhasil ditambahkan! Password login adalah tanggal lahir (format: ddmmyyyy)";
            
        } catch(PDOException $e) {
            $db->rollBack();
            if ($e->getCode() == 23000) {
                $error = "NIK sudah terdaftar!";
            } else {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

// Get all teachers
$query = "SELECT g.*, v.status FROM guru g LEFT JOIN verifikasi_absen v ON g.nik = v.nik ORDER BY g.namaptk";
$stmt = $db->prepare($query);
$stmt->execute();
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Data Guru - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
                        <h5>Data Guru</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                            Tambah Guru
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
                                        <th>NIK</th>
                                        <th>Nama</th>
                                        <th>Jenis Kelamin</th>
                                        <th>Email</th>
                                        <th>HP</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teachers as $teacher): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($teacher['nik']); ?></td>
                                        <td><?php echo htmlspecialchars($teacher['namaptk']); ?></td>
                                        <td><?php echo htmlspecialchars($teacher['jenis_kelamin']); ?></td>
                                        <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                        <td><?php echo htmlspecialchars($teacher['hp']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $teacher['status'] == 'aktif' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($teacher['status'] ?? 'Tidak Aktif'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewTeacherModal" onclick="viewTeacher('<?php echo htmlspecialchars($teacher['nik']); ?>')">
                                                View
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="editTeacher('<?php echo $teacher['nik']; ?>')">
                                                Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="confirmDeleteTeacher('<?php echo $teacher['nik']; ?>')">
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

    <!-- View Teacher Modal -->
    <div class="modal fade" id="viewTeacherModal" tabindex="-1" aria-labelledby="viewTeacherModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="viewTeacherModalLabel">Detail Data Guru</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <table class="table table-bordered">
              <tbody>
                <tr><th>NIK</th><td id="view-nik"></td></tr>
                <tr><th>Nama Lengkap</th><td id="view-namaptk"></td></tr>
                <tr><th>Jenis Kelamin</th><td id="view-jenis_kelamin"></td></tr>
                <tr><th>Tanggal Lahir</th><td id="view-tgl_lahir"></td></tr>
                <tr><th>Alamat</th><td id="view-alamat"></td></tr>
                <tr><th>Email</th><td id="view-email"></td></tr>
                <tr><th>HP</th><td id="view-hp"></td></tr>
                <tr><th>Password</th>
                  <td>
                    <span id="view-password" style="letter-spacing:2px;"></span>
                    <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="toggle-password" onclick="togglePasswordView()" title="Tampilkan/Sembunyikan Password">
                      <i class="fas fa-eye"></i>
                    </button>
                  </td>
                </tr>
                <tr><th>Status</th><td id="view-status"></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    </div>

    <!-- Add Teacher Modal -->
    <div class="modal fade" id="addTeacherModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Data Guru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">NIK</label>
                                    <input type="text" class="form-control" name="nik" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" name="namaptk" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Jenis Kelamin</label>
                                    <select class="form-control" name="jenis_kelamin" required>
                                        <option value="">Pilih...</option>
                                        <option value="L">Laki-laki</option>
                                        <option value="P">Perempuan</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Lahir</label>
                                    <input type="date" class="form-control" name="tgl_lahir">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">No. HP</label>
                                    <input type="text" class="form-control" name="hp">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" name="alamat" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editTeacher(nik) {
            // Implementation for edit teacher
            window.location.href = 'admin_guru_edit.php?nik=' + nik;
        }

        function deleteTeacher(nik) {
            if (confirm('Apakah Anda yakin ingin menghapus data guru ini?')) {
                let form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="nik" value="${nik}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        let deleteNik = null;
        function confirmDeleteTeacher(nik) {
            deleteNik = nik;
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteTeacherModal'));
            deleteModal.show();
        }

        function performDeleteTeacher() {
            if (!deleteNik) return;
            let form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="nik" value="${deleteNik}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        let currentPassword = '';
        let passwordVisible = false;

        function viewTeacher(nik) {
            fetch('admin_guru_view.php?nik=' + encodeURIComponent(nik))
                .then(response => response.json())
                .then(data => {
                    document.getElementById('view-nik').textContent = data.nik;
                    document.getElementById('view-namaptk').textContent = data.namaptk;
                    document.getElementById('view-jenis_kelamin').textContent = data.jenis_kelamin;
                    document.getElementById('view-tgl_lahir').textContent = data.tgl_lahir;
                    document.getElementById('view-alamat').textContent = data.alamat;                   
                    document.getElementById('view-email').textContent = data.email;
                    document.getElementById('view-hp').textContent = data.hp;
                    currentPassword = data.password_plain || '';
                    passwordVisible = false;
                    document.getElementById('view-password').textContent = '•'.repeat(currentPassword.length);
                    document.getElementById('toggle-password').innerHTML = '<i class="fas fa-eye"></i>';
                })
                .catch(error => {
                    console.error('Error fetching teacher data:', error);
                    alert('Gagal mengambil data guru.');
                });
        }

        function togglePasswordView() {
            const pwSpan = document.getElementById('view-password');
            const toggleBtn = document.getElementById('toggle-password');
            if (!passwordVisible) {
                pwSpan.textContent = currentPassword;
                toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i>';
                passwordVisible = true;
            } else {
                pwSpan.textContent = '•'.repeat(currentPassword.length);
                toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
                passwordVisible = false;
            }
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
<div class="modal fade" id="deleteTeacherModal" tabindex="-1" aria-labelledby="deleteTeacherModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteTeacherModalLabel">Konfirmasi Hapus Data Guru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Apakah Anda yakin ingin menghapus data guru ini?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-danger" onclick="performDeleteTeacher()">Hapus</button>
      </div>
    </div>
  </div>
</div>

<?php if (isset($_GET['force_delete_nik'])): ?>
<!-- Modal Konfirmasi Paksa Hapus -->
<div class="modal fade show" id="forceDeleteModal" tabindex="-1" style="display:block;" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">Konfirmasi Hapus Paksa</h5>
        </div>
        <div class="modal-body">
          <p>Tidak dapat menghapus guru karena terdapat data verifikasi absensi terkait.<br>
          <b>Apakah kamu tetap ingin menghapus data ini beserta data verifikasi absennya?</b></p>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="nik" value="<?= htmlspecialchars($_GET['force_delete_nik']) ?>">
          <input type="hidden" name="force_delete" value="1">
        </div>
        <div class="modal-footer">
          <a href="admin_guru.php" class="btn btn-secondary">Batal</a>
          <button type="submit" class="btn btn-danger">Hapus Paksa</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
document.body.classList.add('modal-open');
</script>
<?php endif; ?>

</body>
</html>
