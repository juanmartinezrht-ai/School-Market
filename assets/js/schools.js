let allSchoolsList = [];

document.addEventListener('DOMContentLoaded', () => {
    const schoolSearchInput = document.getElementById('schoolSearch');
    const schoolContainer = document.getElementById('schoolSelectionGrid');
    
    if (schoolContainer) {
        // Load initial schools list
        fetchSchools();
        
        // Instant Search listener (filters in-memory immediately as user types)
        if (schoolSearchInput) {
            schoolSearchInput.addEventListener('input', () => {
                filterSchoolsLocal(schoolSearchInput.value);
            });
        }
    }
});

/**
 * Filter schools in-memory instantly
 */
function filterSchoolsLocal(query) {
    const container = document.getElementById('schoolSelectionGrid');
    if (!container) return;

    if (!query || query.trim() === '') {
        renderSchools(allSchoolsList, container);
        return;
    }

    const q = query.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    
    const filtered = allSchoolsList.filter(s => {
        const name = (s.school_name || '').toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        const addr = (s.address || '').toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        return name.includes(q) || addr.includes(q);
    });

    renderSchools(filtered, container);
}

/**
 * Retrieve schools list from API
 */
function fetchSchools() {
    const container = document.getElementById('schoolSelectionGrid');
    if (!container) return;

    // Detect if page was opened via file:// protocol instead of http://localhost/
    if (window.location.protocol === 'file:') {
        container.innerHTML = `
            <div style="grid-column: 1/-1; text-align: center; color: #dc2626; background: #fef2f2; padding: 20px; border-radius: 8px; border: 1px solid #fca5a5;">
                <h3 style="font-weight:700; margin-bottom:8px;">⚠️ ATENCIÓN: Página abierta como archivo local</h3>
                <p style="font-size:14px; margin-bottom:12px;">Estás abriendo la página desde <code>file:///</code>. Para que PHP y la base de datos funcionen, debes abrirla a través de XAMPP en tu navegador:</p>
                <a href="http://localhost/school_market/select-school.html" style="display:inline-block; background:#dc2626; color:white; padding:10px 20px; border-radius:50px; text-decoration:none; font-weight:700;">👉 Abrir http://localhost/school_market/select-school.html</a>
            </div>`;
        return;
    }

    container.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 20px; font-weight:600; color: var(--text-muted);">Searching institutions...</div>';

    fetch(`${API_SCHOOL}?action=list`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Server returned HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(res => {
            if (res.status === 'success') {
                allSchoolsList = res.data;
                const searchInput = document.getElementById('schoolSearch');
                const initialQuery = searchInput ? searchInput.value : '';
                filterSchoolsLocal(initialQuery);
            } else {
                container.innerHTML = `<div style="grid-column: 1/-1; text-align: center; color: red;">${res.message}</div>`;
            }
        })
        .catch(err => {
            console.error("Failed to load schools list", err);
            container.innerHTML = `<div style="grid-column: 1/-1; text-align: center; color: red;">Failed to connect (${err.message}). Make sure database is seeded.</div>`;
        });
}

/**
 * Render schools grid cards
 */
function renderSchools(schools, container) {
    if (schools.length === 0) {
        container.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 30px; font-weight:600; color: var(--text-muted);">No schools found in Medellín matching this query.</div>';
        return;
    }

    let html = '';
    schools.forEach(school => {
        // Check if logo is a full upload url or local path
        const logoUrl = school.logo.includes('uploads/') 
            ? school.logo 
            : `assets/images/schools/${school.logo}`;

        html += `
            <div class="school-card" onclick="selectSchool(${JSON.stringify(school).replace(/"/g, '&quot;')})">
                <img src="${logoUrl}" class="school-card-logo" alt="${sanitizeHTML(school.school_name)} logo" onerror="this.src='assets/images/default_logo.png'">
                <div class="school-card-name">${sanitizeHTML(school.school_name)}</div>
                <div class="school-card-address">📍 ${sanitizeHTML(school.address)}</div>
            </div>
        `;
    });

    container.innerHTML = html;
}

/**
 * Handle card click in selecting school
 */
function selectSchool(school) {
    // Render detail panel inside selection screen
    const detailPanel = document.getElementById('selectedSchoolDetail');
    if (!detailPanel) return;

    // Apply colors temporarily to preview visual changes
    document.documentElement.style.setProperty('--primary-color', school.primary_color);
    document.documentElement.style.setProperty('--secondary-color', school.secondary_color);

    const logoUrl = school.logo.includes('uploads/') 
        ? school.logo 
        : `assets/images/schools/${school.logo}`;

    let detailHtml = `
        <div class="glass-panel" style="border-left: 6px solid var(--primary-color); display:flex; flex-direction:column; gap:20px; align-items:center; text-align:center; animation: slideIn 0.3s forwards;">
            <img src="${logoUrl}" style="height:100px; width:100px; object-fit:contain; border-radius:50%; background:white; border: 3px solid var(--primary-color); box-shadow:var(--shadow-sm);" onerror="this.src='assets/images/default_logo.png'">
            <div>
                <h2 style="font-size:26px; font-weight:800; color:var(--text-dark);">${sanitizeHTML(school.school_name)}</h2>
                <p style="color:var(--text-muted); font-size:15px; margin-top:8px;">📍 ${sanitizeHTML(school.address)}</p>
            </div>
            <p style="font-size:14px; color:var(--text-dark); max-width:500px;">
                You are entering the custom marketplace for this institution. Only students and teachers from this school can buy and sell here.
            </p>
            <div style="display:flex; gap:16px;">
                <button class="btn btn-outline" onclick="cancelSelection()">Change School</button>
                <button class="btn btn-primary" onclick="confirmSchoolSelection(${JSON.stringify(school).replace(/"/g, '&quot;')})">Enter Marketplace</button>
            </div>
        </div>
    `;

    detailPanel.innerHTML = detailHtml;
    detailPanel.style.display = 'block';

    // Hide search listings grids to focus on details preview panel
    const searchGridBox = document.getElementById('searchGridBox');
    if (searchGridBox) searchGridBox.style.display = 'none';
}

/**
 * Cancel current selection preview
 */
function cancelSelection() {
    const detailPanel = document.getElementById('selectedSchoolDetail');
    if (detailPanel) {
        detailPanel.innerHTML = '';
        detailPanel.style.display = 'none';
    }

    const searchGridBox = document.getElementById('searchGridBox');
    if (searchGridBox) searchGridBox.style.display = 'block';

    // Reset styles to defaults
    document.documentElement.style.setProperty('--primary-color', '#3b82f6');
    document.documentElement.style.setProperty('--secondary-color', '#10b981');
}

/**
 * Confirm selection, store values, and redirect
 */
function confirmSchoolSelection(school) {
    applySchoolTheme(school);
    showToast(`Entering ${school.school_name}...`);
    setTimeout(() => {
        // If logged in already, go index, else login
        if (currentUser) {
            window.location.href = 'index.html';
        } else {
            window.location.href = 'login.html';
        }
    }, 1000);
}

/**
 * Helper: Debounce utility for inputs filtering
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
