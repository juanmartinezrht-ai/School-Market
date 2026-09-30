/**
 * Client Auth Handlers (login/register/profile)
 */

document.addEventListener('DOMContentLoaded', () => {
    // Register Form Handler
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        // Toggle student/teacher fields depending on role select
        const roleSelect = document.getElementById('roleSelect');
        const studentFields = document.getElementById('studentFields');
        
        if (roleSelect && studentFields) {
            roleSelect.addEventListener('change', () => {
                const gradeInput = document.getElementById('grade_level');
                if (roleSelect.value === 'teacher') {
                    studentFields.style.display = 'none';
                    document.getElementById('studentIdInput').removeAttribute('required');
                    if (gradeInput) gradeInput.removeAttribute('required');
                } else {
                    studentFields.style.display = 'block';
                    document.getElementById('studentIdInput').setAttribute('required', 'required');
                    if (gradeInput) gradeInput.setAttribute('required', 'required');
                }
            });
        }

        registerForm.addEventListener('submit', handleRegisterSubmit);
    }

    // Login Form Handler
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLoginSubmit);
    }

    // Profile Form Handler
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', handleProfileSubmit);
    }
});

/**
 * Process Register Submission
 */
function handleRegisterSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    // Add selected school ID - take from form select first, fallback to localStorage
    const schoolSelectEl = form.querySelector('[name="school_id"]');
    const schoolIdFromForm = schoolSelectEl ? schoolSelectEl.value : '';

    if (!schoolIdFromForm) {
        const savedSchool = localStorage.getItem('selected_school_theme');
        if (!savedSchool) {
            showToast('Por favor selecciona tu institución educativa.', 'error');
            setTimeout(() => { window.location.href = 'select-school.html'; }, 1500);
            return;
        }
        const school = JSON.parse(savedSchool);
        formData.set('school_id', school.id);
    }
    // school_id already in formData from the <select name="school_id"> field

    // Basic frontend verification
    const password = form.password.value;
    const confirmPassword = form.confirm_password ? form.confirm_password.value : password;

    if (password.length < 6) {
        showToast('La contraseña debe tener al menos 6 caracteres.', 'error');
        return;
    }

    if (password !== confirmPassword) {
        showToast('Las contraseñas no coinciden.', 'error');
        return;
    }

    // Validate student-specific fields manually (HTML required was removed to allow teacher flow)
    const roleEl = form.querySelector('[name="role"]');
    const role = roleEl ? roleEl.value : 'student';
    if (role === 'student') {
        const studentIdEl = form.querySelector('[name="student_id"]');
        if (!studentIdEl || !studentIdEl.value.trim()) {
            showToast('Por favor ingresa tu Carné / Código Estudiantil.', 'error');
            studentIdEl && studentIdEl.focus();
            return;
        }
    }

    fetch(`${API_AUTH}?action=register`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(res => {
        if (res.status === 'success') {
            showToast(res.message, 'success');
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 1500);
        } else {
            showToast(res.message, 'error');
        }
    })
    .catch(err => {
        console.error("Register Error:", err);
        showToast('An error occurred during registration. Please try again.', 'error');
    });
}

/**
 * Process Login Submission
 */
function handleLoginSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    fetch(`${API_AUTH}?action=login`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(res => {
        if (res.status === 'success') {
            showToast(res.message, 'success');
            
            // Set current user ID for shopping cart isolation
            localStorage.setItem('sm_current_user_id', res.data.id);
            if (window.Cart) Cart.updateBadge();

            // Apply their school's theme dynamically
            const schoolTheme = {
                id: res.data.school_id,
                school_name: res.data.school_name,
                primary_color: res.data.theme ? res.data.theme.primary : '#3b82f6',
                secondary_color: res.data.theme ? res.data.theme.secondary : '#10b981',
                logo: res.data.theme ? res.data.theme.logo : 'default_logo.png',
                background_image: res.data.theme ? res.data.theme.background_image : 'default_bg.jpg'
            };
            applySchoolTheme(schoolTheme);
            
            setTimeout(() => {
                if (res.data.role === 'admin' || res.data.redirect === 'admin/dashboard.php') {
                    window.location.href = 'admin/dashboard.php';
                } else {
                    window.location.href = 'index.html';
                }
            }, 1000);
        } else {
            showToast(res.message, 'error');
        }
    })
    .catch(err => {
        console.error("Login Error:", err);
        showToast('Login failed. Please check connection.', 'error');
    });
}

/**
 * Process User Profile Form Updates
 */
function handleProfileSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    
    const password = form.password.value;
    const confirmPassword = form.confirm_password ? form.confirm_password.value : '';

    if (password && password.length < 6) {
        showToast('New password must be at least 6 characters.', 'error');
        return;
    }

    if (password && password !== confirmPassword) {
        showToast('Passwords do not match.', 'error');
        return;
    }

    fetch(`${API_AUTH}?action=update-profile`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(res => {
        if (res.status === 'success') {
            showToast(res.message, 'success');
            // Reset passwords input fields
            form.password.value = '';
            if (form.confirm_password) form.confirm_password.value = '';
            
            // Reload user data
            checkSession();
        } else {
            showToast(res.message, 'error');
        }
    })
    .catch(err => {
        console.error("Profile Update Error:", err);
        showToast('Failed to update profile.', 'error');
    });
}
