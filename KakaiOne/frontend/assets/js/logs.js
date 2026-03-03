document.addEventListener('DOMContentLoaded', loadLogs);

async function loadLogs() {
    try {
        const response = await fetch('../backend/logs/get_logs.php');
        const data = await response.json();

        if (data.status === 'error') {
            alert(data.message);
            window.location.href = 'index.php';
            return;
        }

        // UI Polish: Show the dashboard button only if the user is an admin
        if (data.role === 'admin') {
            document.getElementById('btnDashboard').classList.remove('d-none');
        }

        const tbody = document.getElementById('logTableBody');
        tbody.innerHTML = '';

        if (data.data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4">No activity logged yet.</td></tr>`;
            return;
        }

        data.data.forEach(log => {
            // Format the date nicely
            const dateObj = new Date(log.movement_date);
            const formattedDate = dateObj.toLocaleString();

            // Color-code the actions for UI Polish
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

            // Handle NULL locations cleanly
            const fromLoc = log.from_location ? log.from_location : '<span class="text-muted">Supplier/System</span>';
            const toLoc = log.to_location ? log.to_location : '<span class="text-muted">Customer/Loss</span>';

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

// The new, secure logout function
async function logout() {
    try {
        await fetch('../backend/auth/logout.php');
        window.location.href = 'index.php';
    } catch (error) {
        console.error("Logout failed", error);
    }
}