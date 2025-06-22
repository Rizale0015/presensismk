<?php
require_once 'session.php';
require_once 'database.php';
checkAdminAccess();

$database = new Database();
$db = $database->getConnection();

// Fetch data for dropdowns
$queryKelas = "SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas";
$stmtKelas = $db->prepare($queryKelas);
$stmtKelas->execute();
$kelasList = $stmtKelas->fetchAll(PDO::FETCH_ASSOC);

$queryGuru = "SELECT nik, namaptk FROM guru ORDER BY namaptk";
$stmtGuru = $db->prepare($queryGuru);
$stmtGuru->execute();
$guruList = $stmtGuru->fetchAll(PDO::FETCH_ASSOC);

$queryPelajaran = "SELECT id_pelajaran, nama_pelajaran FROM pelajaran ORDER BY nama_pelajaran";
$stmtPelajaran = $db->prepare($queryPelajaran);
$stmtPelajaran->execute();
$pelajaranList = $stmtPelajaran->fetchAll(PDO::FETCH_ASSOC);

$queryJam = "SELECT id_jam, jam_mulai, jam_selesai FROM jam ORDER BY jam_mulai";
$stmtJam = $db->prepare($queryJam);
$stmtJam->execute();
$jamList = $stmtJam->fetchAll(PDO::FETCH_ASSOC);

$queryHari = "SELECT id_hari, nama_hari FROM hari ORDER BY id_hari";
$stmtHari = $db->prepare($queryHari);
$stmtHari->execute();
$hariList = $stmtHari->fetchAll(PDO::FETCH_ASSOC);

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $query = "INSERT INTO jadwal_pelajaran (id_pelajaran, id_kelas, id_jam, id_hari, nik) 
                          VALUES (:id_pelajaran, :id_kelas, :id_jam, :id_hari, :nik)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id_pelajaran', $_POST['id_pelajaran']);
                $stmt->bindParam(':id_kelas', $_POST['id_kelas']);
                $stmt->bindParam(':id_jam', $_POST['id_jam']);
                $stmt->bindParam(':id_hari', $_POST['id_hari']);
                $stmt->bindParam(':nik', $_POST['nik']);
                if ($stmt->execute()) {
                    $success = "Jadwal pelajaran berhasil ditambahkan!";
                } else {
                    $error = "Gagal menambahkan jadwal pelajaran!";
                }
                break;
            case 'edit':
                $query = "UPDATE jadwal_pelajaran SET id_pelajaran=:id_pelajaran, id_kelas=:id_kelas, id_jam=:id_jam, id_hari=:id_hari, nik=:nik 
                          WHERE id=:id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id_pelajaran', $_POST['id_pelajaran']);
                $stmt->bindParam(':id_kelas', $_POST['id_kelas']);
                $stmt->bindParam(':id_jam', $_POST['id_jam']);
                $stmt->bindParam(':id_hari', $_POST['id_hari']);
                $stmt->bindParam(':nik', $_POST['nik']);
                $stmt->bindParam(':id', $_POST['id']);
                if ($stmt->execute()) {
                    $success = "Jadwal pelajaran berhasil diupdate!";
                } else {
                    $error = "Gagal mengupdate jadwal pelajaran!";
                }
                break;
            case 'delete':
                $query = "DELETE FROM jadwal_pelajaran WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $_POST['id']);
                if ($stmt->execute()) {
                    $success = "Jadwal pelajaran berhasil dihapus!";
                } else {
                    $error = "Gagal menghapus jadwal pelajaran!";
                }
                break;
        }
    }

    if (isset($_POST['jam'])) {
        foreach ($_POST['jam'] as $jam) {
            // Simpan $jam ke database (pastikan validasi format di sisi server juga)
        }
    }
}

// Fetch jadwal pelajaran with joins for display
$query = "SELECT jp.id, p.nama_pelajaran, k.nama_kelas, j.jam_mulai, j.jam_selesai, h.nama_hari, g.namaptk
          FROM jadwal_pelajaran jp
          LEFT JOIN pelajaran p ON jp.id_pelajaran = p.id_pelajaran
          LEFT JOIN kelas k ON jp.id_kelas = k.id_kelas
          LEFT JOIN jam j ON jp.id_jam = j.id_jam
          LEFT JOIN hari h ON jp.id_hari = h.id_hari
          LEFT JOIN guru g ON jp.nik = g.nik
          ORDER BY h.id_hari, j.jam_mulai";
