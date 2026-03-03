<?php
session_start();
// Security Gate
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Admin Dashboard</title>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white p-3 h-100">
                    <small class="opacity-75">Total Sales</small>
                    <h2 id="kpiSales" class="fw-bold">₱0.00</h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white p-3 h-100">
                    <small class="opacity-75">Net Profit</small>
                    <h2 id="kpiProfit" class="fw-bold">₱0.00</h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white p-3 h-100">
                    <small class="opacity-75">Critical Stocks</small>
                    <h2 id="kpiCrit" class="fw-bold">0</h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark p-3 h-100">
                    <small class="opacity-75">Expiring (30 Days)</small>
                    <h2 id="kpiExpiring" class="fw-bold">0</h2>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Inventory Intelligence</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Wholesale (Box)</th>
                            <th>Retail (Pcs)</th>
                            <th>Shelf (Pcs)</th>
                            <th>System Recommendation</th>
                        </tr>
                    </thead>
                    <tbody id="stockTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/logs.js"></script>
</body>

</html>