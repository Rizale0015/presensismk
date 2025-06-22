<?php
require_once 'session.php';
require_once 'database.php';
checkAdminAccess();

$database = new Database();
$db = $database->getConnection();

// Fetch kelas for filter and form dropdown
$queryKelas = "SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas";
$stmtKelas = $db->prepare($queryKelas);
$stmtKelas->execute();
$kelasList = $stmtKelas->fetchAll(PDO::FETCH_ASSOC);

// Handle form submissions for add/edit/delete (synchronous)
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $query = "INSERT INTO absen_siswa (nis, id_kelas, id_jam, id_pelajaran, id_hari, id_tanggal, jam_hadir, keterangan) 
                          VALUES (:nis, :id_kelas, :id_jam, :id_pelajaran, :id_hari, :id_tanggal, :jam_hadir, :keterangan)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nis', $_POST['nis']);
                $stmt->bindParam(':id_kelas', $_POST['id_kelas']);
                $stmt->bindParam(':id_jam', $_POST['id_jam']);
                $stmt->bindParam(':id_pelajaran', $_POST['id_pelajaran']);
                $stmt->bindParam(':id_hari', $_POST['id_hari']);
                $stmt->bindParam(':id_tanggal', $_POST['id_tanggal']);
                $stmt->bindParam(':jam_hadir', $_POST['jam_hadir']);
                $stmt->bindParam(':keterangan', $_POST['keterangan']);
                if ($stmt->execute()) {
                    $success = "Data absensi berhasil ditambahkan!";
                } else {
                    $error = "Gagal menambahkan data absensi!";
                }
                break;
            case 'edit':
                $query = "UPDATE absen_siswa SET nis=:nis, id_kelas=:id_kelas, id_jam=:id_jam, id_pelajaran=:id_pelajaran, 
                          id_hari=:id_hari, id_tanggal=:id_tanggal, jam_hadir=:jam_hadir, keterangan=:keterangan 
                          WHERE id_absen=:id_absen";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nis', $_POST['nis']);
                $stmt->bindParam(':id_kelas', $_POST['id_kelas']);
                $stmt->bindParam(':id_jam', $_POST['id_jam']);
                $stmt->bindParam(':id_pelajaran', $_POST['id_pelajaran']);
                $stmt->bindParam(':id_hari', $_POST['id_hari']);
                $stmt->bindParam(':id_tanggal', $_POST['id_tanggal']);
                $stmt->bindParam(':jam_hadir', $_POST['jam_hadir']);
                $stmt->bindParam(':keterangan', $_POST['keterangan']);
                $stmt->bindParam(':id_absen', $_POST['id_absen']);
                if ($stmt->execute()) {
                    $success = "Data absensi berhasil diupdate!";
                } else {
                    $error = "Gagal mengupdate data absensi!";
                }
                break;
            case 'delete':
                $query = "DELETE FROM absen_siswa WHERE id_absen = :id_absen";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id_absen', $_POST['id_absen']);
                if ($stmt->execute()) {
                    $success = "Data absensi berhasil dihapus!";
                } else {
                    $error = "Gagal menghapus data absensi!";
                }
                break;
        }
    }
}