$stmt = $db->prepare($query);
$stmt->execute();
$jadwalList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kelola Jadwal Pelajaran - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/sidebar.css"> 
</head>
<body>
<?php include 'admin_header.php'; ?>

<div class="container mt-4">
    <div class="row">
            <div class="col-md-3">
                <?php include 'admin_sidebar.php'; ?>
            </div>
        <div class="col-md-9 main-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>Jadwal Pelajaran</h5>
        <div>
            <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addJadwalModal">Tambah Jadwal</button>
            <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#manageMasterDataModal">Edit Jadwal</button>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

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

    <div class="table-responsive">
        <table class="table table-striped" id="jadwalTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Pelajaran</th>
                    <th>Kelas</th>
                    <th>Jam Mulai</th>
                    <th>Jam Selesai</th>
                    <th>Hari</th>
                    <th>Guru</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jadwalList as $jadwal): ?>
                <tr data-kelas="<?php echo htmlspecialchars($jadwal['nama_kelas']); ?>">
                    <td><?php echo htmlspecialchars($jadwal['id']); ?></td>
                    <td><?php echo htmlspecialchars($jadwal['nama_pelajaran']); ?></td>
                    <td><?php echo htmlspecialchars($jadwal['nama_kelas']); ?></td>
                    <td><?php echo htmlspecialchars($jadwal['jam_mulai']); ?></td>
                    <td><?php echo htmlspecialchars($jadwal['jam_selesai']); ?></td>
                    <td><?php echo htmlspecialchars($jadwal['nama_hari']); ?></td>
                    <td><?php echo htmlspecialchars($jadwal['namaptk']); ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="editJadwal(<?php echo $jadwal['id']; ?>)">Edit</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteJadwal(<?php echo $jadwal['id']; ?>)">Hapus</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Jadwal Modal -->
