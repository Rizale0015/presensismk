<?php
// Shared header partial for Admin pages
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="admin_dashboard.php">
            <img src="assets/img/logoAA.png" alt="Logo" width="36" height="36" class="me-2" style="object-fit:contain;">
            Sistem Presensi - Admin
        </a>
        <ul class="navbar-nav ms-auto flex-row align-items-center">
            <li class="nav-item me-3">
                <span class="navbar-text">
                    Selamat datang, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin'; ?>
                </span>
            </li>
            <li class="nav-item me-3">
                <a href="admin_profil.php" class="nav-link" title="Profil Admin">
                    <i class="fas fa-user-circle fa-lg"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> 
                </a>
            </li>
        </ul>
    </div>
</nav>

<!-- Modal Feedback -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" id="feedbackModalHeader">
        <h5 class="modal-title" id="feedbackModalLabel"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="feedbackModalBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>
<script>
function showFeedbackModal(type, message) {
    const modal = new bootstrap.Modal(document.getElementById('feedbackModal'));
    const header = document.getElementById('feedbackModalHeader');
    const label = document.getElementById('feedbackModalLabel');
    const body = document.getElementById('feedbackModalBody');

    if(type === 'success') {
        header.className = 'modal-header bg-success text-white';
        label.innerHTML = '<i class="fas fa-check-circle me-2"></i>Berhasil';
    } else {
        header.className = 'modal-header bg-danger text-white';
        label.innerHTML = '<i class="fas fa-times-circle me-2"></i>Gagal';
    }
    body.textContent = message;
    modal.show();
}
</script>
