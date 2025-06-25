// Product page functionality
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle functionality (if not already in main.js)
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('nav ul');
    
    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('show');
        });
    }
    
    // Category filter auto-submit
    const categorySelect = document.getElementById('category');
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            this.form.submit();
        });
    }
    
    // Initialize product hover effects
    initProductCards();
});

// Initialize product cards
function initProductCards() {
    const productCards = document.querySelectorAll('.product-card');
    
    productCards.forEach(card => {
        // Prevent action buttons from triggering card click
        const actionButtons = card.querySelectorAll('.action-btn, .btn-cart');
        actionButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    });
}

// Add to wishlist function
function addToWishlist(productId) {
    event.preventDefault();
    event.stopPropagation();
    
    // Check if user is logged in
    const isLoggedIn = document.querySelector('nav ul li a[href="../logout.php"]') !== null;
    
    if (!isLoggedIn) {
        // Redirect to login page with return URL
        window.location.href = '../login.php?redirect=products/';
        return;
    }
    
    // Change heart icon to filled
    const heartIcon = event.target.closest('.action-btn').querySelector('i');
    if (heartIcon) {
        heartIcon.className = 'fas fa-heart';
        heartIcon.style.color = '#e91e63';
    }
    
    // Send AJAX request to add to wishlist
    fetch(`../wishlist.php?add=${productId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(data => {
        showNotification('Product added to wishlist!', 'success');
    })
    .catch(error => {
        console.error('Error adding to wishlist:', error);
        showNotification('Failed to add product to wishlist', 'error');
    });
}

// Show notification function
function showNotification(message, type = 'info') {
    // Create notification element if it doesn't exist
    let notification = document.getElementById('notification');
    
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'notification';
        document.body.appendChild(notification);
        
        // Add styles if not already in CSS
        const style = document.createElement('style');
        style.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 25px;
                border-radius: 8px;
                color: white;
                font-weight: 600;
                z-index: 1000;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                transform: translateY(-20px);
                opacity: 0;
                transition: all 0.3s ease;
            }
            .notification.show {
                transform: translateY(0);
                opacity: 1;
            }
            .notification.success { background-color: #4caf50; }
            .notification.error { background-color: #f44336; }
            .notification.info { background-color: #3366ff; }
        `;
        document.head.appendChild(style);
    }
    
    // Set notification content and style
    notification.textContent = message;
    notification.className = `notification ${type}`;
    
    // Show notification
    notification.classList.add('show');
    
    // Hide notification after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}

// Add to cart function
function addToCart(productId, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    // Show notification
    showNotification('Adding to cart...', 'info');
    
    // Redirect to cart.php with the product ID
    window.location.href = '../cart.php?add=' + productId;
}