/**
 * Products catalog and details actions handler
 */

document.addEventListener('DOMContentLoaded', () => {
    const isProductsPage = document.getElementById('productsPage');
    const isTeacherPage = document.getElementById('teacherProductsPage');
    const isDetailsPage = document.getElementById('productDetailsPage');
    const isCreatePage = document.getElementById('productCreatePage');

    if (isProductsPage) {
        setupProductsFilter();
        loadStudentProducts();
    }

    if (isTeacherPage) {
        setupTeacherFilter();
        loadTeacherProducts();
    }

    if (isDetailsPage) {
        loadProductDetails();
    }

    if (isCreatePage) {
        setupListingForm();
    }
});

/**
 * Load student products into list grids
 */
function loadStudentProducts(filterParams = {}) {
    const grid = document.getElementById('productGrid');
    if (!grid) return;

    grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px; font-weight:600; color: var(--text-muted);">Loading catalog...</div>';

    // Append school ID from localStorage automatically
    const savedSchool = localStorage.getItem('selected_school_theme');
    if (savedSchool) {
        const school = JSON.parse(savedSchool);
        filterParams.school_id = school.id;
    }

    // Convert filter parameters to query string
    const query = new URLSearchParams(filterParams).toString();

    fetch(`${API_PRODUCT}?action=list&${query}`)
        .then(response => response.json())
        .then(res => {
            if (res.status === 'success') {
                renderProducts(res.data, grid, 'student');
            } else {
                grid.innerHTML = `<div style="grid-column: 1/-1; text-align: center; color: red; padding: 40px;">${res.message}</div>`;
            }
        })
        .catch(err => {
            console.error(err);
            grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: red; padding: 40px;">Failed to load listings.</div>';
        });
}

/**
 * Load teacher resources catalog
 */
function loadTeacherProducts(filterParams = {}) {
    const grid = document.getElementById('teacherProductGrid');
    if (!grid) return;

    grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px; font-weight:600; color: var(--text-muted);">Loading resources...</div>';

    const savedSchool = localStorage.getItem('selected_school_theme');
    if (savedSchool) {
        const school = JSON.parse(savedSchool);
        filterParams.school_id = school.id;
    }

    const query = new URLSearchParams(filterParams).toString();

    fetch(`${API_TEACHER}?action=list&${query}`)
        .then(response => response.json())
        .then(res => {
            if (res.status === 'success') {
                renderProducts(res.data, grid, 'teacher');
            } else {
                grid.innerHTML = `<div style="grid-column: 1/-1; text-align: center; color: red; padding: 40px;">${res.message}</div>`;
            }
        })
        .catch(err => {
            console.error(err);
            grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: red; padding: 40px;">Failed to load resources.</div>';
        });
}

/**
 * Render items inside listing grids
 */