<div class="modal fade" id="addJadwalModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="jadwalForm">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah/Edit Jadwal Pelajaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add" />
                    <input type="hidden" name="id" id="id" />
                    <div class="mb-3">
                        <label for="id_pelajaran" class="form-label">Pelajaran</label>
                        <select class="form-control" name="id_pelajaran" id="id_pelajaran" required>
                            <option value="">Pilih Pelajaran</option>
                            <?php foreach ($pelajaranList as $pelajaran): ?>
                                <option value="<?php echo $pelajaran['id_pelajaran']; ?>"><?php echo htmlspecialchars($pelajaran['nama_pelajaran']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="id_kelas" class="form-label">Kelas</label>
                        <select class="form-control" name="id_kelas" id="id_kelas" required>
                            <option value="">Pilih Kelas</option>
                            <?php foreach ($kelasList as $kelas): ?>
                                <option value="<?php echo $kelas['id_kelas']; ?>"><?php echo htmlspecialchars($kelas['nama_kelas']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="id_jam" class="form-label">Jam</label>
                        <select class="form-control" name="id_jam" id="id_jam" required>
                            <option value="">Pilih Jam</option>
                            <?php foreach ($jamList as $jam): ?>
                                <option value="<?php echo $jam['id_jam']; ?>"><?php echo htmlspecialchars($jam['jam_mulai'] . ' - ' . $jam['jam_selesai']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="id_hari" class="form-label">Hari</label>
                        <select class="form-control" name="id_hari" id="id_hari" required>
                            <option value="">Pilih Hari</option>
                            <?php foreach ($hariList as $hari): ?>
                                <option value="<?php echo $hari['id_hari']; ?>"><?php echo htmlspecialchars($hari['nama_hari']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="nik" class="form-label">Guru</label>
                        <select class="form-control" name="nik" id="nik" required>
                            <option value="">Pilih Guru</option>
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

<!-- Manage Master Data Modal -->
<div class="modal fade" id="manageMasterDataModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="POST" id="masterDataForm">
                <div class="modal-header">
                    <h5 class="modal-title">Kelola Data Master Jadwal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Pelajaran Management -->
                        <div class="col-md-4">
                            <h6>Pelajaran</h6>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="newPelajaran" placeholder="Tambah Pelajaran Baru">
                                <button type="button" class="btn btn-primary mt-2" onclick="addPelajaran()">Tambah</button>
                            </div>
                            <ul class="list-group" id="pelajaranList">
                                <?php foreach ($pelajaranList as $pelajaran): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?= htmlspecialchars($pelajaran['nama_pelajaran']); ?>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="deletePelajaran(<?= $pelajaran['id_pelajaran']; ?>)">Hapus</button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <!-- Jam Management -->
                        <div class="col-md-4">
                            <h6>Jam</h6>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="newJam" placeholder="Tambah Jam Baru (format: HH:MM - HH:MM)">
                                <button type="button" class="btn btn-primary mt-2" onclick="addJam()">Tambah</button>
                            </div>
                            <ul class="list-group" id="jamList">
                                <?php foreach ($jamList as $jam): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?= htmlspecialchars($jam['jam_mulai'] . ' - ' . $jam['jam_selesai']); ?>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteJam(<?= $jam['id_jam']; ?>)">Hapus</button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <!-- Hari Management -->
                        <div class="col-md-4">
                            <h6>Hari</h6>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="newHari" placeholder="Tambah Hari Baru">
                                <button type="button" class="btn btn-primary mt-2" onclick="addHari()">Tambah</button>
                            </div>
                            <ul class="list-group" id="hariList">
                                <?php foreach ($hariList as $hari): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?= htmlspecialchars($hari['nama_hari']); ?>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteHari(<?= $hari['id_hari']; ?>)">Hapus</button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Functions to add and delete pelajaran, jam, hari via AJAX calls
    function addPelajaran() {
        const name = document.getElementById('newPelajaran').value.trim();
        if (!name) return alert('Masukkan nama pelajaran.');
        fetch('api/manage_master_data.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({type: 'pelajaran', action: 'add', name})
        }).then(res => res.json()).then(data => {
            if (data.success) {
                // Reload list without closing modal
                location.reload();
            } else {
                alert(data.message || 'Gagal menambah pelajaran.');
            }
        });
    }
    function deletePelajaran(id) {
        if (!confirm('Hapus pelajaran ini?')) return;
        fetch('api/manage_master_data.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({type: 'pelajaran', action: 'delete', id})
        }).then(res => res.json()).then(data => {
            if (data.success) {
                // Reload list without closing modal
                location.reload();
            } else {
                alert(data.message || 'Gagal menghapus pelajaran.');
            }
        });
    }
    function addJam() {
        const time = document.getElementById('newJam').value.trim();
        if (!time) return alert('Masukkan jam baru.');
        fetch('api/manage_master_data.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({type: 'jam', action: 'add', time})
        }).then(res => res.json()).then(data => {
            if (data.success) {
                // Reload list without closing modal
                location.reload();
            } else {
                alert(data.message || 'Gagal menambah jam.');
            }
        });
    }
    function deleteJam(id) {
        if (!confirm('Hapus jam ini?')) return;
        fetch('api/manage_master_data.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({type: 'jam', action: 'delete', id})
        }).then(res => res.json()).then(data => {
            if (data.success) {
                // Reload list without closing modal
                location.reload();
            } else {
                alert(data.message || 'Gagal menghapus jam.');
            }
        });
    }
    function addHari() {
        const name = document.getElementById('newHari').value.trim();
        if (!name) return alert('Masukkan nama hari.');
        fetch('api/manage_master_data.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({type: 'hari', action: 'add', name})
        }).then(res => res.json()).then(data => {
            if (data.success) {
                // Reload list without closing modal
                location.reload();
            } else {
                alert(data.message || 'Gagal menambah hari.');
            }
        });
    }
    function deleteHari(id) {
        if (!confirm('Hapus hari ini?')) return;
        fetch('api/manage_master_data.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({type: 'hari', action: 'delete', id})
        }).then(res => res.json()).then(data => {
            if (data.success) {
                // Reload list without closing modal
                location.reload();
            } else {
                alert(data.message || 'Gagal menghapus hari.');
            }
        });
    }
</script>

