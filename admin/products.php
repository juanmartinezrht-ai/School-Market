<?php
/**
 * Administrator Product Moderation Panel
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
    <title>School Market - Moderar Productos</title>
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
                <li class="active"><a href="products.php">🛍️ Moderar Productos</a></li>
                <li><a href="schools.php">🏫 Datos del Colegio</a></li>
                <li><a href="reports.php">⚠️ Reportes y Alertas</a></li>
                <li><a href="settings.php">⚙️ Configuración</a></li>
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
                    <h1>Listing Moderation Dashboard</h1>
                    <p>Audit and approve listings uploaded by students and teachers</p>
                </div>
            </header>

            <!-- 1. Student Listings Section -->
            <div class="table-card" style="margin-bottom: 40px;">
                <div class="table-header">
                    <h2>Student Products Listings</h2>
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Thumbnail</th>
                                <th>Product Title</th>
                                <th>Seller Name</th>
                                <th>Institution</th>
                                <th>Category / Cond</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="studentProductsTableBody">
                            <tr>
                                <td colspan="8" style="text-align:center; color:var(--text-muted);">Retrieving student listings...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. Teacher Listings Section -->
            <div class="table-card">
                <div class="table-header">
                    <h2>Teacher Academic Resources</h2>
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Thumbnail</th>
                                <th>Resource Title</th>
                                <th>Teacher Name</th>
                                <th>Institution</th>
                                <th>Subject / Grade</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="teacherProductsTableBody">
                            <tr>
                                <td colspan="8" style="text-align:center; color:var(--text-muted);">Retrieving teacher resources...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- Core Scripts -->
    <script src="../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            loadListings();

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

        function loadListings() {
            fetch('../controllers/AdminController.php?action=products-list')
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        renderStudentListings(res.data.student_products);
                        renderTeacherListings(res.data.teacher_products);
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Failed to retrieve listings catalogs.', 'error');
                });
        }

        function renderStudentListings(items) {
            const tbody = document.getElementById('studentProductsTableBody');
            const cop = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });

            if (items.length > 0) {
                let html = '';
                items.forEach(item => {
                    let images = [];
                    try { images = JSON.parse(item.image); } catch (e) { images = [item.image]; }
                    const mainImg = images[0] || '../assets/images/default_product.png';
                    const badgeColor = item.status === 'approved' ? 'badge-approved' : (item.status === 'pending' ? 'badge-pending' : 'badge-hidden');
                    
                    html += `
                        <tr>
                            <td><img src="../${mainImg}" style="height:40px; width:50px; object-fit:cover; border-radius:4px; border:1px solid #e2e8f0;" onerror="this.src='../assets/images/default_product.png'"></td>
                            <td><strong>${sanitizeHTML(item.title)}</strong><br><small style="color:var(--text-muted); display:-webkit-box; -webkit-line-clamp:1; -webkit-box-orient:vertical; overflow:hidden;">${sanitizeHTML(item.description)}</small></td>
                            <td>${sanitizeHTML(item.seller_name)}<br><small style="color:var(--text-muted);">Grade ${sanitizeHTML(item.grade_level || 'N/A')}</small></td>
                            <td>${sanitizeHTML(item.school_name)}</td>
                            <td><span class="badge" style="background:#f1f5f9; color:#475569;">${sanitizeHTML(item.category)}</span><br><small style="color:var(--text-muted);">${sanitizeHTML(item.condition.replace('_', ' '))}</small></td>
                            <td><strong>${cop.format(item.price)}</strong></td>
                            <td><span class="badge ${badgeColor}">${item.status.toUpperCase()}</span></td>
                            <td style="text-align:right;">
                                <div class="actions-cell" style="justify-content:flex-end;">
                                    ${item.status !== 'approved' ? `<button class="btn btn-sm btn-secondary" onclick="moderateListing(${item.id}, 'student', 'approved')">Approve</button>` : ''}
                                    ${item.status !== 'hidden' ? `<button class="btn btn-sm btn-outline" onclick="moderateListing(${item.id}, 'student', 'hidden')">Hide</button>` : ''}
                                    <button class="btn btn-sm btn-outline" style="border-color:#ef4444; color:#dc2626; background:#fff5f5;" onclick="moderateListing(${item.id}, 'student', 'delete')">Remove</button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; color:var(--text-muted);">No student products uploaded.</td></tr>';
            }
        }

        function renderTeacherListings(items) {
            const tbody = document.getElementById('teacherProductsTableBody');
            const cop = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });

            if (items.length > 0) {
                let html = '';
                items.forEach(item => {
                    let images = [];
                    try { images = JSON.parse(item.image); } catch (e) { images = [item.image]; }
                    const mainImg = images[0] || '../assets/images/default_product.png';
                    const badgeColor = item.status === 'approved' ? 'badge-approved' : (item.status === 'pending' ? 'badge-pending' : 'badge-hidden');
                    
                    html += `
                        <tr>
                            <td><img src="../${mainImg}" style="height:40px; width:50px; object-fit:cover; border-radius:4px; border:1px solid #e2e8f0;" onerror="this.src='../assets/images/default_product.png'"></td>
                            <td><strong>${sanitizeHTML(item.title)}</strong><br><small style="color:var(--text-muted); display:-webkit-box; -webkit-line-clamp:1; -webkit-box-orient:vertical; overflow:hidden;">${sanitizeHTML(item.description)}</small></td>
                            <td>🎓 ${sanitizeHTML(item.teacher_name)}</td>
                            <td>${sanitizeHTML(item.school_name)}</td>
                            <td><span class="badge" style="background:#ecfdf5; color:#065f46;">${sanitizeHTML(item.subject)}</span><br><small style="color:var(--text-muted);">Grade ${sanitizeHTML(item.grade)}</small></td>
                            <td><strong>${cop.format(item.price)}</strong></td>
                            <td><span class="badge ${badgeColor}">${item.status.toUpperCase()}</span></td>
                            <td style="text-align:right;">
                                <div class="actions-cell" style="justify-content:flex-end;">
                                    ${item.status !== 'approved' ? `<button class="btn btn-sm btn-secondary" onclick="moderateListing(${item.id}, 'teacher', 'approved')">Approve</button>` : ''}
                                    ${item.status !== 'hidden' ? `<button class="btn btn-sm btn-outline" onclick="moderateListing(${item.id}, 'teacher', 'hidden')">Hide</button>` : ''}
                                    <button class="btn btn-sm btn-outline" style="border-color:#ef4444; color:#dc2626; background:#fff5f5;" onclick="moderateListing(${item.id}, 'teacher', 'delete')">Remove</button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; color:var(--text-muted);">No teacher academic resources uploaded.</td></tr>';
            }
        }

        function moderateListing(id, type, status) {
            let msg = `Are you sure you want to change this listing status to '${status.toUpperCase()}'?`;
            if (status === 'delete') msg = "Are you sure you want to permanently delete this listing from the database? This cannot be undone.";

            if (!confirm(msg)) return;

            const body = new FormData();
            body.append('id', id);
            body.append('type', type);
            body.append('status', status);

            fetch('../controllers/AdminController.php?action=product-moderate', {
                method: 'POST',
                body: body
            })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    showToast(res.message);
                    loadListings();
                } else {
                    showToast(res.message, 'error');
                }
            });
        }

        function sanitizeHTML(str) {
            if (!str) return '';
            return str.replace(/[&<>'"]/g, 
                tag => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    "'": '&#39;',
                    '"': '&quot;'
                }[tag] || tag)
            );
        }
    </script>
</body>
</html>
