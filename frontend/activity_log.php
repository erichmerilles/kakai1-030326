<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>System Audit Trail</title>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container">

        <div class="row mb-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Start Date</label>
                <input type="date" id="dateStart" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">End Date</label>
                <input type="date" id="dateEnd" class="form-control">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" onclick="loadLogs()">
                    <i class="bi bi-filter"></i> Filter Logs
                </button>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Recent Activities</h5>
                <span class="badge bg-secondary" id="recordCount">Loading...</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action Type</th>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>From</th>
                                <th>To</th>
                            </tr>
                        </thead>
                        <tbody id="logTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/js/logs.js"></script>
</body>

</html>