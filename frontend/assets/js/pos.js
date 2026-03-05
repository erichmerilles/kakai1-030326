let cart = [];

document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
});

// 1. Load Products from Database
async function loadProducts() {
    try {
        const response = await fetch('../backend/sales/get_retail_products.php');
        const data = await response.json();

        if (data.status === 'success') {
            const tbody = document.getElementById('productList');
            tbody.innerHTML = '';

            data.data.forEach(p => {
                // Determine button state based on stock
                const isOutOfStock = p.qty <= 0;
                const btnState = isOutOfStock ? 'disabled' : '';
                const btnClass = isOutOfStock ? 'btn-secondary' : 'btn-primary';
                const btnText = isOutOfStock ? 'Out of Stock' : 'Add';

                tbody.innerHTML += `
                    <tr>
                        <td><small class="text-muted">${p.sku}</small></td>
                        <td class="fw-bold">${p.name}</td>
                        <td>₱${parseFloat(p.price).toFixed(2)}</td>
                        <td>
                            <span class="badge ${p.qty < 10 ? 'bg-danger' : 'bg-success'}">
                                ${p.qty}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm ${btnClass}" ${btnState} 
                                onclick="addToCart(${p.product_id}, '${p.name}', ${p.price}, ${p.qty})">
                                ${btnText}
                            </button>
                        </td>
                    </tr>
                `;
            });
        }
    } catch (error) {
        console.error("Error loading products:", error);
    }
}

// 2. Add Item to Cart
function addToCart(id, name, price, maxStock) {
    // Check if product is already in cart
    const existingItem = cart.find(item => item.product_id === id);

    if (existingItem) {
        if (existingItem.qty < maxStock) {
            existingItem.qty++;
        } else {
            Swal.fire('Stock Limit', 'Cannot add more than available stock.', 'warning');
            return;
        }
    } else {
        cart.push({
            product_id: id,
            name: name,
            price: parseFloat(price),
            qty: 1,
            max: maxStock
        });
    }

    // Toast Notification for better UX
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1500,
        timerProgressBar: true
    });
    Toast.fire({ icon: 'success', title: `Added ${name}` });

    renderCart();
}

// 3. Render Cart UI
function renderCart() {
    const cartList = document.getElementById('cartList');
    const cartTotal = document.getElementById('cartTotal');

    cartList.innerHTML = '';
    let total = 0;

    if (cart.length === 0) {
        cartList.innerHTML = '<li class="list-group-item text-center text-muted">Cart is empty</li>';
        cartTotal.textContent = '₱0.00';
        return;
    }

    cart.forEach((item, index) => {
        const subtotal = item.price * item.qty;
        total += subtotal;

        cartList.innerHTML += `
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="my-0">${item.name}</h6>
                    <small class="text-muted">₱${item.price.toFixed(2)} x ${item.qty}</small>
                </div>
                <div class="d-flex align-items-center">
                    <span class="text-primary fw-bold me-3">₱${subtotal.toFixed(2)}</span>
                    <button class="btn btn-sm btn-outline-danger py-0 px-2" onclick="removeFromCart(${index})">&times;</button>
                </div>
            </li>
        `;
    });

    cartTotal.textContent = `₱${total.toFixed(2)}`;
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

// 6. Process Checkout (THE FIX IS HERE)
async function processCheckout() {
    if (cart.length === 0) {
        Swal.fire('Empty Cart', 'Please add items before checking out.', 'warning');
        return;
    }

    // Confirm Sale
    const confirm = await Swal.fire({
        title: 'Complete Sale?',
        text: `Total: ${document.getElementById('cartTotal').textContent}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        confirmButtonText: 'Yes, Process Payment'
    });

    if (confirm.isConfirmed) {
        try {
            // Show Loading
            Swal.fire({ title: 'Processing...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            const response = await fetch('../backend/sales/process_pos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cart: cart })
            });
            const result = await response.json();

            if (result.status === 'success') {
                await Swal.fire('Success!', `Transaction Complete.\nRef: ${result.receipt_no || 'N/A'}`, 'success');
                cart = [];
                renderCart();
                loadProducts(); // Refresh stock counts
            } else {
                Swal.fire('Failed', result.message, 'error');
            }
        } catch (error) {
            console.error(error);
            Swal.fire('Error', 'Transaction failed. Check console.', 'error');
        }
    }
}

// 7. Wrapper to prevent crashes if old code calls showAlert
function showAlert(type, message) {
    Swal.fire({
        icon: type === 'danger' ? 'error' : type,
        title: message,
        timer: 2000,
        showConfirmButton: false
    });
}