function renderProducts(items, targetContainer, type) {
    if (items.length === 0) {
        targetContainer.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 60px; font-size:18px; font-weight:600; color: var(--text-muted);">No products found matching filters.</div>';
        return;
    }

    let html = '';
    items.forEach(item => {
        // Parse images JSON array
        let images = [];
        try {
            images = JSON.parse(item.image);
        } catch (e) {
            images = [item.image];
        }
        const mainImage = images[0] || 'assets/images/default_product.png';

        const formattedPrice = new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            maximumFractionDigits: 0
        }).format(item.price);

        const detailPage = `product-details.html?type=${type}&id=${item.id}`;

        if (type === 'student') {
            html += `
                <div class="product-card">
                    <span class="product-card-badge">${sanitizeHTML(item.category)}</span>
                    <div class="product-card-img-wrapper">
                        <img src="${mainImage}" class="product-card-img" alt="${sanitizeHTML(item.title)}">
                    </div>
                    <div class="product-card-content">
                        <div class="product-card-category">${sanitizeHTML(item.condition.replace('_', ' '))}</div>
                        <h3 class="product-card-title">${sanitizeHTML(item.title)}</h3>
                        <div class="product-card-condition">
                            <span class="condition-dot"></span>
                            <span>Grade: ${sanitizeHTML(item.grade_level || 'N/A')} - ${sanitizeHTML(item.group_name || 'N/A')}</span>
                        </div>
                        <div class="product-card-footer">
                            <div>
                                <div class="product-card-price">${formattedPrice}</div>
                                <div style="font-size:11px; font-weight:700; color:#166534; margin-top:2px;">
                                    <i class="fa-solid fa-boxes-stacked"></i> ${item.stock || 1} disponibles
                                </div>
                            </div>
                            <div class="seller-info">
                                <span class="seller-name">${sanitizeHTML(item.seller_name)}</span>
                                <span>Medellín</span>
                            </div>
                        </div>
                    </div>
                    <a href="${detailPage}" style="position:absolute; top:0; left:0; width:100%; height:100%; z-index:1;"></a>
                </div>
            `;
        } else {
            // Teacher layout
            html += `
                <div class="product-card" style="border-left: 4px solid var(--secondary-color);">
                    <span class="product-card-badge" style="background:var(--secondary-color);">${sanitizeHTML(item.subject)}</span>
                    <div class="product-card-img-wrapper">
                        <img src="${mainImage}" class="product-card-img" alt="${sanitizeHTML(item.title)}">
                    </div>
                    <div class="product-card-content">
                        <div class="product-card-category" style="color:var(--secondary-color);">ACADEMIC MATERIAL</div>
                        <h3 class="product-card-title">${sanitizeHTML(item.title)}</h3>
                        <div class="product-card-condition">
                            <span class="condition-dot" style="background:var(--secondary-color);"></span>
                            <span>Grade target: ${sanitizeHTML(item.grade || 'All')} (${sanitizeHTML(item.group_name || 'All')})</span>
                        </div>
                        <div class="product-card-footer">
                            <div>
                                <div class="product-card-price">${formattedPrice}</div>
                                <div style="font-size:11px; font-weight:700; color:#166534; margin-top:2px;">
                                    <i class="fa-solid fa-boxes-stacked"></i> ${item.stock || 1} disponibles
                                </div>
                            </div>
                            <div class="seller-info">
                                <span class="seller-name">🎓 ${sanitizeHTML(item.teacher_name)}</span>
                                <span>Teacher</span>
                            </div>
                        </div>
                    </div>
                    <a href="${detailPage}" style="position:absolute; top:0; left:0; width:100%; height:100%; z-index:1;"></a>
                </div>
            `;
        }
    });

    targetContainer.innerHTML = html;
}

/**
 * Configure Products page search filters
 */
function setupProductsFilter() {
    const filterForm = document.getElementById('filterForm');
    if (!filterForm) return;

    filterForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const params = {
            search: filterForm.search.value,
            category: filterForm.category.value,
            condition: filterForm.condition.value,
            price_min: filterForm.price_min.value,
            price_max: filterForm.price_max.value,
            grade_level: filterForm.grade_level ? filterForm.grade_level.value : '',
            group_name: filterForm.group_name ? filterForm.group_name.value : ''
        };
        loadStudentProducts(params);
    });
}

/**
 * Configure Teacher page search filters
 */
function setupTeacherFilter() {
    const filterForm = document.getElementById('teacherFilterForm');
    if (!filterForm) return;

    filterForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const params = {
            search: filterForm.search.value,
            subject: filterForm.subject.value,
            grade: filterForm.grade.value,
            price_min: filterForm.price_min.value,
            price_max: filterForm.price_max.value
        };
        loadTeacherProducts(params);
    });
}

/**
 * Load individual product details views
 */
