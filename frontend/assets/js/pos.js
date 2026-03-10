// frontend/assets/js/pos.js
let cart = [];
let heldCart = []; // Array to temporarily store suspended transactions
let loadedProducts = []; // NEW: Store products globally for the scanner to search

document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
    setupBarcodeScanner(); // Initialize the scanner listener
});

// 1. Load Products (With Separated Wholesale/Shelf Stock Logic)
async function loadProducts() {
    try {
        const response = await fetch('../backend/sales/get_retail_products.php');
        const data = await response.json();

        if (data.status === 'success') {
            loadedProducts = data.data; // Save data for the scanner to search
            const tbody = document.getElementById('productList');
            tbody.innerHTML = '';

            data.data.forEach(p => {
                const shelfStock = parseInt(p.shelf_qty || 0);
                const wholesaleStock = parseInt(p.wholesale_qty || 0);
                const boxSize = parseInt(p.units_per_box);
                const unitName = p.base_unit || 'pcs';

                const packPrice = parseFloat(p.pack_price || p.price);
                const boxPrice = parseFloat(p.box_price || (p.price * boxSize));

                const safeName = p.name.replace(/'/g, "\\'");
                const canSellPack = shelfStock > 0;

                let boxBtnHTML = '';
                if (!boxSize || boxSize <= 0 || isNaN(boxSize)) {
                    boxBtnHTML = `<button class="btn btn-sm btn-outline-secondary w-50" disabled>No Box Size</button>`;
                } else if (wholesaleStock < 1) {
                    boxBtnHTML = `
                        <button class="btn btn-sm btn-outline-secondary w-50" disabled>
                            0 Boxes<br><small>in Wholesale</small>
                        </button>
                    `;
                } else {
                    boxBtnHTML = `
                        <button class="btn btn-sm btn-outline-dark w-50" 
                            onclick="addToCart(${p.product_id}, '${safeName}', ${boxPrice}, 1, 'box', ${wholesaleStock})">
                            Add Box<br>
                            <small>₱${boxPrice.toFixed(2)}</small>
                        </button>
                    `;
                }

                tbody.innerHTML += `
                    <tr>
                        <td>
                            <div class="fw-bold">${p.name}</div>
                            <small class="text-muted">${p.sku}</small>
                        </td>
                        <td>
                            <div class="d-flex flex-column gap-1 text-center">
                                <span class="badge ${shelfStock < 10 ? 'bg-danger' : 'bg-success'} fs-6">
                                    ${shelfStock} ${unitName} (Shelf)
                                </span>
                                <span class="badge ${wholesaleStock < 1 ? 'bg-danger' : 'bg-secondary'} fs-6">
                                    ${wholesaleStock} Boxes (Wholesale)
                                </span>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary w-50" 
                                    ${!canSellPack ? 'disabled' : ''}
                                    onclick="addToCart(${p.product_id}, '${safeName}', ${packPrice}, 1, '${unitName}', ${shelfStock})">
                                    Add ${unitName}<br>
                                    <small>₱${packPrice.toFixed(2)}</small>
                                </button>
                                ${boxBtnHTML}
                            </div>
                            <small class="text-muted d-block text-center mt-1">1 Box = ${boxSize || '?'} ${unitName}</small>
                        </td>
                    </tr>
                `;
            });
        }
    } catch (error) {
        console.error("Error loading products:", error);
    }
}

// 1.5 NEW: Barcode Scanner Listener
function setupBarcodeScanner() {
    const scannerInput = document.getElementById('barcodeScannerInput');
    if (!scannerInput) return;

    scannerInput.addEventListener('keypress', function (e) {
        // Barcode scanners automatically send an 'Enter' keypress (code 13) after reading a code
        if (e.key === 'Enter') {
            e.preventDefault();
            const scannedSKU = this.value.trim().toUpperCase(); // Ensure case matches DB

            // Search the loaded products array for the matching SKU
            const product = loadedProducts.find(p => p.sku.toUpperCase() === scannedSKU);

            if (product) {
                const shelfStock = parseInt(product.shelf_qty || 0);
                const packPrice = parseFloat(product.pack_price || product.price);
                const unitName = product.base_unit || 'pcs';

                // Add 1 pack to the cart automatically
                addToCart(product.product_id, product.name, packPrice, 1, unitName, shelfStock);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Unknown Barcode',
                    text: `SKU '${scannedSKU}' not found in inventory.`,
                    timer: 2000,
                    showConfirmButton: false
                });
            }

            // Clear the input and refocus immediately for the next scan
            this.value = '';
            this.focus();
        }
    });

    // Ensure input stays focused if user clicks elsewhere (optional but helpful for speed)
    document.addEventListener('click', (e) => {
        // Don't steal focus if they are typing in a specific input or clicking a button
        if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'BUTTON') {
            scannerInput.focus();
        }
    });
}

// 2. Add Item to Cart
function addToCart(id, name, price, qtyToAdd, type, currentStock) {
    const displayName = type === 'box' ? `${name} (BOX)` : `${name} (${type})`;

    const existingItem = cart.find(item => item.product_id === id && item.type === type);

    const totalInCart = cart
        .filter(item => item.product_id === id && item.type === type)
        .reduce((sum, item) => sum + item.cart_qty, 0);

    if ((totalInCart + 1) > currentStock) {
        Swal.fire('Insufficient Stock', `You only have ${currentStock} available in this location.`, 'warning');
        return;
    }

    if (existingItem) {
        existingItem.cart_qty++;
    } else {
        cart.push({
            product_id: id,
            name: displayName,
            price: parseFloat(price),
            cart_qty: 1,
            qty_per_unit: qtyToAdd,
            type: type
        });
    }

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1000,
        timerProgressBar: true
    });
    Toast.fire({ icon: 'success', title: `Added ${displayName}` });

    renderCart();
}

