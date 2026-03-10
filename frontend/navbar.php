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

// Function to keep accordion open if a child page is active
function isAccordionActive($pages)
{
    return in_array(basename($_SERVER['PHP_SELF']), $pages) ? 'show' : '';
}

function isAccordionButtonActive($pages)
{
    return in_array(basename($_SERVER['PHP_SELF']), $pages) ? '' : 'collapsed';
}

$role = $_SESSION['role'] ?? 'guest';
$userName = $_SESSION['username'] ?? 'User';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<div class="mobile-header d-flex justify-content-between align-items-center d-lg-none">
    <a class="text-white text-decoration-none fw-bold fs-5" href="#">
        <span class="brand-icon-mobile"><i class="bi bi-box-seam"></i></span> Kakai's
    </a>
    <button class="btn text-white border-0 p-0" onclick="toggleSidebar()">
        <i class="bi bi-list fs-1"></i>
    </button>
</div>

<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()"></div>

<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a class="brand-logo" href="#">
            <div class="brand-icon"><i class="bi bi-box-seam-fill"></i></div>
            Kakai's POS
        </a>
    </div>

    <div class="sidebar-content">
        <div class="accordion accordion-flush" id="sidebarAccordion">

            <div class="nav-item mb-2">
                <?php
                $dashLink = 'customer.php';
                if ($role === 'admin') $dashLink = 'admin_dashboard.php';
                if ($role === 'stockman') $dashLink = 'stockman_dashboard.php';
                if ($role === 'cashier') $dashLink = 'cashier_dashboard.php';
                ?>
                <a href="<?= $dashLink ?>" class="nav-link <?php echo isActive($dashLink); ?>">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
            </div>

            <?php
            $dailyPages = ['pos.php', 'online_orders.php'];
            if (in_array($role, ['admin', 'cashier'])):
            ?>
                <div class="accordion-item bg-transparent border-0 mb-1">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?= isAccordionButtonActive($dailyPages) ?> bg-transparent shadow-none nav-link-accordion" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDaily">
                            <i class="bi bi-cart-fill me-3" style="width:24px; text-align:center;"></i> Daily Operations
                        </button>
                    </h2>
                    <div id="collapseDaily" class="accordion-collapse collapse <?= isAccordionActive($dailyPages) ?>" data-bs-parent="#sidebarAccordion">
                        <div class="accordion-body p-0 pt-1 pb-1 ms-4">
                            <ul class="nav nav-pills flex-column">
                                <li class="nav-item">
                                    <a href="pos.php" class="nav-link py-2 px-3 <?php echo isActive('pos.php'); ?>"><i class="bi bi-calculator me-2 fs-6"></i> Point of Sale</a>
                                </li>
                                <li class="nav-item">
                                    <a href="online_orders.php" class="nav-link py-2 px-3 <?php echo isActive('online_orders.php'); ?>"><i class="bi bi-globe me-2 fs-6"></i> Online Orders</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php
            $inventoryPages = ['products.php', 'receive_return.php', 'inventory_control.php', 'supplier_directory.php'];
            if (in_array($role, ['admin', 'stockman'])):
            ?>
                <div class="accordion-item bg-transparent border-0 mb-1">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?= isAccordionButtonActive($inventoryPages) ?> bg-transparent shadow-none nav-link-accordion" type="button" data-bs-toggle="collapse" data-bs-target="#collapseInventory">
                            <i class="bi bi-boxes me-3" style="width:24px; text-align:center;"></i> Inventory & Logistics
                        </button>
                    </h2>
                    <div id="collapseInventory" class="accordion-collapse collapse <?= isAccordionActive($inventoryPages) ?>" data-bs-parent="#sidebarAccordion">
                        <div class="accordion-body p-0 pt-1 pb-1 ms-4">
                            <ul class="nav nav-pills flex-column">
                                <li class="nav-item">
                                    <a href="products.php" class="nav-link py-2 px-3 <?php echo isActive('products.php'); ?>"><i class="bi bi-list-ul me-2 fs-6"></i> Product Master List</a>
                                </li>
                                <li class="nav-item">
                                    <a href="receive_return.php" class="nav-link py-2 px-3 <?php echo isActive('receive_return.php'); ?>"><i class="bi bi-arrow-left-right me-2 fs-6"></i> Receive / Return</a>
                                </li>
                                <li class="nav-item">
                                    <a href="inventory_control.php" class="nav-link py-2 px-3 <?php echo isActive('inventory_control.php'); ?>"><i class="bi bi-sliders me-2 fs-6"></i> Inventory Control</a>
                                </li>
                                <li class="nav-item">
                                    <a href="supplier_directory.php" class="nav-link py-2 px-3 <?php echo isActive('supplier_directory.php'); ?>"><i class="bi bi-truck me-2 fs-6"></i> Supplier Directory</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($role === 'cashier'): ?>
                <div class="nav-item mb-2">
                    <a href="store_shelf.php" class="nav-link <?php echo isActive('store_shelf.php'); ?>">
                        <i class="bi bi-shop"></i> Store Shelf View
                    </a>
                </div>
            <?php endif; ?>

            <?php
            $biPages = ['profitability.php', 'inventory_forecast.php', 'expiry_tracker.php'];
            if ($role === 'admin'):
            ?>
                <div class="accordion-item bg-transparent border-0 mb-1">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?= isAccordionButtonActive($biPages) ?> bg-transparent shadow-none nav-link-accordion" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBI">
                            <i class="bi bi-graph-up-arrow me-3" style="width:24px; text-align:center;"></i> Business Intel
                        </button>
                    </h2>
                    <div id="collapseBI" class="accordion-collapse collapse <?= isAccordionActive($biPages) ?>" data-bs-parent="#sidebarAccordion">
                        <div class="accordion-body p-0 pt-1 pb-1 ms-4">
                            <ul class="nav nav-pills flex-column">
                                <li class="nav-item">
                                    <a href="profitability.php" class="nav-link py-2 px-3 <?php echo isActive('profitability.php'); ?>"><i class="bi bi-cash-coin me-2 fs-6"></i> Profitability</a>
                                </li>
                                <li class="nav-item">
                                    <a href="inventory_forecast.php" class="nav-link py-2 px-3 <?php echo isActive('inventory_forecast.php'); ?>"><i class="bi bi-stars me-2 fs-6"></i> Stock Forecast</a>
                                </li>
                                <li class="nav-item">
                                    <a href="expiry_tracker.php" class="nav-link py-2 px-3 <?php echo isActive('expiry_tracker.php'); ?>"><i class="bi bi-exclamation-triangle me-2 fs-6"></i> Expiry Tracker</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <?php
                $userPages = ['user_profiles.php', 'rbac.php'];
                ?>
                <div class="accordion-item bg-transparent border-0 mb-1">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?= isAccordionButtonActive($userPages) ?> bg-transparent shadow-none nav-link-accordion" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUsers">
                            <i class="bi bi-shield-lock-fill me-3" style="width:24px; text-align:center;"></i> Access Control
                        </button>
                    </h2>
                    <div id="collapseUsers" class="accordion-collapse collapse <?= isAccordionActive($userPages) ?>" data-bs-parent="#sidebarAccordion">
                        <div class="accordion-body p-0 pt-1 pb-1 ms-4">
                            <ul class="nav nav-pills flex-column">
                                <li class="nav-item">
                                    <a href="user_profiles.php" class="nav-link py-2 px-3 <?php echo isActive('user_profiles.php'); ?>"><i class="bi bi-people me-2 fs-6"></i> User Profiles</a>
                                </li>
                                <li class="nav-item">
                                    <a href="rbac.php" class="nav-link py-2 px-3 <?php echo isActive('rbac.php'); ?>"><i class="bi bi-key me-2 fs-6"></i> RBAC Matrix</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="nav-item mb-2 mt-3">
                    <a href="activity_log.php" class="nav-link <?php echo isActive('activity_log.php'); ?>">
                        <i class="bi bi-journal-text"></i> Activity Log
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <div class="user-profile">
        <div class="user-card">
            <div class="user-avatar">
                <?php echo strtoupper(substr($userName, 0, 1)); ?>
            </div>
            <div class="d-flex flex-column">
                <span class="fw-bold text-white"><?php echo htmlspecialchars($userName); ?></span>
                <span class="text-muted small text-uppercase" style="font-size: 0.75rem;">
                    <?php echo htmlspecialchars($role); ?>
                </span>
            </div>
        </div>
        <button onclick="confirmLogout()" class="btn btn-danger w-100 btn-sm fw-bold">
            <i class="bi bi-box-arrow-right me-2"></i> Log Out
        </button>
    </div>
</nav>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('show');
        document.getElementById('sidebarBackdrop').classList.toggle('show');
    }

    function confirmLogout() {
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
                window.location.href = '../backend/auth/logout.php';
            }
        })
    }
</script>