<?php
/**
 * Administrator Users Management Panel
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

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
    <title>School Market - Control de Usuarios</title>
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
                <li class="active"><a href="users.php">👥 Control de Usuarios</a></li>
                <li><a href="products.php">🛍️ Moderar Productos</a></li>
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
                    <h1>Users Control Center</h1>
                    <p>Manage and audit student and teacher registration accounts</p>
                </div>
            </header>

            <div class="table-card">
                <div class="table-header">
                    <h2>Registered Accounts Directory</h2>
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Institution</th>
                                <th>Grade / Group</th>
                                <th>Status</th>
                                <th>Joined Date</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <tr>
                                <td colspan="8" style="text-align:center; color:var(--text-muted);">Retrieving user records...</td>
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
            loadUsers();

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

        function loadUsers() {
            fetch('../controllers/AdminController.php?action=users-list')
                .then(r => r.json())
                .then(res => {
                    const tbody = document.getElementById('usersTableBody');
                    if (res.status === 'success' && res.data.length > 0) {
                        let html = '';
                        res.data.forEach(u => {
                            const date = new Date(u.created_at).toLocaleDateString('es-CO');
                            const badgeColor = u.status === 'active' ? 'badge-active' : (u.status === 'suspended' ? 'badge-suspended' : 'badge-banned');
                            
                            html += `
                                <tr>
                                    <td><strong>${sanitizeHTML(u.full_name)}</strong><br><small style="color:var(--text-muted);">Doc ID: ${sanitizeHTML(u.student_id || 'N/A')}</small></td>
                                    <td>${sanitizeHTML(u.email)}</td>
                                    <td><span class="badge" style="background:#e2e8f0; color:#475569;">${u.role.toUpperCase()}</span></td>
                                    <td>${sanitizeHTML(u.school_name)}</td>
                                    <td>Grade ${sanitizeHTML(u.grade_level || 'N/A')} - ${sanitizeHTML(u.group_name || 'N/A')}</td>
                                    <td><span class="badge ${badgeColor}">${u.status.toUpperCase()}</span></td>
                                    <td>${date}</td>
                                    <td style="text-align:right;">
                                        <div class="actions-cell" style="justify-content:flex-end;">
                                            ${u.status !== 'active' ? `<button class="btn btn-sm btn-secondary" onclick="updateUserStatus(${u.id}, 'active')">Activate</button>` : ''}
                                            ${u.status === 'active' ? `<button class="btn btn-sm btn-outline" style="border-color:#fbbf24; color:#d97706;" onclick="updateUserStatus(${u.id}, 'suspended')">Suspend</button>` : ''}
                                            ${u.status !== 'banned' ? `<button class="btn btn-sm btn-outline" style="border-color:#ef4444; color:#dc2626;" onclick="updateUserStatus(${u.id}, 'banned')">Ban</button>` : ''}
                                            <button class="btn btn-sm btn-outline" style="background:#fef2f2; border-color:#fca5a5; color:#b91c1c;" onclick="deleteUser(${u.id})">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                        tbody.innerHTML = html;
                    } else {
                        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; color:var(--text-muted);">No users found.</td></tr>';
                    }
                })
                .catch(err => {
                    console.error(err);
                    document.getElementById('usersTableBody').innerHTML = '<tr><td colspan="8" style="text-align:center; color:red;">Failed to retrieve user directories.</td></tr>';
                });
        }

        function updateUserStatus(userId, status) {
            if (!confirm(`Are you sure you want to change user status to '${status.toUpperCase()}'?`)) return;

            const body = new FormData();
            body.append('user_id', userId);
            body.append('status', status);

            fetch('../controllers/AdminController.php?action=user-status', {
                method: 'POST',
                body: body
            })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    showToast(res.message);
                    loadUsers();
                } else {
                    showToast(res.message, 'error');
                }
            });
        }

        function deleteUser(userId) {
            if (!confirm('Are you sure you want to permanently delete this user account? All listings and history associated will be removed.')) return;

            const body = new FormData();
            body.append('user_id', userId);

            fetch('../controllers/AdminController.php?action=user-delete', {
                method: 'POST',
                body: body
            })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    showToast(res.message);
                    loadUsers();
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
