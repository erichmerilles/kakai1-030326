<?php
// frontend/navbar.php
if (basename($_SERVER['PHP_SELF']) == 'navbar.php') {
    header("Location: index.php");
    exit;
}

function isActive($page)
{
    return basename($_SERVER['PHP_SELF']) == $page ? 'active' : '';
}

$role = $_SESSION['role'] ?? 'guest';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<div class="mobile-header">
    <a class="text-white text-decoration-none fw-bold fs-5" href="#">
        <span class="brand-icon-mobile"><i class="bi bi-box-seam"></i></span> KakaiOne
    </a>
    <div class="d-flex align-items-center">
        <button class="btn text-white border-0" onclick="toggleSidebar()">
            <i class="bi bi-list fs-1"></i>
        </button>
    </div>
</div>

<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()"></div>

<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a class="brand-logo" href="#">
            <div class="brand-icon"><i class="bi bi-box-seam-fill"></i></div>
            KakaiOne
        </a>
    </div>

    <div class="sidebar-content">
        <ul class="nav nav-pills flex-column">

            <?php if ($role === 'admin'): ?>
                <div class="nav-section-label">Overview</div>
                <li class="nav-item mb-2">
                    <a href="admin_dashboard.php" class="nav-link <?php echo isActive('admin_dashboard.php'); ?>">
                        <i class="bi bi-house-door-fill"></i> Home / Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="analytics.php" class="nav-link <?php echo isActive('analytics.php'); ?>">
                        <i class="bi bi-pie-chart-fill"></i> Profit Analytics
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array($role, ['admin', 'cashier'])): ?>
                <div class="nav-section-label">Store Front</div>
                <li class="nav-item">
                    <a href="pos.php" class="nav-link <?php echo isActive('pos.php'); ?>">
                        <i class="bi bi-cart-fill"></i> Cash Register
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array($role, ['admin', 'stockman'])): ?>
                <div class="nav-section-label">Inventory</div>
                <li class="nav-item">
                    <a href="restock.php" class="nav-link <?php echo isActive('restock.php'); ?>">
                        <i class="bi bi-arrow-down-up"></i> Refill Shelves
                    </a>
                </li>
                <li class="nav-item">
                    <a href="stock_adjustments.php" class="nav-link <?php echo isActive('stock_adjustments.php'); ?>">
                        <i class="bi bi-truck"></i> Receive & Adjust
                    </a>
                </li>
                <li class="nav-item">
                    <a href="inventory.php" class="nav-link <?php echo isActive('inventory.php'); ?>">
                        <i class="bi bi-boxes"></i> Stock Levels
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($role === 'admin'): ?>
                <div class="nav-section-label">Admin Controls</div>
                <li class="nav-item">
                    <a href="products.php" class="nav-link <?php echo isActive('products.php'); ?>">
                        <i class="bi bi-tags-fill"></i> Manage Products
                    </a>
                </li>
                <li class="nav-item">
                    <a href="activity_log.php" class="nav-link <?php echo isActive('activity_log.php'); ?>">
                        <i class="bi bi-clock-history"></i> Activity History
                    </a>
                </li>
            <?php endif; ?>

        </ul>
    </div>

    <div class="user-profile">
        <div class="user-card">
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
            </div>
            <div class="d-flex flex-column">
                <span class="fw-bold text-white"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                <span class="text-muted small text-uppercase" style="font-size: 0.75rem;">
                    <?php echo htmlspecialchars($role); ?>
                </span>
            </div>
        </div>
        <button onclick="logout()" class="btn btn-danger w-100 btn-sm fw-bold">
            <i class="bi bi-box-arrow-right me-2"></i> Log Out
        </button>
    </div>
</nav>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('show');
        document.getElementById('sidebarBackdrop').classList.toggle('show');
    }

    function logout() {
        Swal.fire({
            title: 'Sign Out?',
            text: "Are you sure you want to log out?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f1416c',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, Log Out'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'logout.php';
            }
        })
    }
</script>