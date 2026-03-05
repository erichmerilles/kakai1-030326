// frontend/assets/js/analytics.js

document.addEventListener('DOMContentLoaded', loadAnalytics);

async function loadAnalytics() {
    try {
        const response = await fetch('../backend/dashboard/get_analytics.php');
        const data = await response.json();

        if (data.status === 'success') {
            renderTrendChart(data.trends);
            renderCategoryChart(data.categories);
            renderProfitTable(data.product_performance);
        } else {
            console.error("Failed to load analytics:", data.message);
        }
    } catch (error) {
        console.error("Fetch error:", error);
    }
}

// 1. Render Line Chart (Revenue vs Profit)
function renderTrendChart(trends) {
    const ctx = document.getElementById('trendChart').getContext('2d');

    const dates = trends.map(t => t.sale_date);
    const revenue = trends.map(t => parseFloat(t.revenue));
    const profit = trends.map(t => parseFloat(t.profit));

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [
                {
                    label: 'Gross Revenue (₱)',
                    data: revenue,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                },
                {
                    label: 'Net Profit (₱)',
                    data: profit,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }
            ]
        },
        options: { responsive: true, plugins: { legend: { position: 'top' } } }
    });
}

// 2. Render Doughnut Chart (Categories)
function renderCategoryChart(categories) {
    const ctx = document.getElementById('categoryChart').getContext('2d');

    const labels = categories.map(c => c.category_name);
    const profits = categories.map(c => parseFloat(c.profit));

    // Aesthetic colors for the pie chart
    const colors = ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14'];

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: profits,
                backgroundColor: colors.slice(0, labels.length),
                borderWidth: 1
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
}

// 3. Render Detailed Table (ROI & Margin)
function renderProfitTable(products) {
    const tbody = document.getElementById('profitTableBody');
    tbody.innerHTML = '';

    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">No sales data available.</td></tr>';
        return;
    }

    products.forEach(p => {
        const rev = parseFloat(p.total_revenue);
        const cost = parseFloat(p.total_cost);
        const profit = parseFloat(p.total_profit);

        // Calculate Profit Margin: (Profit / Revenue) * 100
        const margin = rev > 0 ? ((profit / rev) * 100).toFixed(1) : 0;

        // Highlight high-margin items in green, low-margin in red
        const marginBadge = margin > 30
            ? `<span class="badge bg-success">${margin}%</span>`
            : `<span class="badge bg-danger">${margin}%</span>`;

        tbody.innerHTML += `
            <tr>
                <td class="ps-4">
                    <div class="fw-bold text-dark">${p.name}</div>
                    <small class="text-muted">${p.sku}</small>
                </td>
                <td class="text-center fw-bold">${p.total_sold}</td>
                <td class="text-end">₱${rev.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                <td class="text-end text-muted">₱${cost.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                <td class="text-end fw-bold text-success">₱${profit.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                <td class="text-end pe-4">${marginBadge}</td>
            </tr>
        `;
    });
}