function loadProductDetails() {
    const action = getQueryParam('action');
    if (action === 'new') {
        return; // Creating a new product listing, skip loading details
    }

    const id = getQueryParam('id');
    const type = getQueryParam('type') || 'student'; // student or teacher

    if (!id) {
        showToast('Invalid request: Product ID missing.', 'error');
        setTimeout(() => { window.location.href = 'index.html'; }, 2000);
        return;
    }

    const endpoint = type === 'student' ? API_PRODUCT : API_TEACHER;
    
    fetch(`${endpoint}?action=get&id=${id}`)
        .then(response => response.json())
        .then(res => {
            if (res.status === 'success') {
                renderDetails(res.data, type);
            } else {
                showToast(res.message, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Failed to load listing details.', 'error');
        });
}

/**
 * Render product details layouts dynamically
 */
function renderDetails(data, type) {
    // Gallery Setup
    let images = [];
    try {
        images = JSON.parse(data.image);
    } catch (e) {
        images = [data.image];
    }

    const mainImg = document.getElementById('mainImg');
    const thubRow = document.getElementById('thumbRow');

    if (mainImg) {
        mainImg.src = images[0] || 'assets/images/default_product.png';
    }

    if (thubRow && images.length > 1) {
        thubRow.innerHTML = images.map((img, index) => 
            `<img src="${img}" class="thumbnail ${index === 0 ? 'active' : ''}" onclick="setMainImage('${img}', this)">`
        ).join('');
    }

    // Details texts
    document.getElementById('productTitle').innerText = data.title;
    document.getElementById('productDesc').innerText = data.description;
    
    // Condition or Subject badge
    const badge = document.getElementById('productCategory');
    if (badge) {
        if (type === 'student') {
            badge.innerText = data.category;
            document.getElementById('productCondition').innerText = data.condition.replace('_', ' ').toUpperCase();
        } else {
            badge.innerText = data.subject;
            document.getElementById('productCondition').innerText = 'ACADEMIC RESOURCE';
        }
    }

    // Price details
    const formattedPrice = new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        maximumFractionDigits: 0
    }).format(data.price);
    
    document.getElementById('productPrice').innerText = formattedPrice;

    // Seller fields
    const sellerName = type === 'student' ? data.seller_name : data.teacher_name;
    document.getElementById('sellerNameText').innerText = sellerName;
    document.getElementById('sellerSchool').innerText = data.school_name;
    
    if (type === 'student') {
        document.getElementById('sellerClass').innerText = `Grade ${data.grade_level || 'N/A'} - Group ${data.group_name || 'N/A'}`;
        
        // Reputation ratings display
        const rating = data.seller_rating || 0.0;
        const count = data.seller_review_count || 0;
        
        document.getElementById('reputationScore').innerText = rating.toFixed(1);
        document.getElementById('reputationCount').innerText = `(${count} reviews)`;
        
        // Render stars
        const starsBox = document.getElementById('reputationStars');
        if (starsBox) {
            let stars = '';
            for (let i = 1; i <= 5; i++) {
                if (i <= Math.round(rating)) {
                    stars += '★';
                } else {
                    stars += '☆';
                }
            }
            starsBox.innerHTML = stars;
        }

        // Reviews profile triggers
        const viewReviewsBtn = document.getElementById('viewReviewsBtn');
        if (viewReviewsBtn) {
            viewReviewsBtn.href = `reviews.html?seller_id=${data.user_id}&seller_name=${encodeURIComponent(data.seller_name)}`;
        }

        // Binds review submit inputs
        const sellerIdInput = document.getElementById('reviewSellerId');
        if (sellerIdInput) sellerIdInput.value = data.user_id;

        const reportUserIdInput = document.getElementById('reportUserId');
        if (reportUserIdInput) reportUserIdInput.value = data.user_id;
    } else {
        // Hide reputation UI elements for teacher products since the rating is student-only
        const repBox = document.getElementById('reputationBox');
        if (repBox) repBox.style.display = 'none';
        
        document.getElementById('sellerClass').innerText = `Target: Grade ${data.grade || 'All'} (${data.group_name || 'All'})`;
    }

    // Dynamic color theme binding
    if (data.primary_color) {
        document.documentElement.style.setProperty('--primary-color', data.primary_color);
        document.documentElement.style.setProperty('--secondary-color', data.secondary_color);
    }

    // Stock & Availability bindings
    const stock = parseInt(data.stock) || 1;
    const stockBadge = document.getElementById('productStockBadge');
    const stockCount = document.getElementById('productStockCount');
    const buyButton = document.getElementById('buyButton');
    const addToCartBtn = document.getElementById('addToCartBtn');
    const qtyInput = document.getElementById('detailQuantity');
    const btnMinus = document.getElementById('btnQtyMinus');
    const btnPlus = document.getElementById('btnQtyPlus');

    if (stockCount) stockCount.innerText = stock;

    if (stock <= 0) {
        if (stockBadge) {
            stockBadge.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Agotado';
            stockBadge.style.background = '#fee2e2';
            stockBadge.style.color = '#991b1b';
            stockBadge.style.borderColor = '#fecaca';
        }
        if (buyButton) {
            buyButton.disabled = true;
            buyButton.innerText = 'Producto Agotado';
            buyButton.style.opacity = '0.6';
        }
        if (addToCartBtn) {
            addToCartBtn.disabled = true;
            addToCartBtn.style.opacity = '0.6';
        }
        if (qtyInput) qtyInput.disabled = true;
    } else {
        if (qtyInput) {
            qtyInput.max = stock;
            qtyInput.value = 1;
        }

        if (btnMinus && qtyInput) {
            btnMinus.onclick = () => {
                let v = parseInt(qtyInput.value) || 1;
                if (v > 1) qtyInput.value = v - 1;
            };
        }

        if (btnPlus && qtyInput) {
            btnPlus.onclick = () => {
                let v = parseInt(qtyInput.value) || 1;
                if (v < stock) qtyInput.value = v + 1;
                else showToast(`Solo hay ${stock} unidad(es) disponibles`, 'info');
            };
        }

        // Add to Cart handler
        if (addToCartBtn) {
            addToCartBtn.onclick = () => {
                const qty = parseInt(qtyInput ? qtyInput.value : 1) || 1;
                if (window.Cart && typeof window.Cart.addItem === 'function') {
                    window.Cart.addItem(data, qty, type);
                } else if (typeof Cart !== 'undefined' && typeof Cart.addItem === 'function') {
                    Cart.addItem(data, qty, type);
                } else {
                    // Direct localStorage fallback
                    const uid = (window.currentUser && window.currentUser.id) || localStorage.getItem('sm_current_user_id');
                    const CART_KEY = uid ? `school_market_cart_user_${uid}` : 'school_market_cart_guest';
                    let items = [];
                    try { items = JSON.parse(localStorage.getItem(CART_KEY)) || []; } catch(e) {}
                    let img = 'assets/images/default_product.png';
                    try {
                        if (data.image) {
                            const parsed = JSON.parse(data.image);
                            img = Array.isArray(parsed) ? (parsed[0] || img) : data.image;
                        }
                    } catch (e) { img = data.image || img; }

                    const existing = items.find(i => i.id == data.id && i.type === type);
                    if (existing) {
                        existing.quantity += qty;
                    } else {
                        items.push({
                            id: data.id,
                            type: type,
                            title: data.title,
                            price: parseFloat(data.price) || 0,
                            stock: parseInt(data.stock) || 1,
                            quantity: qty,
                            image: img,
                            seller_name: type === 'student' ? (data.seller_name || 'Estudiante') : (data.teacher_name || 'Docente'),
                            school_name: data.school_name || ''
                        });
                    }
                    localStorage.setItem(CART_KEY, JSON.stringify(items));
                    showToast(`¡"${data.title}" se agregó al carrito!`, 'success');
                    const badge = document.getElementById('navCartBadge');
                    if (badge) {
                        const totalCount = items.reduce((a, b) => a + (b.quantity || 1), 0);
                        badge.textContent = totalCount;
                        badge.style.display = 'inline-flex';
                    }
                }
            };
        }

        // Checkout buy handler
        if (buyButton) {
            buyButton.onclick = () => {
                const qty = parseInt(qtyInput ? qtyInput.value : 1) || 1;
                openCheckoutModal(data, type, qty);
            };
        }
    }
}