// 3. Render Cart UI
function renderCart() {
    const cartList = document.getElementById('cartList');
    const cartTotal = document.getElementById('cartTotal');
    const cartCount = document.getElementById('cartCount');

    cartList.innerHTML = '';
    let total = 0;
    let totalItems = 0;

    if (cart.length === 0) {
        cartList.innerHTML = '<li class="list-group-item text-center text-muted py-4"><i class="bi bi-cart-x display-4 d-block mb-2"></i>Cart is empty</li>';
        cartTotal.textContent = '₱0.00';
        if (cartCount) cartCount.textContent = '0 items';
        return;
    }

    cart.forEach((item, index) => {
        const subtotal = item.price * item.cart_qty;
        total += subtotal;
        totalItems += item.cart_qty;

        cartList.innerHTML += `
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="my-0">${item.name}</h6>
                    <small class="text-muted">
                        ₱${item.price.toFixed(2)} x ${item.cart_qty} 
                    </small>
                </div>
                <div class="d-flex align-items-center">
                    <span class="text-primary fw-bold me-3">₱${subtotal.toFixed(2)}</span>
                    <button class="btn btn-sm btn-outline-danger py-0 px-2" onclick="removeFromCart(${index})">&times;</button>
                </div>
            </li>
        `;
    });

    cartTotal.textContent = `₱${total.toFixed(2)}`;
    if (cartCount) cartCount.textContent = `${totalItems} items`;
}

// 4. Remove Item
function removeFromCart(index) {
    cart.splice(index, 1);
    renderCart();
}

// 5. Clear Cart
function clearCart() {
    if (cart.length === 0) return;

    Swal.fire({
        title: 'Clear Cart?',
        text: "Remove all items?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, clear it'
    }).then((result) => {
        if (result.isConfirmed) {
            cart = [];
            renderCart();
        }
    });
}

// 6. Process Checkout
async function processCheckout() {
    if (cart.length === 0) {
        Swal.fire('Empty Cart', 'Please add items.', 'warning');
        return;
    }

    const confirm = await Swal.fire({
        title: 'Confirm Sale',
        text: `Total: ${document.getElementById('cartTotal').textContent}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Pay Now'
    });

    if (confirm.isConfirmed) {
        const backendCart = cart.map(item => {
            return {
                product_id: item.product_id,
                cart_qty: item.cart_qty,
                price: item.price,
                type: item.type
            };
        });

        try {
            Swal.fire({ title: 'Processing...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            const response = await fetch('../backend/sales/process_pos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cart: backendCart })
            });
            const result = await response.json();

            if (result.status === 'success') {
                Swal.close();
                buildReceipt(result.receipt_no, cart, document.getElementById('cartTotal').textContent);
                const receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));
                receiptModal.show();
            } else {
                Swal.fire('Error', result.message, 'error');
            }
        } catch (error) {
            console.error(error);
            Swal.fire('Error', 'Transaction failed.', 'error');
        }
    }
}

// 7. Build the Receipt HTML
function buildReceipt(receiptNo, currentCart, totalAmount) {
    document.getElementById('receiptNumber').textContent = receiptNo;
    document.getElementById('receiptDate').textContent = new Date().toLocaleString('en-PH');
    document.getElementById('receiptTotalDue').textContent = totalAmount;

    const receiptItemsContainer = document.getElementById('receiptItems');
    receiptItemsContainer.innerHTML = '';

    currentCart.forEach(item => {
        const subtotal = item.price * item.cart_qty;
        const shortName = item.name.length > 15 ? item.name.substring(0, 15) + '..' : item.name;

        receiptItemsContainer.innerHTML += `
            <div class="receipt-item">
                <div class="receipt-item-name">${shortName}</div>
                <div class="receipt-item-qty">${item.cart_qty}</div>
                <div class="receipt-item-price">${subtotal.toFixed(2)}</div>
            </div>
        `;
    });
}

// 8. Close Receipt and Reset POS
function closeReceiptAndReset() {
    const modalEl = document.getElementById('receiptModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();

    cart = [];
    renderCart();
    loadProducts(); // Refresh stock numbers
}

// 9. Legacy Alert Wrapper
function showAlert(type, message) {
    Swal.fire({
        icon: type === 'danger' ? 'error' : type,
        title: message,
        timer: 2000,
        showConfirmButton: false
    });
}

// 10. Suspend Transaction (Hold Cart)
function holdCart() {
    if (cart.length === 0) {
        Swal.fire('Cart is Empty', 'There is nothing to hold.', 'info');
        return;
    }

    if (heldCart.length > 0) {
        Swal.fire('Hold Limit Reached', 'You already have a cart on hold. Please resume or clear it first.', 'warning');
        return;
    }

    heldCart = [...cart];
    cart = [];
    renderCart();

    document.getElementById('btnRestore').classList.remove('d-none');

    Swal.fire({
        icon: 'success',
        title: 'Transaction Suspended',
        text: 'Cart held. You can now serve the next customer.',
        timer: 2000,
        showConfirmButton: false
    });
}

// 11. Resume Suspended Transaction
function restoreCart() {
    if (cart.length > 0) {
        Swal.fire('Active Cart', 'Please clear or checkout the current cart before resuming the held transaction.', 'warning');
        return;
    }

    if (heldCart.length === 0) return;

    cart = [...heldCart];
    heldCart = [];
    renderCart();

    document.getElementById('btnRestore').classList.add('d-none');
}