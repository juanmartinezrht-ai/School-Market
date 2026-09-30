/**
 * Global School Market JS Library
 */

const API_AUTH = 'controllers/AuthController.php';
const API_SCHOOL = 'controllers/SchoolController.php';
const API_PRODUCT = 'controllers/ProductController.php';
const API_TEACHER = 'controllers/TeacherController.php';

// Immediate Dark Mode initialization to avoid white flash
(function() {
    if (localStorage.getItem('sm_dark_mode') === 'true') {
        document.documentElement.setAttribute('data-theme', 'dark');
    }
    if (localStorage.getItem('sm_font_large') === 'true') {
        document.documentElement.classList.add('font-large');
    }
})();

document.addEventListener('DOMContentLoaded', () => {
    // Apply accessibility classes to body
    applyThemeMode();
    if (localStorage.getItem('sm_font_large') === 'true') {
        document.body.classList.add('font-large');
    }

    // Initial Session Check
    checkSession();

    // Setup Mobile Nav Toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const navLinks = document.querySelector('.nav-links');
    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('open');
            menuToggle.classList.toggle('active');
        });
    }

    // Apply school theme if saved in localStorage
    const savedSchool = localStorage.getItem('selected_school_theme');
    if (savedSchool) {
        try {
            const school = JSON.parse(savedSchool);
            applySchoolTheme(school);
        } catch (e) {
            console.error("Error parsing saved school theme", e);
        }
    } else {
        // If we are not on select-school.html, and have no school selected, redirect
        const currentPath = window.location.pathname;
        if (!currentPath.includes('select-school.html') && !currentPath.includes('admin/') && !currentPath.includes('login.html') && !currentPath.includes('register.html') && !currentPath.includes('about.html')) {
            window.location.href = 'select-school.html';
        }
    }

    // Ensure Cart manager is loaded across all pages
    if (typeof Cart === 'undefined') {
        const cartScript = document.createElement('script');
        cartScript.src = 'assets/js/cart.js';
        cartScript.onload = () => {
            if (window.Cart) Cart.updateBadge();
        };
        document.head.appendChild(cartScript);
    }
});

/**
 * Display toast notification
 */
function showToast(message, type = 'success') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerText = message;
    
    container.appendChild(toast);

    // Auto-remove after 4 seconds
    setTimeout(() => {
        toast.style.animation = 'slideIn 0.3s reverse forwards';
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 4000);
}

/**
 * Apply Custom School Visual Theme to the website
 */
function applySchoolTheme(school) {
    if (!school) return;
    
    // Set custom CSS variables on documentElement
    document.documentElement.style.setProperty('--primary-color', school.primary_color || '#3b82f6');
    document.documentElement.style.setProperty('--secondary-color', school.secondary_color || '#10b981');
    
    // Set hover states by lightening/darkening (simulated via opacity or inline helper)
    // Dynamic background image
    if (school.background_image) {
        const bgUrl = school.background_image.includes('uploads/') 
            ? school.background_image 
            : `assets/images/schools/${school.background_image}`;
            
        // Use custom styling helper or fallback to nice gradient overlay + school background
        document.documentElement.style.setProperty(
            '--school-bg', 
            `linear-gradient(rgba(248, 250, 252, 0.93), rgba(248, 250, 252, 0.93)), url('${bgUrl}')`
        );
    }

    // Update logo in header if present
    const logoImg = document.querySelector('.logo-img');
    if (logoImg && school.logo) {
        const logoUrl = school.logo.includes('uploads/') 
            ? school.logo 
            : `assets/images/schools/${school.logo}`;
        logoImg.src = logoUrl;
    }

    // Save selection
    localStorage.setItem('selected_school_theme', JSON.stringify(school));
}

/**
 * Check session state and update page structure
 */
let currentUser = null;
function checkSession() {
    fetch(`${API_AUTH}?action=check-session`)
        .then(response => response.json())
        .then(res => {
            if (res.status === 'success') {
                currentUser = res.data;
                localStorage.setItem('sm_current_user_id', currentUser.id);
                updateNavbar(currentUser);
                
                // If school theme was returned from DB, make sure it matches local
                if (currentUser.theme) {
                    const dbSchool = {
                        id: currentUser.school_id,
                        school_name: currentUser.school_name,
                        primary_color: currentUser.theme.primary,
                        secondary_color: currentUser.theme.secondary,
                        logo: currentUser.theme.logo,
                        background_image: currentUser.theme.background_image || 'default_bg.jpg'
                    };
                    applySchoolTheme(dbSchool);
                }
                if (typeof adaptFormForRole === 'function') {
                    adaptFormForRole(currentUser.role);
                }
            } else {
                currentUser = null;
                localStorage.removeItem('sm_current_user_id');
                updateNavbar(null);
            }
            if (window.Cart) Cart.updateBadge();
            if (typeof renderCartPage === 'function') renderCartPage();
        })
        .catch(err => {
            console.error("Session verification failed", err);
            currentUser = null;
            localStorage.removeItem('sm_current_user_id');
            updateNavbar(null);
            if (window.Cart) Cart.updateBadge();
            if (typeof renderCartPage === 'function') renderCartPage();
        });
}

/**
 * Modify navigation links based on user status
 */
