document.addEventListener('DOMContentLoaded', loadDashboard);

async function loadDashboard() {
    try {
        const response = await fetch('../backend/dashboard/get_dashboard_data.php');
        const data = await response.json();

        if (data.status === 'error') {
            if (data.message.includes('Access Denied')) {
                window.location.href = 'index.php'; // Changed from index.html
            }
            alert(data.message);
            return;
        }

        // 1. Populate KPIs
        document.getElementById('kpiSales').textContent = `₱${parseFloat(data.kpis.total_sales).toFixed(2)}`;
        document.getElementById('kpiProfit').textContent = `₱${parseFloat(data.kpis.net_profit).toFixed(2)}`;
        document.getElementById('kpiCrit').textContent = data.kpis.crit_stocks;
        document.getElementById('kpiExpiring').textContent = data.kpis.expiring_items;
        document.getElementById('topProduct').textContent = data.top_product;

        // 2. Populate Stock Overview Table with Intelligent Actions
        const tbody = document.getElementById('stockTableBody');
        tbody.innerHTML = '';

        data.stock_overview.forEach(item => {
            let totalLoose = parseInt(item.retail_pcs) + parseInt(item.shelf_pcs);
            let actionBadge = '';

            // Capstone Panel Logic: What should the user do?
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

    } catch (error) {
        console.error("Dashboard Load Error:", error);
    }
}

function logout() {
    window.location.href = 'index.php';
}