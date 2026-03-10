// frontend/assets/js/analytics.js

// Global variables to store chart instances so we can destroy them before redrawing
let trendChartInstance = null;
let categoryChartInstance = null;

document.addEventListener('DOMContentLoaded', loadAnalytics);

async function loadAnalytics() {
    // 1. Read dates from the filter inputs
    const startInput = document.getElementById('filterStart');
    const endInput = document.getElementById('filterEnd');

    let url = '../backend/dashboard/get_analytics.php';

    // Append query parameters if the elements exist
    if (startInput && endInput) {
        url += `?start=${startInput.value}&end=${endInput.value}`;
    }

    try {
        const response = await fetch(url);
        const data = await response.json();

        if (data.status === 'success') {
            renderTrendChart(data.trends);
            renderCategoryChart(data.categories);
            renderProfitTable(data.product_performance);
            renderForecast(data.forecast);
            renderHeatmap(data.categories);
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

    // Destroy existing chart to prevent overlap/glitching when filtering dates
    if (trendChartInstance) {
        trendChartInstance.destroy();
    }

    const dates = trends.map(t => t.sale_date);
    const revenue = trends.map(t => parseFloat(t.revenue));
    const profit = trends.map(t => parseFloat(t.profit));

    trendChartInstance = new Chart(ctx, {
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

    // Destroy existing chart to prevent overlap/glitching
    if (categoryChartInstance) {
        categoryChartInstance.destroy();
    }

    const labels = categories.map(c => c.category_name);
    const profits = categories.map(c => parseFloat(c.profit));

    const colors = ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14'];

    categoryChartInstance = new Chart(ctx, {
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
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">No sales data available for this date range.</td></tr>';
        return;
    }

    products.forEach(p => {
        const rev = parseFloat(p.total_revenue);
        const profit = parseFloat(p.total_profit);
        const cost = parseFloat(p.total_cost);

        const margin = rev > 0 ? ((profit / rev) * 100).toFixed(1) : 0;
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

// 4. Render Predictive Forecast (Low-Stock AI)
function renderForecast(forecasts) {
    const list = document.getElementById('forecastList');
    list.innerHTML = '';

    if (!forecasts || forecasts.length === 0) {
        list.innerHTML = '<li class="list-group-item text-center py-4 text-success"><i class="bi bi-check-circle fs-3 d-block mb-2"></i>Stock levels are healthy!</li>';
        return;
    }

    forecasts.forEach(f => {
        const days = parseInt(f.days_left);
        let badgeClass = days <= 3 ? 'bg-danger' : (days <= 7 ? 'bg-warning text-dark' : 'bg-info');

        list.innerHTML += `
            <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                <div>
                    <div class="fw-bold text-dark">${f.name}</div>
                    <small class="text-muted">Selling ~${f.velocity} / day (Stock: ${f.current_stock})</small>
                </div>
                <div class="text-end">
                    <span class="badge ${badgeClass} fs-6 rounded-pill px-3 py-2 shadow-sm">
                        Runs out in ${days} day${days !== 1 ? 's' : ''}
                    </span>
                </div>
            </li>
        `;
    });
}

// 5. Render ROI Heatmap
function renderHeatmap(categories) {
    const container = document.getElementById('heatmapContainer');
    container.innerHTML = '';

    if (!categories || categories.length === 0) {
        container.innerHTML = '<span class="text-muted">No category data for this date range.</span>';
        return;
    }

    // Map categories to include ROI calculation
    let rois = categories.map(c => {
        let cost = parseFloat(c.total_cost) || 1;
        let profit = parseFloat(c.profit) || 0;
        return {
            name: c.category_name,
            roi: (profit / cost) * 100,
            revenue: parseFloat(c.total_revenue)
        };
    });

    // Sort by ROI performance
    rois.sort((a, b) => b.roi - a.roi);

    rois.forEach(r => {
        let bgColor = '';
        let textColor = 'text-dark';

        // Dynamic color coding for the "Heatmap" look
        if (r.roi >= 100) { bgColor = '#198754'; textColor = 'text-white'; } // Best
        else if (r.roi >= 50) { bgColor = '#20c997'; textColor = 'text-white'; } // Good
        else if (r.roi >= 20) { bgColor = '#a3cfbb'; } // Average
        else if (r.roi >= 0) { bgColor = '#e9ecef'; } // Breakeven
        else { bgColor = '#f8d7da'; textColor = 'text-danger'; } // Loss

        container.innerHTML += `
            <div class="p-3 rounded shadow-sm flex-fill text-center border" 
                 style="background-color: ${bgColor}; min-width: 130px;">
                <div class="fw-bold ${textColor}">${r.name}</div>
                <div class="fs-4 fw-bolder ${textColor}">${r.roi.toFixed(1)}% ROI</div>
                <small class="${textColor} opacity-75">Rev: ₱${r.revenue.toLocaleString('en-PH')}</small>
            </div>
        `;
    });
}