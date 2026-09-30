<?php
/**
 * Administrator Schools & Themes Panel
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/School.php';

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
    <title>School Market - Datos del Colegio</title>
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
                <li class="active"><a href="schools.php">🏫 Datos del Colegio</a></li>
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
                    <h1>Schools & Dynamic Themes</h1>
                    <p>Configure custom visual branding identities for Medellín institutions</p>
                </div>
            </header>

            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px;">
                
                <!-- Left: Schools Directory -->
                <div class="table-card" style="margin-bottom: 0;">
                    <div class="table-header">
                        <h2>Institutions Registry</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Logo</th>
                                    <th>School Name</th>
                                    <th>Address</th>
                                    <th>Theme Palette</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="schoolsTableBody">
                                <tr>
                                    <td colspan="5" style="text-align:center; color:var(--text-muted);">Loading institutions directory...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right: Customize / Create Wizard Form -->
                <div class="settings-box" style="padding: 24px;">
                    <h2 id="formTitle" style="font-size:18px; font-weight:700; margin-bottom: 20px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">Add New School</h2>
                    
                    <form id="schoolSaveForm" enctype="multipart/form-data">
                        <input type="hidden" name="id" id="schoolId" value="0">
                        
                        <div class="form-group">
                            <label for="schoolName">Institution Name</label>
                            <input type="text" id="schoolName" name="school_name" class="form-control" placeholder="e.g. Colegio San Ignacio" required>
                        </div>

                        <div class="form-group">
                            <label for="schoolAddress">Address in Medellín</label>
                            <input type="text" id="schoolAddress" name="address" class="form-control" placeholder="e.g. Calle 48 #43-37" required>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label for="primaryColor">Primary Color</label>
                                <input type="color" id="primaryColor" name="primary_color" class="form-control" style="height: 48px; padding: 4px;" value="#3b82f6">
                            </div>
                            <div class="form-group">
                                <label for="secondaryColor">Secondary Color</label>
                                <input type="color" id="secondaryColor" name="secondary_color" class="form-control" style="height: 48px; padding: 4px;" value="#10b981">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="schoolLogoFile">Logo Image File</label>
                            <input type="file" id="schoolLogoFile" name="logo" class="form-control" accept="image/*">
                        </div>

                        <div class="form-group" style="margin-bottom: 24px;">
                            <label for="schoolBgFile">Background Wallpaper File</label>
                            <input type="file" id="schoolBgFile" name="background_image" class="form-control" accept="image/*">
                        </div>

                        <div style="display: flex; gap: 10px;">
                            <button type="button" class="btn btn-outline" style="flex-grow:1;" onclick="resetSchoolForm()">Reset</button>
                            <button type="submit" class="btn btn-primary" style="flex-grow:2;">Save Institution</button>
                        </div>
                    </form>
                </div>
            </div>

        </main>
    </div>

    <!-- Core Scripts -->
    <script src="../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            loadSchools();

            // Bind admin save form
            document.getElementById('schoolSaveForm').addEventListener('submit', handleSaveSchool);

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

        let loadedSchoolsList = [];

        function loadSchools() {
            fetch('../controllers/AdminController.php?action=schools-list')
                .then(r => r.json())
                .then(res => {
                    const tbody = document.getElementById('schoolsTableBody');
                    if (res.status === 'success' && res.data.length > 0) {
                        loadedSchoolsList = res.data;
                        let html = '';
                        res.data.forEach(s => {
                            const logoUrl = s.logo.includes('uploads/') ? `../${s.logo}` : `../assets/images/schools/${s.logo}`;
                            
                            html += `
                                <tr>
                                    <td><img src="${logoUrl}" style="height:36px; width:36px; object-fit:contain; border-radius:50%; background:white; border:1px solid #e2e8f0; padding:2px;" onerror="this.src='../assets/images/default_logo.png'"></td>
                                    <td><strong>${sanitizeHTML(s.school_name)}</strong></td>
                                    <td style="font-size:13px; color:var(--text-muted);">${sanitizeHTML(s.address)}</td>
                                    <td>
                                        <div style="display:flex; gap:6px; align-items:center;">
                                            <span style="display:inline-block; width:16px; height:16px; border-radius:4px; background:${s.primary_color}; border:1px solid #e2e8f0;"></span>
                                            <span style="display:inline-block; width:16px; height:16px; border-radius:4px; background:${s.secondary_color}; border:1px solid #e2e8f0;"></span>
                                            <code style="font-size:11px;">${s.primary_color}</code>
                                        </div>
                                    </td>
                                    <td style="text-align:right;">
                                        <div class="actions-cell" style="justify-content:flex-end;">
                                            <button class="btn btn-sm btn-outline" onclick="editSchool(${s.id})">Edit</button>
                                            <button class="btn btn-sm btn-outline" style="border-color:#ef4444; color:#dc2626;" onclick="deleteSchool(${s.id})">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                        tbody.innerHTML = html;
                    } else {
                        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:var(--text-muted);">No schools registered.</td></tr>';
                    }
                })
                .catch(err => {
                    console.error(err);
                    document.getElementById('schoolsTableBody').innerHTML = '<tr><td colspan="5" style="text-align:center; color:red;">Failed to connect to database.</td></tr>';
                });
        }

        function editSchool(id) {
            const school = loadedSchoolsList.find(s => s.id === id);
            if (!school) return;

            document.getElementById('schoolId').value = school.id;
            document.getElementById('schoolName').value = school.school_name;
            document.getElementById('schoolAddress').value = school.address;
            document.getElementById('primaryColor').value = school.primary_color;
            document.getElementById('secondaryColor').value = school.secondary_color;
            
            document.getElementById('formTitle').innerText = 'Modify Institution';
        }

        function resetSchoolForm() {
            document.getElementById('schoolSaveForm').reset();
            document.getElementById('schoolId').value = '0';
            document.getElementById('primaryColor').value = '#3b82f6';
            document.getElementById('secondaryColor').value = '#10b981';
            document.getElementById('formTitle').innerText = 'Add New School';
        }

        function handleSaveSchool(e) {
            e.preventDefault();
            const formData = new FormData(e.target);

            fetch('../controllers/AdminController.php?action=school-save', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    showToast(res.message);
                    resetSchoolForm();
                    loadSchools();
                } else {
                    showToast(res.message, 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Failed to save school coordinates.', 'error');
            });
        }

        function deleteSchool(id) {
            if (!confirm('Are you sure you want to delete this school? ALL users, products, and sales logs mapped to this institution will be deleted recursively.')) return;

            const body = new FormData();
            body.append('id', id);

            fetch('../controllers/AdminController.php?action=school-delete', {
                method: 'POST',
                body: body
            })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    showToast(res.message);
                    loadSchools();
                    if (document.getElementById('schoolId').value == id) {
                        resetSchoolForm();
                    }
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
