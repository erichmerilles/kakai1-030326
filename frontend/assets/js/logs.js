document.addEventListener('DOMContentLoaded', loadLogs);

async function loadLogs() {
    const tbody = document.getElementById('logTableBody');
    if (!tbody) return;

    // 1. Set Date Defaults (Last 30 Days)
    const today = new Date();
    const last30 = new Date();
    last30.setDate(today.getDate() - 30);

    const dateStartInput = document.getElementById('dateStart');
    const dateEndInput = document.getElementById('dateEnd');

    // Only set default values if they are empty
    if (!dateStartInput.value) {
        dateStartInput.value = last30.toISOString().split('T')[0];
        dateEndInput.value = today.toISOString().split('T')[0];
    }

    const start = dateStartInput.value;
    const end = dateEndInput.value;

    try {
        // 2. Fetch with Date Parameters
        const response = await fetch(`../backend/logs/get_logs.php?start=${start}&end=${end}`);
        const data = await response.json();

        if (data.status === 'error') {
            console.error(data.message);
            return;
        }

        tbody.innerHTML = '';

        // Update the badge count
        const countBadge = document.getElementById('recordCount');
        if (countBadge) countBadge.textContent = `${data.data.length} Records`;

        if (data.data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">No activity found for this period.</td></tr>`;
            return;
        }

        data.data.forEach(log => {
            const dateObj = new Date(log.movement_date);
            const formattedDate = dateObj.toLocaleString();

            let actionBadge = '';
            switch (log.movement_type) {
                case 'sale':
                    actionBadge = `<span class="badge bg-success">POS Sale</span>`;
                    break;
                case 'explode':
                    actionBadge = `<span class="badge bg-warning text-dark">Box Breakdown</span>`;
                    break;
                case 'receive':
                    actionBadge = `<span class="badge bg-primary">Received Stock</span>`;
                    break;
                default:
                    actionBadge = `<span class="badge bg-secondary">${log.movement_type.toUpperCase()}</span>`;
            }

            const fromLoc = log.from_location ? log.from_location : '<span class="text-muted small">System</span>';
            const toLoc = log.to_location ? log.to_location : '<span class="text-muted small">Loss/Customer</span>';

            tbody.innerHTML += `
                <tr>
                    <td>${formattedDate}</td>
                    <td class="fw-bold">${log.username}</td>
                    <td>${actionBadge}</td>
                    <td>${log.product_name}</td>
                    <td class="fw-bold text-center">${log.quantity}</td>
                    <td>${fromLoc}</td>
                    <td>${toLoc}</td>
                </tr>
            `;
        });

    } catch (error) {
        console.error("Failed to load logs:", error);
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