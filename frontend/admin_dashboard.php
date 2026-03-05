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
    <title>Admin Dashboard | KakaiOne</title>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-3">
        <div class="row mb-4 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Start Date</label>
                <input type="date" id="dateStart" class="form-control shadow-sm">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">End Date</label>
                <input type="date" id="dateEnd" class="form-control shadow-sm">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100 fw-bold shadow-sm" onclick="loadDashboard()">
                    <i class="bi bi-filter"></i> Filter Data
                </button>
            </div>
        </div>

        <div id="dashboardNotifications"></div>

        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white p-3 h-100 shadow border-0">
                    <small class="opacity-75 text-uppercase fw-bold">Total Sales</small>
                    <h2 id="kpiSales" class="fw-bold mt-2 mb-0">₱0.00</h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white p-3 h-100 shadow border-0">
                    <small class="opacity-75 text-uppercase fw-bold">Net Profit</small>
                    <h2 id="kpiProfit" class="fw-bold mt-2 mb-0">₱0.00</h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white p-3 h-100 shadow border-0">
                    <small class="opacity-75 text-uppercase fw-bold">Critical Stocks</small>
                    <h2 id="kpiCrit" class="fw-bold mt-2 mb-0">0</h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark p-3 h-100 shadow border-0">
                    <small class="opacity-75 text-uppercase fw-bold">Expiring (30 Days)</small>
                    <h2 id="kpiExpiring" class="fw-bold mt-2 mb-0">0</h2>
                </div>
            </div>
        </div>

        <div class="alert alert-info shadow-sm d-flex align-items-center border-0 border-start border-info border-4" role="alert">
            <i class="bi bi-trophy-fill fs-4 me-3 text-info"></i>
            <div>
                <span class="fw-bold me-1">Top Selling Product (Selected Period):</span>
                <span id="topProduct">Loading...</span>
            </div>
        </div>

        <div class="card shadow-sm border-0 mt-2">
            <div class="card-header bg-white py-3 border-bottom-0">
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-boxes me-2 text-primary"></i>Inventory Intelligence</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Product Name</th>
                                <th class="text-center">Wholesale (Box)</th>
                                <th class="text-center">Retail (Pcs)</th>
                                <th class="text-center">Shelf (Pcs)</th>
                                <th class="pe-4">System Recommendation</th>
                            </tr>
                        </thead>
                        <tbody id="stockTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/logs.js"></script>
</body>

</html>