// Fetch students for dropdown
$querySiswa = "SELECT nis, nama FROM siswa ORDER BY nama";
$stmtSiswa = $db->prepare($querySiswa);
$stmtSiswa->execute();
$siswaList = $stmtSiswa->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kelola Data Absensi - Admin</title>
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
        <div class="col-md-9 main-content">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Data Absensi Siswa</h5>
                <div>
                    <a href="admin_dashboard.php" class="btn btn-secondary me-2">Kembali</a>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAbsensiModal">Tambah Absensi</button>
                </div>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-4">
                    <select id="filterKelas" class="form-select">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($kelasList as $kelas): ?>
                            <option value="<?php echo $kelas['id_kelas']; ?>"><?php echo htmlspecialchars($kelas['nama_kelas']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" id="searchInput" class="form-control" placeholder="Cari nama siswa atau keterangan..." />
                </div>
            </div>

            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-striped" id="absensiTable">
                    <thead>
                        <tr>
                            <th>ID Absensi</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Tanggal</th>
                            <th>Jam Hadir</th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="absensiTableBody">
                        <!-- Data will be loaded here by JavaScript -->
                    </tbody>
                </table>
            </div>

            <nav>
                <ul class="pagination" id="pagination">
                    <!-- Pagination buttons will be loaded here -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Add/Edit Absensi Modal -->
<div class="modal fade" id="addAbsensiModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="absensiForm">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah/Edit Data Absensi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add" />
                    <input type="hidden" name="id_absen" id="id_absen" />
                    <div class="mb-3">
                        <label for="nis" class="form-label">Nama Siswa</label>
                        <select class="form-control" name="nis" id="nis" required>
                            <option value="">Pilih Siswa</option>
                            <?php foreach ($siswaList as $siswa): ?>
                                <option value="<?php echo $siswa['nis']; ?>"><?php echo htmlspecialchars($siswa['nama']); ?></option>
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
                        <label for="id_tanggal" class="form-label">Tanggal</label>
                        <input type="date" class="form-control" name="id_tanggal" id="id_tanggal" required />
                    </div>
                    <div class="mb-3">
                        <label for="jam_hadir" class="form-label">Jam Hadir</label>
                        <input type="time" class="form-control" name="jam_hadir" id="jam_hadir" required />
                    </div>
                    <div class="mb-3">
                        <label for="keterangan" class="form-label">Keterangan</label>
                        <textarea class="form-control" name="keterangan" id="keterangan"></textarea>
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
    let currentPage = 1;
    const rowsPerPage = 10;
    let totalRows = 0;

    function fetchData(page = 1) {
        const filterKelas = document.getElementById('filterKelas').value;
        const search = document.getElementById('searchInput').value.trim();

        const params = new URLSearchParams();
        params.append('limit', rowsPerPage);
        params.append('offset', (page - 1) * rowsPerPage);
        if (filterKelas) params.append('id_kelas', filterKelas);
        if (search) params.append('search', search);

        fetch('api/absensi_siswa.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                totalRows = data.total;
                renderTable(data.data);
                renderPagination(page);
            })
            .catch(error => {
                console.error('Error fetching data:', error);
            });
    }

    function renderTable(data) {
        const tbody = document.getElementById('absensiTableBody');
        tbody.innerHTML = '';

        if (data.length === 0) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = 7;
            td.className = 'text-center';
            td.textContent = 'Tidak ada data.';
            tr.appendChild(td);
            tbody.appendChild(tr);
            return;
        }

        data.forEach(item => {
            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td>${item.id_absen}</td>
                <td>${item.nama_siswa}</td>
                <td>${item.nama_kelas}</td>
                <td>${item.id_tanggal}</td>
                <td>${item.jam_hadir}</td>
                <td>${item.keterangan}</td>
                <td>
                    <button class="btn btn-sm btn-warning" onclick="editAbsensi(${item.id_absen})">Edit</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteAbsensi(${item.id_absen})">Hapus</button>
                </td>
            `;

            tbody.appendChild(tr);
        });
    }

    function renderPagination(current) {
        const pagination = document.getElementById('pagination');
        pagination.innerHTML = '';

        const totalPages = Math.ceil(totalRows / rowsPerPage);
        if (totalPages <= 1) return;

        for (let i = 1; i <= totalPages; i++) {
            const li = document.createElement('li');
            li.className = 'page-item' + (i === current ? ' active' : '');
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = i;
            a.addEventListener('click', (e) => {
                e.preventDefault();
                currentPage = i;
                fetchData(currentPage);
            });
            li.appendChild(a);
            pagination.appendChild(li);
        }
    }

    function editAbsensi(id_absen) {
        fetch('api/absensi_siswa.php?limit=1&offset=0&search=&id_kelas=')
            .then(response => response.json())
            .then(data => {
                const absensi = data.data.find(a => a.id_absen == id_absen);
                if (!absensi) return;

                document.getElementById('formAction').value = 'edit';
                document.getElementById('id_absen').value = absensi.id_absen;
                document.getElementById('nis').value = absensi.nis;
                document.getElementById('id_kelas').value = absensi.id_kelas;
                document.getElementById('id_tanggal').value = absensi.id_tanggal;
                document.getElementById('jam_hadir').value = absensi.jam_hadir;
                document.getElementById('keterangan').value = absensi.keterangan;

                var myModal = new bootstrap.Modal(document.getElementById('addAbsensiModal'));
                myModal.show();
            })
            .catch(error => {
                console.error('Error fetching absensi data:', error);
            });
    }

    function deleteAbsensi(id_absen) {
        if (confirm('Apakah Anda yakin ingin menghapus data absensi ini?')) {
            let form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete" />
                <input type="hidden" name="id_absen" value="${id_absen}" />
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Reset form when modal is hidden
    var addAbsensiModal = document.getElementById('addAbsensiModal');
    addAbsensiModal.addEventListener('hidden.bs.modal', function () {
        document.getElementById('absensiForm').reset();
        document.getElementById('formAction').value = 'add';
        document.getElementById('id_absen').value = '';
    });

    // Fetch initial data
    document.addEventListener('DOMContentLoaded', function () {
        fetchData();
    });

    // Fetch data on filter or search change
    document.getElementById('filterKelas').addEventListener('change', function () {
        currentPage = 1;
        fetchData();
    });
    document.getElementById('searchInput').addEventListener('input', function () {
        currentPage = 1;
        fetchData();
    });
</script>
</body>
</html>
