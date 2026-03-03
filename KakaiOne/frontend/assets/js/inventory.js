// frontend/assets/js/inventory.js
document.addEventListener('DOMContentLoaded', loadInventory);

async function loadInventory() {
    try {
        const response = await fetch('../backend/inventory/get_inventory.php');
        const data = await response.json();

        if (data.status === 'error') {
            showAlert('danger', data.message);
            // If access denied, kick them back to login
            if (data.message.includes('Access Denied')) {
                window.location.href = 'index.php';
            }
            return;
        }

        // Populate Wholesale Table
        const wholesaleBody = document.getElementById('wholesaleTableBody');
        wholesaleBody.innerHTML = ''; // Clear loading state
        data.wholesale.forEach(item => {
            wholesaleBody.innerHTML += `
                <tr>
                    <td>${item.sku}</td>
                    <td>${item.name}</td>
                    <td><span class="badge bg-primary fs-6">${item.boxes}</span></td>
                    <td>${item.units_per_box} pcs</td>
                    <td>
                        <button class="btn btn-warning btn-sm" onclick="explodeBox(${item.batch_id}, ${item.product_id})">
                            Explode 1 Box
                        </button>
                    </td>
                </tr>
            `;
        });

        // Populate Retail Table
        const retailBody = document.getElementById('retailTableBody');
        retailBody.innerHTML = '';
        data.retail.forEach(item => {
            retailBody.innerHTML += `
                <tr>
                    <td>${item.sku}</td>
                    <td>${item.name}</td>
                    <td><span class="badge bg-success fs-6">${item.pieces}</span></td>
                </tr>
            `;
        });

    } catch (error) {
        console.error("Error fetching inventory:", error);
        showAlert('danger', 'Failed to communicate with the server.');
    }
}

function showAlert(type, message) {
    const alertBox = document.getElementById('alertBox');
    alertBox.className = `alert alert-${type} mb-4`;
    alertBox.textContent = message;
    alertBox.classList.remove('d-none');
}

function logout() {
    window.location.href = 'index.php';
}

// Placeholder for the next step
function explodeBox(batchId, productId) {
    console.log(`Ready to explode batch ${batchId} for product ${productId}`);
    // Next API call goes here!
}

// Add this to the bottom of frontend/assets/js/inventory.js

async function explodeBox(batchId, productId) {
    // Add a simple confirmation so they don't click it by accident
    if (!confirm("Are you sure you want to open 1 wholesale box and move the pieces to retail?")) {
        return;
    }

    try {
        const response = await fetch('../backend/inventory/explode_box.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                batch_id: batchId,
                product_id: productId
            })
        });

        const data = await response.json();

        if (data.status === 'success') {
            showAlert('success', data.message);
            // Reload the tables to instantly show the updated inventory numbers!
            loadInventory();
        } else {
            showAlert('danger', data.message);
        }

    } catch (error) {
        console.error("Explode Error:", error);
        showAlert('danger', "A network error occurred.");
    }
}