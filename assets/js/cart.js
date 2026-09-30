/**
 * School Market - Shopping Cart Manager
 */

window.Cart = {
    /**
     * Get dynamic storage key based on active user
     */
    getStorageKey: function() {
        const userId = (window.currentUser && window.currentUser.id) 
            || localStorage.getItem('sm_current_user_id');
        return userId ? `school_market_cart_user_${userId}` : 'school_market_cart_guest';
    },

    /**
     * Get all cart items
     */
    getItems: function() {
        try {
            const key = this.getStorageKey();
            const raw = localStorage.getItem(key);
            return raw ? JSON.parse(raw) : [];
        } catch (e) {
            console.error('Error reading cart from localStorage', e);
            return [];
        }
    },

    /**
     * Save cart items to localStorage
     */
    saveItems: function(items) {
        const key = this.getStorageKey();
        localStorage.setItem(key, JSON.stringify(items));
        this.updateBadge();
    },

    /**
     * Add an item to cart
     */
    addItem: function(product, quantity = 1, type = 'student') {
        quantity = Math.max(1, parseInt(quantity) || 1);
        const stock = Math.max(1, parseInt(product.stock) || 1);
        const items = this.getItems();
        
        // Find existing item with same id and type
        const existingIndex = items.findIndex(i => i.id == product.id && i.type === type);

        // Resolve main image
        let img = 'assets/images/default_product.png';
        try {
            if (product.image) {
                const parsed = JSON.parse(product.image);
                img = Array.isArray(parsed) ? (parsed[0] || img) : product.image;
            }
        } catch (e) {
            img = product.image || img;
        }

        if (existingIndex > -1) {
            const newTotal = items[existingIndex].quantity + quantity;
            if (newTotal > stock) {
                if (typeof showToast === 'function') {
                    showToast(`Solo hay ${stock} unidad(es) disponibles de este producto.`, 'error');
                } else {
                    alert(`Solo hay ${stock} unidad(es) disponibles.`);
                }
                items[existingIndex].quantity = stock;
            } else {
                items[existingIndex].quantity = newTotal;
                if (typeof showToast === 'function') {
                    showToast(`Se agregaron +${quantity} unidad(es) al carrito!`, 'success');
                }
            }
            items[existingIndex].stock = stock;
        } else {
            const addQty = Math.min(quantity, stock);
            items.push({
                id: product.id,
                type: type,
                title: product.title,
                price: parseFloat(product.price) || 0,
                stock: stock,
                quantity: addQty,
                image: img,
                seller_name: type === 'student' ? (product.seller_name || 'Estudiante') : (product.teacher_name || 'Docente'),
                school_name: product.school_name || ''
            });
            if (typeof showToast === 'function') {
                showToast(`¡"${product.title}" se agregó al carrito!`, 'success');
            }
        }

        this.saveItems(items);
        return true;
    },

    /**
     * Update quantity of an item
     */
    updateQuantity: function(id, type, newQty) {
        let items = this.getItems();
        const item = items.find(i => i.id == id && i.type === type);
        if (!item) return;

        newQty = parseInt(newQty);
        if (newQty <= 0) {
            this.removeItem(id, type);
            return;
        }

        if (newQty > item.stock) {
            if (typeof showToast === 'function') {
                showToast(`Máximo ${item.stock} unidad(es) disponibles`, 'error');
            }
            newQty = item.stock;
        }

        item.quantity = newQty;
        this.saveItems(items);
    },

    /**
     * Remove an item from the cart
     */
    removeItem: function(id, type) {
        let items = this.getItems();
        items = items.filter(i => !(i.id == id && i.type === type));
        this.saveItems(items);
        if (typeof showToast === 'function') {
            showToast('Producto eliminado del carrito.', 'info');
        }
    },

    /**
     * Clear all cart items
     */
    clear: function() {
        const key = this.getStorageKey();
        localStorage.removeItem(key);
        this.updateBadge();
    },

    /**
     * Total number of individual items
     */
    getTotalCount: function() {
        const items = this.getItems();
        return items.reduce((acc, item) => acc + (item.quantity || 1), 0);
    },

    /**
     * Total subtotal in COP
     */
    getSubtotal: function() {
        const items = this.getItems();
        return items.reduce((acc, item) => acc + (item.price * (item.quantity || 1)), 0);
    },

    /**
     * Update badge counter across all navbars
     */
    updateBadge: function() {
        const count = this.getTotalCount();
        const badges = document.querySelectorAll('.cart-badge, #navCartBadge');
        badges.forEach(badge => {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'inline-flex';
            } else {
                badge.style.display = 'none';
            }
        });
    }
};

var Cart = window.Cart;

// Global helper functions
function addToCart(product, quantity = 1, type = 'student') {
    return window.Cart.addItem(product, quantity, type);
}

// Auto update badge on DOM ready and purge obsolete legacy key
document.addEventListener('DOMContentLoaded', () => {
    try {
        if (localStorage.getItem('school_market_cart')) {
            localStorage.removeItem('school_market_cart');
        }
    } catch (e) {}
    if (window.Cart) window.Cart.updateBadge();
});