/**
 * Handle details main gallery switch
 */
function setMainImage(src, thumbEl) {
    const mainImg = document.getElementById('mainImg');
    if (mainImg) mainImg.src = src;

    // Toggle active classes
    const thumbs = document.querySelectorAll('.thumbnail');
    thumbs.forEach(t => t.classList.remove('active'));
    thumbEl.classList.add('active');
}

/**
 * Checkout purchase dialog flows
 */
function openCheckoutModal(product, type, quantity = 1) {
    // Verify login
    if (!currentUser) {
        showToast('Inicia sesión para realizar compras.', 'error');
        setTimeout(() => { window.location.href = 'login.html'; }, 1500);
        return;
    }

    const modal = document.getElementById('checkoutModal');
    if (!modal) return;

    quantity = Math.max(1, parseInt(quantity) || 1);
    const unitPrice = parseFloat(product.price);
    const totalAmount = unitPrice * quantity;
    const commPct = 5.0; // Simulated locally first, verified on server
    const commAmt = totalAmount * (commPct / 100);
    const sellAmt = totalAmount - commAmt;

    const cop = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });

    document.getElementById('modalProductTitle').innerText = product.title;
    if (document.getElementById('modalQuantity')) {
        document.getElementById('modalQuantity').innerText = quantity;
    }
    if (document.getElementById('modalUnitPrice')) {
        document.getElementById('modalUnitPrice').innerText = cop.format(unitPrice);
    }
    document.getElementById('modalPrice').innerText = cop.format(totalAmount);
    document.getElementById('modalCommission').innerText = cop.format(commAmt);
    document.getElementById('modalSellerReceives').innerText = cop.format(sellAmt);

    const sellerName = type === 'student' ? product.seller_name : product.teacher_name;
    document.getElementById('modalSellerName').innerText = sellerName;

    // Open modal
    modal.classList.add('open');

    // Setup confirm checkout button
    const confirmBtn = document.getElementById('confirmCheckoutBtn');
    confirmBtn.onclick = () => {
        confirmCheckout(product.id, type, quantity);
    };
}

