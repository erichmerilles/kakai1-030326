<?php
session_start();
// RBAC: Only Admin and Stockman can view inventory
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'stockman'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Inventory Management</title>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div id="alertBox" class="alert d-none" role="alert"></div>

        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">📦 Wholesale Storage (Boxes)</h5>
                        <button class="btn btn-light btn-sm text-primary fw-bold">Receive Shipment</button>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>SKU</th>
                                    <th>Product Name</th>
                                    <th>Boxes in Stock</th>
                                    <th>Units per Box</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="wholesaleTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">🏷️ Retail Warehouse (Pieces)</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>SKU</th>
                                    <th>Product Name</th>
                                    <th>Pieces in Stock</th>
                                </tr>
                            </thead>
                            <tbody id="retailTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="receiveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Receive Wholesale Shipment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="receiveForm">
                        <div class="mb-3">
                            <label>Product</label>
                            <select id="rec_product" class="form-select"></select>
                        </div>
                        <div class="mb-3">
                            <label>Quantity (Boxes)</label>
                            <input type="number" id="rec_qty" class="form-control" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label>Expiry Date</label>
                            <input type="date" id="rec_expiry" class="form-control" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="submitReceive()">Confirm Receipt</button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/inventory.js"></script>
    <script src="assets/js/logs.js"></script>
</body>

</html>