function updateNavbar(user) {
    const navLinks = document.getElementById('navLinks');
    const navUserActions = document.getElementById('navUserActions');

    if (navLinks) {
        navLinks.innerHTML = `
            <li><a href="index.html" id="navHome">Dashboard</a></li>
            <li><a href="products.html" id="navProducts">Productos</a></li>
            <li><a href="teacher-products.html" id="navTeacher">Docentes</a></li>
            <li><a href="about.html" id="navAbout">Nosotros</a></li>
        `;
    }

    if (navUserActions) {
        const themeIcon = isDarkMode() ? 'fa-sun' : 'fa-moon';
        const themeTitle = isDarkMode() ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro';

        if (user) {
            // Logged In User
            const avatarSrc = user.avatar || 'assets/images/default_avatar.svg';
            const adminBtn = user.role === 'admin' ? `
                <a href="admin/dashboard.php" class="nav-btn-admin" style="background: linear-gradient(135deg, #1e293b, #0f172a); color: #f8fafc; border: 1px solid #334155; padding: 7px 14px; border-radius: 50px; font-weight: 700; text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;" title="Acceder al Panel de Control Institucional">
                    <i class="fa-solid fa-shield-halved" style="color: #60a5fa;"></i> Modo Admin
                </a>
            ` : '';

            navUserActions.innerHTML = `
                ${adminBtn}
                <a href="product-details.html?action=new" class="nav-btn-sell">
                    <i class="fa-solid fa-plus"></i> Vender
                </a>
                <a href="my-products.html" class="nav-btn-my-products" title="Mis Productos en Venta">
                    <i class="fa-solid fa-box-archive"></i> Mis Productos
                </a>
                <a href="cart.html" class="nav-btn-cart" id="navBtnCart" title="Carrito de Compras">
                    <i class="fa-solid fa-cart-shopping"></i> Carrito
                    <span class="cart-badge" id="navCartBadge" style="display:none;">0</span>
                </a>
                <a href="profile.html" class="nav-btn-profile" title="Mi Perfil y Accesibilidad">
                    <img src="${avatarSrc}" class="nav-avatar-img" alt="Foto Perfil">
                    <span>Perfil</span>
                </a>
                <button type="button" onclick="toggleDarkMode()" class="nav-theme-toggle" id="themeToggleBtn" title="${themeTitle}">
                    <i class="fa-solid ${themeIcon}"></i>
                </button>
                <button onclick="logout(event)" class="nav-btn-logout" title="Cerrar Sesión">
                    <i class="fa-solid fa-right-from-bracket"></i> Salir
                </button>
            `;
        } else {
            // Logged Out User
            navUserActions.innerHTML = `
                <a href="cart.html" class="nav-btn-cart" id="navBtnCart" title="Carrito de Compras">
                    <i class="fa-solid fa-cart-shopping"></i> Carrito
                    <span class="cart-badge" id="navCartBadge" style="display:none;">0</span>
                </a>
                <button type="button" onclick="toggleDarkMode()" class="nav-theme-toggle" id="themeToggleBtn" title="${themeTitle}">
                    <i class="fa-solid ${themeIcon}"></i>
                </button>
                <a href="login.html" class="nav-btn-login">Iniciar Sesión</a>
                <a href="register.html" class="nav-btn-register">Registrarse</a>
            `;
        }

        // Update cart badge if Cart manager is loaded
        if (window.Cart) {
            Cart.updateBadge();
        }
    }

    highlightActiveLink();
}

/**
 * Highlight navbar links based on window location path
 */
function highlightActiveLink() {
    const currentPath = window.location.pathname;
    const links = document.querySelectorAll('.nav-links a');
    
    links.forEach(link => {
        const href = link.getAttribute('href');
        if (href && currentPath.includes(href)) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

/**
 * Close user session
 */
function logout(e) {
    if (e) e.preventDefault();
    
    fetch(`${API_AUTH}?action=logout`)
        .then(response => response.json())
        .then(res => {
            currentUser = null;
            localStorage.removeItem('sm_current_user_id');
            if (window.Cart) Cart.updateBadge();
            if (res.status === 'success') {
                showToast('Logged out successfully.');
                setTimeout(() => {
                    window.location.href = 'select-school.html';
                }, 1000);
            } else {
                showToast('Logout failed.', 'error');
            }
        })
        .catch(err => {
            console.error("Logout request error", err);
            currentUser = null;
            localStorage.removeItem('sm_current_user_id');
            if (window.Cart) Cart.updateBadge();
        });
}

/**
 * Get URL query parameter values
 */
function getQueryParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
}

/**
 * Global HTML Sanitizer Helper
 */
function sanitizeHTML(str) {
    if (!str) return '';
    return String(str).replace(/[&<>'"]/g, 
        tag => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        }[tag] || tag)
    );
}

/**
 * Accessibility and Dark Mode Helpers
 */
function isDarkMode() {
    return localStorage.getItem('sm_dark_mode') === 'true';
}

function applyThemeMode() {
    const dark = isDarkMode();
    if (dark) {
        document.documentElement.setAttribute('data-theme', 'dark');
        if (document.body) document.body.classList.add('dark-theme');
    } else {
        document.documentElement.removeAttribute('data-theme');
        if (document.body) document.body.classList.remove('dark-theme');
    }
    
    // Update theme toggle buttons on page
    const toggleBtns = document.querySelectorAll('.nav-theme-toggle, #themeToggleBtn');
    toggleBtns.forEach(btn => {
        btn.innerHTML = dark ? '<i class="fa-solid fa-sun"></i>' : '<i class="fa-solid fa-moon"></i>';
        btn.title = dark ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro';
    });
}

function toggleDarkMode() {
    const next = !isDarkMode();
    localStorage.setItem('sm_dark_mode', next ? 'true' : 'false');
    applyThemeMode();
    if (typeof showToast === 'function') {
        showToast(next ? 'Modo Oscuro activado 🌙' : 'Modo Claro activado ☀️', 'info');
    }
}

