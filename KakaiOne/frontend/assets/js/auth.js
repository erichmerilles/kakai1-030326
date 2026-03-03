// frontend/assets/js/auth.js
document.getElementById('loginForm').addEventListener('submit', async function (e) {
    e.preventDefault(); // Prevent default page reload

    const formData = new FormData(this);
    const alertBox = document.getElementById('loginAlert');

    try {
        // Send data to the PHP backend
        const response = await fetch('../backend/auth/login.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.status === 'success') {
            // RBAC Routing: Redirect users based on their role
            alertBox.classList.add('d-none');

            switch (result.role) {
                case 'admin':
                    window.location.href = 'admin_dashboard.html';
                    break;
                case 'cashier':
                    window.location.href = 'pos.html';
                    break;
                case 'stockman':
                    window.location.href = 'inventory.html'; // Default stockman view
                    break;
                case 'customer':
                    window.location.href = 'customer_page.html';
                    break;
                default:
                    window.location.href = 'index.html'; // Fallback for 'staff'
            }
        } else {
            // Show error message
            alertBox.textContent = result.message;
            alertBox.classList.remove('d-none');
        }
    } catch (error) {
        alertBox.textContent = "An error occurred connecting to the server.";
        alertBox.classList.remove('d-none');
        console.error("Login Error:", error);
    }
});