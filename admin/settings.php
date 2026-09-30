<?php
/**
 * Administrator Config Settings Panel
 */
require_once __DIR__ . '/../config/config.php';

// Authentication Check
// Authentication Check
$isAdmin = isset($_SESSION['admin_id']) || ((isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'));
if (!$isAdmin) {
    header('Location: ../login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Market - Configuración</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-body">

    <div class="admin-wrapper">
        
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar">
            <a href="dashboard.php" class="admin-logo">
                <span>🛡️</span> Modo <span>Admin</span>
            </a>
            <ul class="admin-menu">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="users.php">👥 Control de Usuarios</a></li>
                <li><a href="products.php">🛍️ Moderar Productos</a></li>
                <li><a href="schools.php">🏫 Datos del Colegio</a></li>
                <li><a href="reports.php">⚠️ Reportes y Alertas</a></li>
                <li class="active"><a href="settings.php">⚙️ Configuración</a></li>
                <li style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
                    <a href="../index.html" style="color: #60a5fa; font-weight: 700;">
                        <span>🛍️</span> Ver Tienda Escolar
                    </a>
                </li>
            </ul>
            <div class="admin-logout-btn">
                <a href="#" id="adminLogoutLink" style="color: #ef4444; font-weight:600; text-decoration:none; display:flex; align-items:center; gap:10px; padding:10px 16px;">
                    <span>🚪</span> Cerrar Sesión
                </a>
            </div>
        </aside>

        <!-- Main Content Panel -->
        <main class="admin-main">
            
            <header class="admin-header">
                <div class="admin-title">
                    <h1>Configuration Settings</h1>
                    <p>Adjust platform fees, commission rates, and review revenue reports</p>
                </div>
            </header>

            <div class="settings-grid">
                
                <!-- Commission settings Widget -->
                <div class="settings-box">
                    <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Commission Fee Configuration</h2>
                    
                    <form id="commissionForm">
                        <div class="form-group" style="margin-bottom: 24px;">
                            <label for="commissionRate">Platform Transaction Fee (%)</label>
                            <input type="number" id="commissionRate" name="commission_percentage" class="form-control" placeholder="5" min="0" max="9.9" step="0.1" required>
                            <span style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Important: Medellín School Market rules require commission fees to be strictly LESS than 10%. Default is 5.0%.</span>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%;">Apply Fee Changes</button>
                    </form>
                </div>

                <!-- Summary statistics of income -->
                <div class="settings-box" style="display:flex; flex-direction:column; gap:20px;">
                    <h2 style="font-size: 18px; font-weight: 700; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Revenues Summary</h2>
                    
                    <div style="display:flex; flex-direction:column; gap:15px;">
                        <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:15px; border-radius:8px;">
                            <span style="font-size:12px; color:var(--text-muted); text-transform:uppercase; font-weight:600;">Sales Volume (Total COP Traded)</span>
                            <h3 style="font-size:24px; font-weight:800; color:#0f172a; margin-top:4px;" id="revVolumeText">$0 COP</h3>
                        </div>
                        <div style="background:#f0fdf4; border:1px solid #bbf7d0; padding:15px; border-radius:8px;">
                            <span style="font-size:12px; color:#166534; text-transform:uppercase; font-weight:600;">Net Platform Revenue</span>
                            <h3 style="font-size:24px; font-weight:800; color:#15803d; margin-top:4px;" id="revRevenueText">$0 COP</h3>
                        </div>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <!-- Core Scripts -->
    <script src="../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            loadConfig();

            // Bind commission updates
            document.getElementById('commissionForm').addEventListener('submit', (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);

                fetch('../controllers/AdminController.php?action=commission-save', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        showToast(res.message);
                        loadConfig();
                    } else {
                        showToast(res.message, 'error');
                    }
                });
            });

            // Bind admin logout
            document.getElementById('adminLogoutLink').addEventListener('click', (e) => {
                e.preventDefault();
                fetch('../controllers/AuthController.php?action=logout')
                    .then(r => r.json())
                    .then(res => {
                        localStorage.removeItem('sm_current_user_id');
                        window.location.href = '../select-school.html';
                    })
                    .catch(() => {
                        window.location.href = '../select-school.html';
                    });
            });
        });

        function loadConfig() {
            fetch('../controllers/AdminController.php?action=stats')
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        const d = res.data;
                        document.getElementById('commissionRate').value = d.commission_percentage;
                        
                        const cop = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });
                        document.getElementById('revVolumeText').innerText = cop.format(d.sales_volume);
                        document.getElementById('revRevenueText').innerText = cop.format(d.total_revenue);
                    }
                });
        }
    </script>
</body>
</html>
