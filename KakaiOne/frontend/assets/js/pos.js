let cart = [];

document.addEventListener('DOMContentLoaded', loadProducts);

async function loadProducts() {
    try {
        const response = await fetch('../backend/sales/get_retail_products.php');
        const data = await response.json();

        if (data.status === 'error') {
            showAlert('danger', data.message);
            if (data.message.includes('Access Denied')) window.location.href = 'index.php';
            return;
        }

        const productList = document.getElementById('productList');
        productList.innerHTML = '';

        data.data.forEach(p => {
            productList.innerHTML += `
                <tr>
                    <td>${p.sku}</td>
                    <td>${p.name}</td>
                    <td>₱${parseFloat(p.price).toFixed(2)}</td>
                    <td><span class="badge bg-success">${p.stock}</span></td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="addToCart(${p.batch_id}, ${p.product_id}, '${p.name}', ${p.price}, ${p.stock})">
                            Add to Cart
                        </button>
                    </td>
                </tr>
            `;
        });
    } catch (error) {
        showAlert('danger', 'Failed to load products.');
    }
}

function addToCart(batchId, productId, name, price, maxStock) {
    const existing = cart.find(item => item.batch_id === batchId);

    if (existing) {
        if (existing.qty >= maxStock) {
            showAlert('warning', `Not enough stock for ${name}`);
            return;
        }
        existing.qty += 1;
    } else {
        cart.push({ batch_id: batchId, product_id: productId, name: name, price: price, qty: 1 });
    }

    renderCart();
}

function renderCart() {
    const cartList = document.getElementById('cartList');
    const cartTotal = document.getElementById('cartTotal');

    cartList.innerHTML = '';
    let total = 0;

    cart.forEach((item, index) => {
        const sub = item.price * item.qty;
        total += sub;

        cartList.innerHTML += `
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="my-0">${item.name}</h6>
                    <small class="text-muted">₱${parseFloat(item.price).toFixed(2)} x ${item.qty}</small>
                </div>
                <div class="d-flex align-items-center">
                    <span class="me-3 fw-bold">₱${sub.toFixed(2)}</span>
                    <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${index})">X</button>
                </div>
            </li>
        `;
    });

    cartTotal.textContent = `₱${total.toFixed(2)}`;
}

function removeFromCart(index) {
    cart.splice(index, 1);
    renderCart();
}

function clearCart() {
    cart = [];
    renderCart();
}

async function processCheckout() {
    if (cart.length === 0) {
        showAlert('warning', 'Cart is empty!');
        return;
    }

    try {
        const response = await fetch('../backend/sales/process_pos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cart: cart })
        });

        const data = await response.json();

        if (data.status === 'success') {
            showAlert('success', data.message);
            clearCart();
            loadProducts(); // Refresh stock numbers
        } else {
            showAlert('danger', data.message);
        }
    } catch (error) {
        showAlert('danger', 'Checkout failed.');
    }
}

function showAlert(type, message) {
    const alertBox = document.getElementById('alertBox');
    alertBox.className = `alert alert-${type} mb-3`;
    alertBox.textContent = message;
    alertBox.classList.remove('d-none');
    setTimeout(() => alertBox.classList.add('d-none'), 4000); // Auto-hide
}

function logout() {
    window.location.href = 'index.php';
}