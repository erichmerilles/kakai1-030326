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

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body d-flex align-items-center justify-content-between py-2">
                <h6 class="mb-0 fw-bold text-muted">Filter Range:</h6>
                <div class="d-flex gap-2">
                    <input type="date" id="filterStart" class="form-control form-control-sm" value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>">
                    <input type="date" id="filterEnd" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>">
                    <button class="btn btn-primary btn-sm px-3" onclick="loadAnalytics()">
                        <i class="bi bi-filter"></i> Apply
                    </button>
                </div>
            </div>
        </div>

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

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100 border-start border-warning border-4">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-robot text-warning me-2"></i>Sales Velocity Forecast</h6>
                        <small class="text-muted">Predicts run-out dates based on 30-day sales velocity.</small>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush" id="forecastList">
                            <li class="list-group-item text-center py-4 text-muted">Calculating AI predictions...</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100 border-start border-success border-4">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-fire text-danger me-2"></i>Category ROI Heatmap</h6>
                        <small class="text-muted">Analyzes Return on Investment (Profit ÷ Cost). Darker green = Better ROI.</small>
                    </div>
                    <div class="card-body d-flex flex-wrap gap-2 align-items-center justify-content-center" id="heatmapContainer">
                        <span class="text-muted">Loading Heatmap...</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white py-3 border-0">
                <h6 class="mb-0 fw-bold"><i class="bi bi-table me-2"></i>Detailed Product Margin Report</h6>
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