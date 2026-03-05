<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'stockman'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Receive & Adjust Stock | KakaiOne</title>
</head>

<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container py-4 px-4">
        <h3 class="fw-bold mb-4 text-dark"><i class="bi bi-box-seam me-2"></i>Receive & Adjust Stock</h3>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100 border-top border-primary border-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-truck me-2"></i>Receive Supplier Delivery</h5>
                        <small class="text-muted">Adds full boxes to the Wholesale Warehouse.</small>
                    </div>
                    <div class="card-body">
                        <form id="receiveForm" onsubmit="processAdjustment(event, 'receive')">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Select Product</label>
                                <select id="recvProduct" class="form-select product-dropdown" required></select>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-bold small text-muted">Quantity (Boxes)</label>
                                    <input type="number" id="recvQty" class="form-control" min="1" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-bold small text-muted">Batch Expiry Date</label>
                                    <input type="date" id="recvExpiry" class="form-control" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-bold py-2">Log Delivery</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100 border-top border-danger border-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-danger"><i class="bi bi-trash3 me-2"></i>Report Damage/Spoilage</h5>
                        <small class="text-muted">Permanently removes ruined items from inventory.</small>
                    </div>
                    <div class="card-body">
                        <form id="damageForm" onsubmit="processAdjustment(event, 'damage')">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Select Product</label>
                                <select id="dmgProduct" class="form-select product-dropdown" required></select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Location of Damage</label>
                                <select id="dmgLocation" class="form-select" required>
                                    <option value="1">Wholesale Warehouse (Measured in Boxes)</option>
                                    <option value="3">Store Shelf (Measured in Loose Packs)</option>
                                </select>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-bold small text-muted">Quantity Damaged</label>
                                    <input type="number" id="dmgQty" class="form-control" min="1" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-bold small text-muted">Reason</label>
                                    <select id="dmgReason" class="form-select">
                                        <option value="damage">Damaged/Pest</option>
                                        <option value="expired">Expired</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-outline-danger w-100 fw-bold py-2">Deduct from Stock</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', loadDropdowns);

        async function loadDropdowns() {
            try {
                // Reusing your catalog API to get all products
                const res = await fetch('../backend/catalog/get_products.php');
                const data = await res.json();

                if (data.status === 'success') {
                    let options = '<option value="">-- Choose Product --</option>';
                    data.data.forEach(p => {
                        options += `<option value="${p.product_id}">${p.sku} - ${p.name}</option>`;
                    });

                    document.querySelectorAll('.product-dropdown').forEach(select => {
                        select.innerHTML = options;
                    });
                }
            } catch (e) {
                console.error('Failed to load products', e);
            }
        }

        async function processAdjustment(e, actionType) {
            e.preventDefault();

            const isReceive = actionType === 'receive';
            const payload = {
                action: actionType,
                product_id: document.getElementById(isReceive ? 'recvProduct' : 'dmgProduct').value,
                qty: document.getElementById(isReceive ? 'recvQty' : 'dmgQty').value,
            };

            if (isReceive) {
                payload.expiry_date = document.getElementById('recvExpiry').value;
            } else {
                payload.location_id = document.getElementById('dmgLocation').value;
                payload.reason = document.getElementById('dmgReason').value;
            }

            const confirmText = isReceive ? 'Record this delivery?' : 'Permanently delete these items as damaged?';
            const confirmBtnColor = isReceive ? '#0d6efd' : '#dc3545';

            const confirm = await Swal.fire({
                title: 'Are you sure?',
                text: confirmText,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: confirmBtnColor,
                confirmButtonText: 'Yes, proceed'
            });

            if (confirm.isConfirmed) {
                try {
                    Swal.fire({
                        title: 'Processing...',
                        didOpen: () => Swal.showLoading()
                    });

                    const response = await fetch('../backend/inventory/process_adjustment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });
                    const result = await response.json();

                    if (result.status === 'success') {
                        Swal.fire('Success', result.message, 'success');
                        document.getElementById(isReceive ? 'receiveForm' : 'damageForm').reset();
                    } else {
                        Swal.fire('Error', result.message, 'error');
                    }
                } catch (error) {
                    Swal.fire('Error', 'Server failed to process.', 'error');
                }
            }
        }
    </script>
</body>

</html>