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
    <title>Product Management</title>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Product Catalog</h5>
                <div>
                    <button class="btn btn-outline-success btn-sm me-2" onclick="openCategoryModal()">+ New Category</button>
                    <button class="btn btn-primary btn-sm" onclick="openProductModal()">+ Add Product</button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>SKU</th>
                            <th>Name</th>
                            <th>Cost</th>
                            <th>Price</th>
                            <th>Units/Box</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="productTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="productForm">
                        <input type="hidden" id="p_id">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Category</label>
                            <div class="input-group">
                                <select id="p_category" class="form-select" onchange="autoGenerateSKU()">
                                    <option value="">Select Category...</option>
                                </select>
                                <button class="btn btn-outline-secondary" type="button" onclick="openCategoryModal()">+</button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">SKU</label>
                            <div class="input-group">
                                <input type="text" id="p_sku" class="form-control" placeholder="Select category to generate" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="autoGenerateSKU()">&#x21bb; Next</button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Product Name</label>
                            <input type="text" id="p_name" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">Cost</label>
                                <input type="number" step="0.01" id="p_cost" class="form-control" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">Price</label>
                                <input type="number" step="0.01" id="p_price" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">Units/Box</label>
                                <input type="number" id="p_units" class="form-control" value="1" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">Critical Level</label>
                                <input type="number" id="p_crit" class="form-control" value="10" required>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="saveProduct()">Save Product</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="categoryModal" tabindex="-1" style="z-index: 1060;">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">New Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="categoryForm">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Category Name</label>
                            <input type="text" id="cat_name" class="form-control" placeholder="e.g. Snacks" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Code Prefix</label>
                            <input type="text" id="cat_code" class="form-control text-uppercase" placeholder="e.g. SNK" maxlength="5" required>
                            <small class="text-muted">Max 5 letters (e.g. SNK)</small>
                        </div>
                        <button type="button" class="btn btn-success w-100" onclick="saveCategory()">Add Category</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/products.js"></script>
    <script src="assets/js/logs.js"></script>
</body>

</html>