<!-- Edit Jadwal Modal -->
<div class="modal fade" id="editJadwalModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="editJadwalForm">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Jadwal Pelajaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_id_jadwal" class="form-label">Pilih Jadwal</label>
                        <select class="form-control" name="id" id="edit_id_jadwal" required>
                            <option value="">Pilih Jadwal</option>
                            <?php foreach ($jadwalList as $jadwal): ?>
                                <option value="<?php echo $jadwal['id']; ?>">
                                    <?php echo htmlspecialchars($jadwal['nama_pelajaran'] . ' - ' . $jadwal['nama_kelas'] . ' - ' . $jadwal['nama_hari'] . ' - ' . $jadwal['jam_mulai'] . '-' . $jadwal['jam_selesai']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_id_pelajaran" class="form-label">Pelajaran</label>
                        <select class="form-control" name="id_pelajaran" id="edit_id_pelajaran" required>
                            <option value="">Pilih Pelajaran</option>
                            <?php foreach ($pelajaranList as $pelajaran): ?>
                                <option value="<?php echo $pelajaran['id_pelajaran']; ?>"><?php echo htmlspecialchars($pelajaran['nama_pelajaran']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_id_jam" class="form-label">Jam</label>
                        <select class="form-control" name="id_jam" id="edit_id_jam" required>
                            <option value="">Pilih Jam</option>
                            <?php foreach ($jamList as $jam): ?>
                                <option value="<?php echo $jam['id_jam']; ?>"><?php echo htmlspecialchars($jam['jam_mulai'] . ' - ' . $jam['jam_selesai']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_id_hari" class="form-label">Hari</label>
                        <select class="form-control" name="id_hari" id="edit_id_hari" required>
                            <option value="">Pilih Hari</option>
                            <?php foreach ($hariList as $hari): ?>
                                <option value="<?php echo $hari['id_hari']; ?>"><?php echo htmlspecialchars($hari['nama_hari']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_nik" class="form-label">Guru</label>
                        <select class="form-control" name="nik" id="edit_nik" required>
                            <option value="">Pilih Guru</option>
                            <?php foreach ($guruList as $guru): ?>
                                <option value="<?php echo $guru['nik']; ?>"><?php echo htmlspecialchars($guru['namaptk']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="editSubmitBtn">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Handle edit jadwal modal form submission
    document.getElementById('editJadwalForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        formData.set('action', 'edit');
        fetch('', {
            method: 'POST',
            body: formData
        }).then(response => response.text())
          .then(data => {
              location.reload();
          }).catch(error => {
              alert('Gagal mengupdate jadwal pelajaran.');
          });
    });

    // Populate edit jadwal modal fields when jadwal is selected
    document.getElementById('edit_id_jadwal').addEventListener('change', function() {
        const jadwalId = this.value;
        const jadwalList = <?php echo json_encode($jadwalList); ?>;
        const jadwal = jadwalList.find(j => j.id == jadwalId);
        if (!jadwal) return;

        document.getElementById('edit_id_pelajaran').value = jadwal.id_pelajaran;
        document.getElementById('edit_id_kelas').value = jadwal.id_kelas;
        document.getElementById('edit_id_jam').value = jadwal.id_jam;
        document.getElementById('edit_id_hari').value = jadwal.id_hari;
        document.getElementById('edit_nik').value = jadwal.nik;
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function editJadwal(id) {
        const jadwalList = <?php echo json_encode($jadwalList); ?>;
        const jadwal = jadwalList.find(j => j.id == id);
        if (!jadwal) return;

        document.getElementById('formAction').value = 'edit';
        document.getElementById('id').value = jadwal.id;
        document.getElementById('id_pelajaran').value = jadwal.id_pelajaran;
        document.getElementById('id_kelas').value = jadwal.id_kelas;
        document.getElementById('id_jam').value = jadwal.id_jam;
        document.getElementById('id_hari').value = jadwal.id_hari;
        document.getElementById('nik').value = jadwal.nik;

        var myModal = new bootstrap.Modal(document.getElementById('addJadwalModal'));
        myModal.show();
    }

    function deleteJadwal(id) {
        if (confirm('Apakah Anda yakin ingin menghapus jadwal pelajaran ini?')) {
            let form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete" />
                <input type="hidden" name="id" value="${id}" />
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Reset form when modal is hidden
    var addJadwalModal = document.getElementById('addJadwalModal');
    addJadwalModal.addEventListener('hidden.bs.modal', function () {
        document.getElementById('jadwalForm').reset();
        document.getElementById('formAction').value = 'add';
        document.getElementById('id').value = '';
    });

    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('jamContainer').addEventListener('click', function(e) {
            if (e.target.closest('.add-jam')) {
                let row = e.target.closest('.jam-row');
                let clone = row.cloneNode(true);
                clone.querySelector('input').value = '';
                clone.querySelector('.remove-jam').style.display = '';
                this.appendChild(clone);
            }
            if (e.target.closest('.remove-jam')) {
                let row = e.target.closest('.jam-row');
                row.remove();
            }
        });
    });
</script>
<script>
function filterByKelas(kelas, btn) {
    // Filter table rows
    document.querySelectorAll('#jadwalTable tbody tr').forEach(function(row) {
        if (!kelas || row.getAttribute('data-kelas') === kelas) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
    // Ubah tombol aktif
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
</script>
</body>
</html>
