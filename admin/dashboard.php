<?php
/**
 * Administrator Dashboard View - Institutional Admin Mode
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Authentication Gate: only users with admin role or admin_id
$isAdmin = isset($_SESSION['admin_id']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
if (!$isAdmin) {
    header('Location: ../login.html');
    exit;
}

$db = Database::getConnection();
$adminSchoolId = $_SESSION['admin_school_id'] ?? $_SESSION['school_id'] ?? null;
$schoolName = 'Todas las Instituciones';
if ($adminSchoolId) {
    $st = $db->prepare("SELECT school_name FROM schools WHERE id = :sid");
    $st->execute([':sid' => $adminSchoolId]);
    $schoolName = $st->fetchColumn() ?: 'Institución Educativa';
}
$adminUsername = $_SESSION['admin_username'] ?? $_SESSION['user_name'] ?? 'Administrador';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Market - Modo Administrador (<?php echo htmlspecialchars($schoolName); ?>)</title>
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
            <div style="padding: 10px 16px 14px; font-size: 11px; color: #94a3b8; border-bottom: 1px solid rgba(255,255,255,0.08); margin-bottom: 10px;">
                <i class="fa-solid fa-school" style="color: #60a5fa;"></i> <?php echo htmlspecialchars($schoolName); ?>
            </div>
            <ul class="admin-menu">
                <li class="active"><a href="dashboard.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
                <li><a href="users.php"><i class="fa-solid fa-users"></i> Control de Usuarios</a></li>
                <li><a href="products.php"><i class="fa-solid fa-boxes-stacked"></i> Moderar Productos</a></li>
                <li><a href="schools.php"><i class="fa-solid fa-school"></i> Datos del Colegio</a></li>
                <li><a href="reports.php"><i class="fa-solid fa-triangle-exclamation"></i> Reportes y Alertas</a></li>
                <li><a href="settings.php"><i class="fa-solid fa-gear"></i> Configuración</a></li>
                <li style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
                    <a href="../index.html" style="color: #60a5fa; font-weight: 700;">
                        <i class="fa-solid fa-store"></i> Ver Tienda Escolar
                    </a>
                </li>
            </ul>
            <div class="admin-logout-btn">
                <a href="#" id="adminLogoutLink" style="color: #ef4444; font-weight:600; text-decoration:none; display:flex; align-items:center; gap:10px; padding:10px 16px;">
                    <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión
                </a>
            </div>
        </aside>

        <!-- Main Content Panel -->
        <main class="admin-main">
            
            <header class="admin-header">
                <div class="admin-title">
                    <h1 style="display: flex; align-items: center; gap: 10px;">
                        <span>Panel Institucional</span>
                    </h1>
                    <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">
                        Administración y moderación de <strong><?php echo htmlspecialchars($schoolName); ?></strong>
                    </p>
                </div>
                <div class="admin-user-info" style="display: flex; align-items: center; gap: 12px;">
                    <a href="../index.html" class="btn btn-primary" style="font-size: 13px; padding: 6px 14px; border-radius: 50px; text-decoration: none;">
                        <i class="fa-solid fa-arrow-left"></i> Ir al Catálogo
                    </a>
                    <span style="font-size: 13px; color: var(--text-muted);">
                        Admin: <strong style="color: var(--admin-accent);">@<?php echo htmlspecialchars($adminUsername); ?></strong>
                    </span>
                </div>
            </header>

            <!-- Metrics Grid Dashboard Summary -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <span class="metric-label">Usuarios del Colegio</span>
                    <span class="metric-value" id="statUsers">0</span>
                </div>
                <div class="metric-card">
                    <span class="metric-label">Colegio Activo</span>
                    <span class="metric-value" id="statSchools" style="font-size: 16px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?php echo htmlspecialchars($schoolName); ?>
                    </span>
                </div>
                <div class="metric-card">
                    <span class="metric-label">Productos en Venta</span>
                    <span class="metric-value" id="statProducts">0</span>
                </div>
                <div class="metric-card" style="border-left: 4px solid var(--secondary-color);">
                    <span class="metric-label">Volumen de Ventas</span>
                    <span class="metric-value" id="statVolume">$0</span>
                </div>
                <div class="metric-card" style="border-left: 4px solid var(--admin-accent); background: #f0fdf4;">
                    <span class="metric-label">Fondo Escolar Recaudado</span>
                    <span class="metric-value" id="statRevenue" style="color:#166534;">$0</span>
                </div>
            </div>

            <!-- Table section for recent transactions -->
            <div class="table-card">
                <div class="table-header">
                    <h2>Últimas Transacciones en este Colegio</h2>
                    <span style="font-size:12px; color:var(--text-muted);">Historial de compras registradas</span>
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID Orden</th>
                                <th>Fecha</th>
                                <th>Colegio</th>
                                <th>Producto</th>
                                <th>Tipo</th>
                                <th>Total Pagado</th>
                                <th>Fondo Escolar</th>
                                <th>Neto Vendedor</th>
                            </tr>
                        </thead>
                        <tbody id="adminDashboardTransactions">
                            <tr>
                                <td colspan="8" style="text-align:center; color:var(--text-muted);">Cargando registro de transacciones...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- Core Javascript Scripts -->
    <script src="../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Load dashboard analytics metrics
            fetch('../controllers/AdminController.php?action=stats')
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        const d = res.data;
                        document.getElementById('statUsers').innerText = d.users_count;
                        document.getElementById('statProducts').innerText = d.products_count;

                        const cop = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });
                        document.getElementById('statVolume').innerText = cop.format(d.sales_volume);
                        document.getElementById('statRevenue').innerText = cop.format(d.total_revenue);
                    }
                });

            // Load transaction logs
            <?php
            if ($adminSchoolId) {
                $sqlTrans = "
                    SELECT s.*, COALESCE(p.title, tp.title) as product_title, sch.school_name 
                    FROM sales s
                    LEFT JOIN student_products p ON s.product_id = p.id AND s.product_type = 'student'
                    LEFT JOIN teacher_products tp ON s.product_id = tp.id AND s.product_type = 'teacher'
                    LEFT JOIN schools sch ON (p.school_id = sch.id OR tp.school_id = sch.id)
                    WHERE (p.school_id = :sid1 OR tp.school_id = :sid2)
                    ORDER BY s.created_at DESC LIMIT 8";
                $stmtTrans = $db->prepare($sqlTrans);
                $stmtTrans->execute([':sid1' => $adminSchoolId, ':sid2' => $adminSchoolId]);
                $transactions = $stmtTrans->fetchAll();
            } else {
                $sqlTrans = "
                    SELECT s.*, COALESCE(p.title, tp.title) as product_title, sch.school_name 
                    FROM sales s
                    LEFT JOIN student_products p ON s.product_id = p.id AND s.product_type = 'student'
                    LEFT JOIN teacher_products tp ON s.product_id = tp.id AND s.product_type = 'teacher'
                    LEFT JOIN schools sch ON (p.school_id = sch.id OR tp.school_id = sch.id)
                    ORDER BY s.created_at DESC LIMIT 8";
                $transactions = $db->query($sqlTrans)->fetchAll();
            }
            ?>
            
            const transContainer = document.getElementById('adminDashboardTransactions');
            const cop = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });
            
            const transData = <?php echo json_encode($transactions); ?>;
            if (transData && transData.length > 0) {
                let html = '';
                transData.forEach(t => {
                    html += `
                        <tr>
                            <td><strong>#SM-${t.id}</strong></td>
                            <td>${t.created_at}</td>
                            <td>${t.school_name || 'N/A'}</td>
                            <td>${t.product_title || 'Producto retirado'}</td>
                            <td><span class="badge badge-active" style="background:#e0f2fe; color:#0369a1;">${(t.product_type || '').toUpperCase()}</span></td>
                            <td><strong>${cop.format(t.total_amount)}</strong></td>
                            <td style="color:#ef4444;">${cop.format(t.commission_amount)}</td>
                            <td style="color:#166534; font-weight:700;">${cop.format(t.seller_amount)}</td>
                        </tr>
                    `;
                });
                transContainer.innerHTML = html;
            } else {
                transContainer.innerHTML = '<tr><td colspan="8" style="text-align:center; color:var(--text-muted); padding: 24px;">No hay transacciones registradas en este colegio todavía.</td></tr>';
            }

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
    </script>
</body>
</html>
