document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
    loadCategories(); // Load categories when the page starts
});

let productModal;
let categoryModal; // 1. Added variable for the new modal

// 2. Fetch Categories for the Dropdown
async function loadCategories() {
    try {
        const response = await fetch('../backend/catalog/get_categories.php');
        const data = await response.json();

        if (data.status === 'success') {
            const select = document.getElementById('p_category');
            // Keep the default option
            select.innerHTML = '<option value="">Select Category...</option>';

            data.data.forEach(cat => {
                // Store category name in a data attribute for reference if needed
                select.innerHTML += `<option value="${cat.category_id}" data-name="${cat.category_name}">${cat.category_name}</option>`;
            });
        }
    } catch (error) {
        console.error("Failed to load categories", error);
    }
}

// 3. Logic: Auto-Generate SKU (Sequential Ascending)
async function autoGenerateSKU() {
    const categorySelect = document.getElementById('p_category');
    const skuInput = document.getElementById('p_sku');

    const categoryId = categorySelect.value;

    // Only generate if a category is selected
    if (!categoryId) return;

    try {
        // UI Feedback: Show loading state
        skuInput.value = "Generating...";
        skuInput.disabled = true;

        // Call the backend to get the next available number
        const response = await fetch(`../backend/catalog/get_next_sku.php?category_id=${categoryId}`);
        const data = await response.json();

        if (data.status === 'success') {
            skuInput.value = data.sku; // e.g., "SOF-002"
        } else {
            console.error(data.message);
            skuInput.value = ""; // Clear on error
            Swal.fire('Error', 'Could not generate SKU. Please type manually.', 'error');
        }
    } catch (error) {
        console.error("SKU Gen Error:", error);
        skuInput.value = "";
        Swal.fire('Error', 'Network error while generating SKU.', 'error');
    } finally {
        skuInput.disabled = false;
    }
}

// 4. Load Product List
function loadProducts() {
    fetch('../backend/catalog/get_products.php')
        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById('productTableBody');
            tbody.innerHTML = '';

            if (data.status === 'error') {
                console.error(data.message);
                return;
            }

            if (data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">No products found.</td></tr>';
                return;
            }

            data.data.forEach(p => {
                // Ensure numbers are formatted safely
                const cost = p.current_cost_price ? parseFloat(p.current_cost_price).toFixed(2) : '0.00';
                const price = p.current_selling_price ? parseFloat(p.current_selling_price).toFixed(2) : '0.00';

                tbody.innerHTML += `
                    <tr>
                        <td>${p.sku}</td>
                        <td>${p.name}</td>
                        <td>₱${cost}</td>
                        <td>₱${price}</td>
                        <td>${p.units_per_box}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" 
                                onclick='editProduct(${JSON.stringify(p)})'>Edit</button>
                        </td>
                    </tr>
                `;
            });
        })
        .catch(err => console.error("Failed to load products:", err));
}

// 5. Open Modal for Adding Product
function openProductModal() {
    // Reset Form
    document.getElementById('productForm').reset();
    document.getElementById('p_id').value = '';
    document.getElementById('modalTitle').textContent = 'Add New Product';

    // Reset select to default
    document.getElementById('p_category').value = "";

    productModal = new bootstrap.Modal(document.getElementById('productModal'));
    productModal.show();
}

// 6. Open Modal for Editing Product
function editProduct(product) {
    document.getElementById('p_id').value = product.product_id;
    document.getElementById('p_sku').value = product.sku;
    document.getElementById('p_name').value = product.name;
    document.getElementById('p_cost').value = product.current_cost_price;
    document.getElementById('p_price').value = product.current_selling_price;
    document.getElementById('p_units').value = product.units_per_box;
    document.getElementById('p_crit').value = product.critical_level;

    // Set the Category dropdown
    document.getElementById('p_category').value = product.category_id || "";

    document.getElementById('modalTitle').textContent = 'Edit Product';
    productModal = new bootstrap.Modal(document.getElementById('productModal'));
    productModal.show();
}

// 7. Save Product (Insert/Update)
async function saveProduct() {
    const data = {
        product_id: document.getElementById('p_id').value,
        category_id: document.getElementById('p_category').value, // Include Category
        sku: document.getElementById('p_sku').value,
        name: document.getElementById('p_name').value,
        cost: document.getElementById('p_cost').value,
        price: document.getElementById('p_price').value,
        units_per_box: document.getElementById('p_units').value,
        critical: document.getElementById('p_crit').value
    };

    try {
        const response = await fetch('../backend/catalog/save_product.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();

        if (result.status === 'success') {
            Swal.fire('Success', result.message, 'success');
            productModal.hide();
            loadProducts();
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    } catch (error) {
        console.error("Save error:", error);
        Swal.fire('Error', 'Failed to save product.', 'error');
    }
}

// 8. NEW: Open Category Modal
function openCategoryModal() {
    document.getElementById('categoryForm').reset();
    categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));
    categoryModal.show();
}

// 9. NEW: Save Category Function
async function saveCategory() {
    const name = document.getElementById('cat_name').value;
    const code = document.getElementById('cat_code').value;

    if (!name || !code) {
        Swal.fire('Error', 'Please fill in both fields.', 'warning');
        return;
    }

    try {
        const response = await fetch('../backend/catalog/save_category.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: name, code: code })
        });
        const result = await response.json();

        if (result.status === 'success') {
            Swal.fire('Success', result.message, 'success');
            categoryModal.hide();
            loadCategories(); // Refresh the dropdown immediately!
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    } catch (error) {
        console.error("Category Save Error:", error);
        Swal.fire('Error', 'Failed to save category.', 'error');
    }
}