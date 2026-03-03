<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Point of Sale - KakaiOne</title>
</head>

<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container-fluid py-4 px-4">
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold text-dark">Retail Inventory</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr class="text-muted small">
                                    <th>SKU</th>
                                    <th>PRODUCT</th>
                                    <th>PRICE</th>
                                    <th>STOCK</th>
                                    <th>ACTION</th>
                                </tr>
                            </thead>
                            <tbody id="productList"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow border-0 position-sticky" style="top: 80px;">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0 fw-bold">Current Order</h5>
                    </div>
                    <div class="card-body">
                        <ul id="cartList" class="list-group list-group-flush mb-3"></ul>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="h5">Total</span>
                            <span class="h4 text-primary fw-bold" id="cartTotal">₱0.00</span>
                        </div>
                        <button class="btn btn-success w-100 py-3 mb-2" onclick="processCheckout()">Complete Transaction</button>
                        <button class="btn btn-light text-danger w-100" onclick="clearCart()">Clear Cart</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/pos.js"></script>
    <script src="assets/js/logs.js"></script>
</body>

</html>