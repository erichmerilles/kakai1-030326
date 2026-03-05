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
                                    <th style="min-width: 200px;">ACTION</th>
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
                        <ul id="cartList" class="list-group list-group-flush mb-3" style="max-height: 400px; overflow-y: auto;"></ul>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="h5">Total</span>
                            <span class="h4 text-primary fw-bold" id="cartTotal">₱0.00</span>
                        </div>
                        <button class="btn btn-success w-100 py-3 mb-2 fw-bold" onclick="processCheckout()">Complete Transaction</button>
                        <button class="btn btn-light text-danger w-100" onclick="clearCart()">Clear Cart</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-success"><i class="bi bi-check-circle-fill me-2"></i>Payment Success</h5>
                </div>
                <div class="modal-body bg-light d-flex justify-content-center">

                    <div class="receipt-wrapper" id="receiptPrintArea">
                        <div class="receipt-header">
                            <h4>KAKAI'S STORE</h4>
                            <p>Wholesale & Retail Trading</p>
                            <p id="receiptDate"></p>
                            <p>Receipt No: <span id="receiptNumber"></span></p>
                        </div>

                        <div class="receipt-divider"></div>
                        <div class="receipt-item fw-bold">
                            <div class="receipt-item-name">ITEM</div>
                            <div class="receipt-item-qty">QTY</div>
                            <div class="receipt-item-price">AMT</div>
                        </div>
                        <div class="receipt-divider"></div>

                        <div id="receiptItems"></div>

                        <div class="receipt-divider"></div>
                        <div class="receipt-total">
                            <span>TOTAL DUE:</span>
                            <span id="receiptTotalDue"></span>
                        </div>
                        <div class="receipt-divider"></div>

                        <div class="receipt-footer">
                            <p>Thank you for shopping with us!</p>
                            <p>System by KakaiOne POS</p>
                        </div>
                    </div>

                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" onclick="closeReceiptAndReset()">New Transaction</button>
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/pos.js"></script>
    <script src="assets/js/logs.js"></script>
</body>

</html>