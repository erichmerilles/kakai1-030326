document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
    loadDashboardNotifications(); // Fetch alerts on load
});

// --- FETCH NOTIFICATIONS ---
async function loadDashboardNotifications() {
    const notifContainer = document.getElementById('dashboardNotifications');
    if (!notifContainer) return;

    try {
        const res = await fetch('../backend/dashboard/get_notifications.php');
        const data = await res.json();

        if (data.status === 'success' && data.count > 0) {
            notifContainer.innerHTML = ''; // Clear existing

            data.data.forEach(notif => {
                // Determine styling based on type
                const alertType = notif.type === 'danger' ? 'alert-danger' : 'alert-warning';
                const borderClass = notif.type === 'danger' ? 'border-danger' : 'border-warning';

                notifContainer.innerHTML += `
                    <div class="alert ${alertType} alert-dismissible fade show shadow-sm border-0 border-start border-4 ${borderClass} d-flex align-items-center mb-3" role="alert">
                        <i class="bi ${notif.icon} fs-3 me-3"></i>
                        <div>
                            <div class="fw-bold text-uppercase" style="font-size: 0.85rem; letter-spacing: 0.5px;">${notif.title}</div>
                            <div>${notif.message}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
            });
        }
    } catch (e) {
        console.error("Failed to load notifications", e);
    }
}

// --- MAIN DASHBOARD LOAD ---
async function loadDashboard() {
    // 1. SAFETY CHECK: Only run this if we are actually on the dashboard
    if (!document.getElementById('kpiSales')) return;

    // 2. DATE LOGIC: Default to "This Month" if inputs are empty
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0];

    const startInput = document.getElementById('dateStart');
    const endInput = document.getElementById('dateEnd');

    // Set defaults in the UI if empty
    if (!startInput.value) {
        startInput.value = firstDay;
        endInput.value = lastDay;
    }

    const start = startInput.value;
    const end = endInput.value;

    try {
        // 3. FETCH DATA with Date Filters
        const response = await fetch(`../backend/dashboard/get_dashboard_data.php?start=${start}&end=${end}`);
        const data = await response.json();

        if (data.status === 'error') {
            if (data.message.includes('Access Denied')) {
                window.location.href = 'index.php';
            }
            alert(data.message);
            return;
        }

        // 4. POPULATE KPIs
        document.getElementById('kpiSales').textContent = `₱${parseFloat(data.kpis.total_sales).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
        document.getElementById('kpiProfit').textContent = `₱${parseFloat(data.kpis.net_profit).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
        document.getElementById('kpiCrit').textContent = data.kpis.crit_stocks;
        document.getElementById('kpiExpiring').textContent = data.kpis.expiring_items;

        // Populate Top Product
        const topProdEl = document.getElementById('topProduct');
        if (topProdEl) topProdEl.textContent = data.top_product;

        // 5. POPULATE STOCK OVERVIEW TABLE
        const tbody = document.getElementById('stockTableBody');
        if (tbody) {
            tbody.innerHTML = '';

            data.stock_overview.forEach(item => {
                let totalLoose = parseInt(item.retail_pcs) + parseInt(item.shelf_pcs);
                let actionBadge = '';

                // Logic: What should the user do?
                if (totalLoose <= item.critical_level) {
                    if (item.wholesale_boxes > 0) {
                        actionBadge = `<span class="badge bg-warning text-dark px-3 py-2"><i class="bi bi-box-seam me-1"></i> Explode Wholesale Box</span>`;
                    } else {
                        actionBadge = `<span class="badge bg-danger px-3 py-2"><i class="bi bi-telephone me-1"></i> Order Supplier</span>`;
                    }
                } else if (item.shelf_pcs < 10 && item.retail_pcs > 0) {
                    actionBadge = `<span class="badge bg-info text-dark px-3 py-2"><i class="bi bi-arrow-left-right me-1"></i> Restock Shelf</span>`;
                } else {
                    actionBadge = `<span class="badge bg-success px-3 py-2"><i class="bi bi-check-circle me-1"></i> Healthy</span>`;
                }

                tbody.innerHTML += `
                    <tr>
                        <td class="fw-bold ps-4 text-dark">${item.name}</td>
                        <td class="text-center"><span class="badge bg-secondary fs-6">${item.wholesale_boxes}</span></td>
                        <td class="text-center text-primary fw-bold fs-6">${item.retail_pcs}</td>
                        <td class="text-center text-success fw-bold fs-6">${item.shelf_pcs}</td>
                        <td class="pe-4">${actionBadge}</td>
                    </tr>
                `;
            });
        }

    } catch (error) {
        console.error("Dashboard Load Error:", error);
    }
}

// Global Logout Function
async function logout() {
    try {
        await fetch('../backend/auth/logout.php');
        window.location.href = 'index.php';
    } catch (error) {
        console.error("Logout failed", error);
    }
}