/**
 * Close modal dialogs
 */
function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove('open');
}

/**
 * Confirm checkout simulated transaction via endpoint
 */
function confirmCheckout(productId, type, quantity = 1) {
    const endpoint = type === 'student' ? API_PRODUCT : API_TEACHER;
    const body = new FormData();
    body.append('product_id', productId);
    body.append('quantity', quantity);

    fetch(`${endpoint}?action=buy`, {
        method: 'POST',
        body: body
    })
    .then(response => response.json())
    .then(res => {
        if (res.status === 'success') {
            closeModal('checkoutModal');
            showToast(res.message, 'success');
            
            // Build and show receipt slip
            const data = res.data;
            showReceiptModal(data);

            // Reload details live to update remaining stock on the page
            loadProductDetails();
        } else {
            showToast(res.message, 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Checkout purchase failed.', 'error');
    });
}

/**
 * Render success purchase transaction receipt
 */
function showReceiptModal(data) {
    const modal = document.getElementById('receiptModal');
    if (!modal) return;

    const cop = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });

    document.getElementById('receiptTransId').innerText = `#SM-${data.transaction_id}`;
    document.getElementById('receiptDate').innerText = data.date;
    document.getElementById('receiptTitle').innerText = data.quantity && data.quantity > 1 
        ? `${data.product_title} (x${data.quantity})` 
        : data.product_title;
    document.getElementById('receiptSchool').innerText = data.school_name;
    document.getElementById('receiptSeller').innerText = data.seller_name;
    document.getElementById('receiptBuyer').innerText = data.buyer_name;
    document.getElementById('receiptTotal').innerText = cop.format(data.price);
    document.getElementById('receiptCommission').innerText = `${cop.format(data.commission_charged)} (${data.commission_rate})`;
    document.getElementById('receiptSellerNet').innerText = cop.format(data.seller_payment);

    modal.classList.add('open');
}

/**
 * Submit feedback star ratings
 */
function submitReview(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    fetch(`${API_PRODUCT}?action=submit-review`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(res => {
        if (res.status === 'success') {
            showToast(res.message);
            closeModal('reviewModal');
            form.reset();
            // Reload product details to update average reputation score
            loadProductDetails();
        } else {
            showToast(res.message, 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Review submission failed.', 'error');
    });
}

/**
 * File safety report
 */
function submitReport(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    fetch(`${API_PRODUCT}?action=report-user`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(res => {
        if (res.status === 'success') {
            showToast(res.message);
            closeModal('reportModal');
            form.reset();
        } else {
            showToast(res.message, 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Safety report submission failed.', 'error');
    });
}

/**
 * Handle listing creation form uploading
 */
function setupListingForm() {
    const form = document.getElementById('listingUploadForm');
    if (!form) return;

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        
        // Form validations
        const imagesInput = document.getElementById('imagesInput');
        if (imagesInput && imagesInput.files.length > 5) {
            showToast('You can upload a maximum of 5 images.', 'error');
            return;
        }

        const formData = new FormData(form);
        
        // Find correct endpoint based on current user session role or visible fields
        const isTeacher = (window.currentUser && window.currentUser.role === 'teacher') ||
                          (document.getElementById('teacherFormFields') && document.getElementById('teacherFormFields').style.display !== 'none');
        const endpoint = isTeacher ? API_TEACHER : API_PRODUCT;

        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Publicando...';
        }

        fetch(`${endpoint}?action=create`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(res => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = isTeacher 
                    ? 'Publicar Recurso Docente <i class="fa-solid fa-paper-plane" style="margin-left:6px;"></i>' 
                    : 'Publicar Producto Ahora <i class="fa-solid fa-paper-plane" style="margin-left:6px;"></i>';
            }
            if (res.status === 'success') {
                showToast(res.message, 'success');
                setTimeout(() => {
                    window.location.href = isTeacher ? 'teacher-products.html' : 'products.html';
                }, 1500);
            } else {
                showToast(res.message, 'error');
            }
        })
        .catch(err => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Publicar <i class="fa-solid fa-paper-plane" style="margin-left:6px;"></i>';
            }
            console.error(err);
            showToast('Error de conexión al publicar el producto.', 'error');
        });
    });
}

/**
 * Simple HTML Escaper helper
 */
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
