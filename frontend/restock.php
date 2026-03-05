<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Shelf Restock</title>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Shelf Restock Management</h5>
                <small class="text-white-50">Transfer: Retail Warehouse &rarr; Store Shelf</small>
            </div>
            <div class="card-body">
                <table class="table table-bordered align-middle table-hover">
                    <thead class="table-light">
                        <tr class="text-center align-middle">
                            <th class="text-start" style="width: 25%;">Product</th>
                            <th class="bg-secondary text-white" style="width: 15%;">
                                <small class="d-block fw-light">Location 1</small>
                                Wholesale
                            </th>
                            <th class="bg-info text-dark" style="width: 15%;">
                                <small class="d-block fw-light">Location 2</small>
                                Retail Warehouse
                            </th>
                            <th class="bg-success text-white" style="width: 15%;">
                                <small class="d-block fw-light">Location 3</small>
                                Store Shelf
                            </th>
                            <th style="width: 30%;">Action (Restock Shelf)</th>
                        </tr>
                    </thead>
                    <tbody id="restockTable"></tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', loadRestockData);

        async function loadRestockData() {
            const tbody = document.getElementById('restockTable');
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">Loading stock levels...</td></tr>';

            try {
                const res = await fetch('../backend/inventory/get_shelf_status.php');
                const data = await res.json();

                if (data.status === 'success') {
                    tbody.innerHTML = '';
                    if (data.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center">No products found.</td></tr>';
                        return;
                    }

                    data.data.forEach(item => {
                        const wholesale = parseInt(item.wholesale_qty);
                        const retailWh = parseInt(item.retail_warehouse_qty);
                        const shelf = parseInt(item.shelf_qty);

                        // Disable button if Retail Warehouse is empty
                        const btnDisabled = retailWh <= 0 ? 'disabled' : '';
                        const btnClass = retailWh <= 0 ? 'btn-secondary' : 'btn-primary';

                        tbody.innerHTML += `
                        <tr>
                            <td>
                                <div class="fw-bold">${item.name}</div>
                                <code class="text-muted">${item.sku}</code>
                            </td>
                            <td class="text-center fs-5 text-muted">${wholesale}</td>
                            <td class="text-center fs-5 fw-bold text-primary">${retailWh}</td>
                            <td class="text-center fs-5 fw-bold text-success">${shelf}</td>
                            <td>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Qty</span>
                                    <input type="number" class="form-control" id="qty_${item.product_id}" placeholder="0" min="1" max="${retailWh}">
                                    <button class="btn ${btnClass}" ${btnDisabled} onclick="restock(${item.product_id})">
                                        Move to Shelf &rarr;
                                    </button>
                                </div>
                                ${retailWh <= 0 ? '<small class="text-danger">Retail Warehouse Empty!</small>' : ''}
                            </td>
                        </tr>
                    `;
                    });
                }
            } catch (e) {
                console.error(e);
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Failed to load data.</td></tr>';
            }
        }

        async function restock(id) {
            const qtyInput = document.getElementById(`qty_${id}`);
            const qty = qtyInput.value;

            if (!qty || qty <= 0) {
                Swal.fire('Invalid Qty', 'Please enter a quantity greater than 0.', 'warning');
                return;
            }

            const confirm = await Swal.fire({
                title: 'Restock Shelf?',
                text: `Move ${qty} items from Retail Warehouse to Shelf?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'Yes, Restock'
            });

            if (confirm.isConfirmed) {
                try {
                    // Show loading
                    Swal.fire({
                        title: 'Processing...',
                        didOpen: () => Swal.showLoading()
                    });

                    const res = await fetch('../backend/inventory/process_restock.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            product_id: id,
                            qty: qty
                        })
                    });
                    const result = await res.json();

                    if (result.status === 'success') {
                        await Swal.fire('Success', result.message, 'success');
                        qtyInput.value = ''; // Clear input
                        loadRestockData(); // Refresh table
                    } else {
                        Swal.fire('Error', result.message, 'error');
                    }
                } catch (e) {
                    console.error(e);
                    Swal.fire('Error', 'Transaction failed.', 'error');
                }
            }
        }
    </script>
</body>

</html>