<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Profitability Analytics | KakaiOne</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container py-4 px-4">
        <h3 class="fw-bold mb-4 text-dark"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Profitability Analytics</h3>

        <div class="row g-4 mb-4">
            <div class="col-md-8">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="mb-0 fw-bold">7-Day Revenue vs Profit Trend</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="trendChart" height="100"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="mb-0 fw-bold">Profit by Category</h6>
                    </div>
                    <div class="card-body d-flex justify-content-center">
                        <canvas id="categoryChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white py-3 border-0">
                <h6 class="mb-0 fw-bold"><i class="bi bi-table me-2"></i>Detailed Product ROI Report</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">SKU / Product</th>
                                <th class="text-center">Units Sold</th>
                                <th class="text-end">Total Revenue</th>
                                <th class="text-end">Total Cost</th>
                                <th class="text-end text-success">Net Profit</th>
                                <th class="text-end pe-4">Profit Margin</th>
                            </tr>
                        </thead>
                        <tbody id="profitTableBody">
                            <tr>
                                <td colspan="6" class="text-center py-4">Loading analytics data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/analytics.js"></script>
</body>

</html>