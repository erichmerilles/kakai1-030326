<?php
// Prevent direct access to the navbar fragment
if (basename($_SERVER['PHP_SELF']) == 'navbar.php') {
    header("Location: index.php");
    exit;
}
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f4f7f6;
    }

    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .navbar {
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="#">
            <span class="text-primary">Kakai</span>One
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
                <?php endif; ?>

                <?php if (in_array($_SESSION['role'], ['admin', 'cashier'])): ?>
                    <li class="nav-item"><a class="nav-link" href="pos.php">POS Checkout</a></li>
                <?php endif; ?>

                <?php if (in_array($_SESSION['role'], ['admin', 'stockman'])): ?>
                    <li class="nav-item"><a class="nav-link" href="inventory.php">Inventory</a></li>
                <?php endif; ?>

                <li class="nav-item"><a class="nav-link" href="activity_log.php">Audit Trail</a></li>
            </ul>
            <div class="d-flex align-items-center">
                <span class="text-light me-3 small">
                    User: <b class="text-info"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></b>
                    (<?php echo ucfirst($_SESSION['role'] ?? 'Guest'); ?>)
                </span>
                <button class="btn btn-outline-danger btn-sm" onclick="logout()">Logout</button>
            </div>
        </div>
    </div>
</nav>