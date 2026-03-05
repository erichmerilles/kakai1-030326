document.addEventListener('DOMContentLoaded', loadDashboard);

async function loadDashboard() {
    // 1. SAFETY CHECK: Only run this if we are actually on the dashboard
    if (!document.getElementById('kpiSales')) return;

    // 2. DATE LOGIC: Default to "This Month" if inputs are empty
    const today = new Date();
    // First day of current month
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
    // Last day of current month
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
                        actionBadge = `<span class="badge bg-warning text-dark">Explode Wholesale Box</span>`;
                    } else {
                        actionBadge = `<span class="badge bg-danger">Order from Supplier</span>`;
                    }
                } else if (item.shelf_pcs < 10 && item.retail_pcs > 0) {
                    actionBadge = `<span class="badge bg-info text-dark">Restock Shelf from Retail</span>`;
                } else {
                    actionBadge = `<span class="badge bg-success">Stock is Healthy</span>`;
                }

                tbody.innerHTML += `
                    <tr>
                        <td class="fw-bold">${item.name}</td>
                        <td><span class="badge bg-primary fs-6">${item.wholesale_boxes}</span></td>
                        <td>${item.retail_pcs}</td>
                        <td>${item.shelf_pcs}</td>
                        <td>${actionBadge}</td>
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