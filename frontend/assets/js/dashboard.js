document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
    loadDashboardNotifications();
});

// --- FETCH NOTIFICATIONS ---
async function loadDashboardNotifications() {
    const notifContainer = document.getElementById('dashboardNotifications');
    if (!notifContainer) return;

    try {
        const res = await fetch('../backend/dashboard/get_notifications.php');
        const data = await res.json();

        if (data.status === 'success' && data.count > 0) {
            notifContainer.innerHTML = '';

            data.data.forEach(notif => {
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
    if (!document.getElementById('kpiSales')) return;

    // Use URLSearchParams to handle date filters more cleanly
    const urlParams = new URLSearchParams(window.location.search);
    const startInput = document.getElementById('dateStart');
    const endInput = document.getElementById('dateEnd');

    if (!startInput.value) {
        const today = new Date();
        startInput.value = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
        endInput.value = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0];
    }

    try {
        const response = await fetch(`../backend/dashboard/get_dashboard_data.php?start=${startInput.value}&end=${endInput.value}`);
        const data = await response.json();

        if (data.status === 'error') {
            if (data.message.includes('Access Denied')) window.location.href = 'index.php';
            console.error("Dashboard Error:", data.message);
            return;
        }

        // POPULATE KPIs with formatting
        document.getElementById('kpiSales').textContent = `₱${parseFloat(data.kpis.total_sales).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
        document.getElementById('kpiProfit').textContent = `₱${parseFloat(data.kpis.net_profit).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
        document.getElementById('kpiCrit').textContent = data.kpis.crit_stocks;
        document.getElementById('kpiExpiring').textContent = data.kpis.expiring_items;

        const topProdEl = document.getElementById('topProduct');
        if (topProdEl) topProdEl.textContent = data.top_product;

        // POPULATE STOCK OVERVIEW TABLE
        const tbody = document.getElementById('stockTableBody');
        if (tbody) {
            tbody.innerHTML = '';

            data.stock_overview.forEach(item => {
                let totalLoose = parseInt(item.retail_pcs) + parseInt(item.shelf_pcs);
                let actionBadge = '';
                let clickableAction = '';

                // Enhanced Active Assistant Logic
                if (totalLoose <= item.critical_level) {
                    if (item.wholesale_boxes > 0) {
                        actionBadge = `<span class="badge bg-warning text-dark px-3 py-2 cursor-pointer" onclick="location.href='inventory.php'">
                                        <i class="bi bi-box-seam me-1"></i> Explode Wholesale Box</span>`;
                    } else {
                        actionBadge = `<span class="badge bg-danger px-3 py-2 cursor-pointer" onclick="location.href='stock_adjustments.php'">
                                        <i class="bi bi-telephone me-1"></i> Order Supplier</span>`;
                    }
                } else if (item.shelf_pcs < 10 && item.retail_pcs > 0) {
                    actionBadge = `<span class="badge bg-info text-dark px-3 py-2 cursor-pointer" onclick="location.href='restock.php'">
                                    <i class="bi bi-arrow-left-right me-1"></i> Restock Shelf</span>`;
                } else {
                    actionBadge = `<span class="badge bg-success px-3 py-2">
                                    <i class="bi bi-check-circle me-1"></i> Healthy</span>`;
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

// --- LOGOUT ---
async function logout() {
    try {
        const res = await fetch('../backend/auth/logout.php');
        const data = await res.json();
        if (data.status === 'success') window.location.href = 'index.php';
    } catch (error) {
        console.error("Logout failed", error);
    }
}