/**
 * Product Details Page JavaScript
 * Handles all interactive functionality for the product details page
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tab functionality
    initTabs();
    
    // Initialize zoom functionality
    initZoom();
    
    // Initialize variant selection
    initVariantSelection();
    
    // Initialize back to top button
    initBackToTop();
    
    // Initialize quantity controls
    initQuantityControls();
    
    // Update cart and wishlist counts from localStorage if available
    updateCountsFromStorage();
});

/**
 * Initialize tab functionality
 */
function initTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons and panels
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanels.forEach(panel => panel.classList.remove('active'));
            
            // Add active class to current button
            this.classList.add('active');
            
            // Show the corresponding panel
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });
}

/**
 * Initialize zoom functionality
 */
function initZoom() {
    const mainImage = document.getElementById('product-main-image');
    const zoomContainer = document.querySelector('.zoom-container');
    const zoomImage = document.querySelector('.zoom-image');
    const zoomClose = document.querySelector('.zoom-close');
    
    if (mainImage && zoomContainer && zoomImage && zoomClose) {
        // Open zoom view when clicking on the zoom icon or main image
        document.querySelector('.zoom-icon')?.addEventListener('click', openZoomView);
        
        // Close zoom view when clicking on the close button
        zoomClose.addEventListener('click', closeZoomView);
        
        // Close zoom view when clicking outside the image
        zoomContainer.addEventListener('click', function(e) {
            if (e.target === zoomContainer) {
                closeZoomView();
            }
        });
        
        // Close zoom view when pressing escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && zoomContainer.classList.contains('active')) {
                closeZoomView();
            }
        });
    }
}

/**
 * Open zoom view
 */
function openZoomView() {
    const mainImage = document.getElementById('product-main-image');
    const zoomContainer = document.querySelector('.zoom-container');
    const zoomImage = document.querySelector('.zoom-image');
    
    if (mainImage && zoomContainer && zoomImage) {
        zoomImage.src = mainImage.src;
        zoomContainer.classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    }
}

/**
 * Close zoom view
 */
function closeZoomView() {
    const zoomContainer = document.querySelector('.zoom-container');
    
    if (zoomContainer) {
        zoomContainer.classList.remove('active');
        document.body.style.overflow = ''; // Re-enable scrolling
    }
}

/**
 * Initialize variant selection
 */
function initVariantSelection() {
    const colorOptions = document.querySelectorAll('.color-option');
    const variantOptions = document.querySelectorAll('.variant-option');
    
    // Color options selection
    colorOptions.forEach(option => {
        option.addEventListener('click', function() {
            colorOptions.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
        });
    });
    
    // Variant options selection
    variantOptions.forEach(option => {
        option.addEventListener('click', function() {
            variantOptions.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
        });
    });
}

/**
 * Initialize back to top button
 */
function initBackToTop() {
    const backToTopButton = document.getElementById('backToTop');
    
    if (backToTopButton) {
        // Show/hide button based on scroll position
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                backToTopButton.style.opacity = '1';
                backToTopButton.style.pointerEvents = 'auto';
            } else {
                backToTopButton.style.opacity = '0';
                backToTopButton.style.pointerEvents = 'none';
            }
        });
        
        // Scroll to top when clicking the button
        backToTopButton.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
}

/**
 * Initialize quantity controls
 */
function initQuantityControls() {
    const quantityInput = document.getElementById('quantity');
    
    if (quantityInput) {
        // Ensure quantity is within valid range when manually changed
        quantityInput.addEventListener('change', function() {
            const min = parseInt(this.getAttribute('min') || 1);
            const max = parseInt(this.getAttribute('max') || 99);
            const value = parseInt(this.value);
            
            if (value < min) this.value = min;
            if (value > max) this.value = max;
        });
    }
}

/**
 * Increment quantity
 * @param {number} max - Maximum allowed quantity
 */
function incrementQuantity(max) {
    const input = document.getElementById('quantity');
    if (!input) return;
    
    const currentValue = parseInt(input.value);
    const maxValue = max || parseInt(input.getAttribute('max') || 99);
    
    if (currentValue < maxValue) {
        input.value = currentValue + 1;
    }
}

/**
 * Decrement quantity
 */
function decrementQuantity() {
    const input = document.getElementById('quantity');
    if (!input) return;
    
    const currentValue = parseInt(input.value);
    const minValue = parseInt(input.getAttribute('min') || 1);
    
    if (currentValue > minValue) {
        input.value = currentValue - 1;
    }
}

/**
 * Add product to wishlist
 * @param {number} productId - Product ID to add to wishlist
 */
function addToWishlist(productId) {
    // Check if user is logged in
    const isLoggedIn = document.querySelector('nav ul li a[href="../logout.php"]') !== null;
    
    if (!isLoggedIn) {
        // Redirect to login page with return URL
        window.location.href = '../login.php?redirect=products/details.php?id=' + productId;
        return;
    }
    
    // Add animation effect to wishlist button
    const wishlistBtn = document.querySelector('.btn-wishlist');
    if (wishlistBtn) {
        wishlistBtn.innerHTML = '<i class="fas fa-heart"></i>';
        wishlistBtn.classList.add('active');
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
        // Show success message
        showNotification('Product added to wishlist!', 'success');
    })
    .catch(error => {
        console.error('Error adding to wishlist:', error);
        showNotification('Failed to add product to wishlist', 'error');
    });
}

/**
 * Show notification message
 * @param {string} message - Message to display
 * @param {string} type - Type of notification (success, error, info)
 */
function showNotification(message, type = 'info') {
    // Create notification element if it doesn't exist
    let notification = document.getElementById('notification');
    
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'notification';
        document.body.appendChild(notification);
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

/**
 * Update wishlist count
 * @param {number} count - Wishlist count
 */
function updateWishlistCount(count) {
    const wishlistCount = document.querySelector('.wishlist-count');
    if (wishlistCount) {
        // Convert to string and check length
        const countStr = count.toString();
        
        // If count is greater than 99, display 99+
        if (count > 99) {
            wishlistCount.textContent = '99+';
            wishlistCount.classList.add('large');
        } else {
            wishlistCount.textContent = countStr;
            // Add 'large' class for double-digit numbers
            if (countStr.length > 1) {
                wishlistCount.classList.add('large');
            } else {
                wishlistCount.classList.remove('large');
            }
        }
    }
}

/**
 * Update cart count
 * @param {number} count - Cart count
 */
function updateCartCount(count) {
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
        // Convert to string and check length
        const countStr = count.toString();
        
        // If count is greater than 99, display 99+
        if (count > 99) {
            cartCount.textContent = '99+';
            cartCount.classList.add('large');
        } else {
            cartCount.textContent = countStr;
            // Add 'large' class for double-digit numbers
            if (countStr.length > 1) {
                cartCount.classList.add('large');
            } else {
                cartCount.classList.remove('large');
            }
        }
    }
}

/**
 * Update counts from localStorage
 */
function updateCountsFromStorage() {
    // Update wishlist count
    const wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
    updateWishlistCount(wishlist.length);
    
    // Update cart count (if you have cart data in localStorage)
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    updateCartCount(cart.length);
}

/**
 * Show notification
 * @param {string} message - Notification message
 * @param {string} type - Notification type (success, error, info)
 */
function showNotification(message, type = 'info') {
    // Check if notification container exists, create if not
    let notificationContainer = document.querySelector('.notification-container');
    
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.className = 'notification-container';
        document.body.appendChild(notificationContainer);
        
        // Add styles if not already in CSS
        if (!document.getElementById('notification-styles')) {
            const style = document.createElement('style');
            style.id = 'notification-styles';
            style.textContent = `
                .notification-container {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                }
                .notification {
                    padding: 15px 20px;
                    margin-bottom: 10px;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    min-width: 300px;
                    max-width: 400px;
                    transform: translateX(120%);
                    transition: transform 0.3s ease;
                }
                .notification.show {
                    transform: translateX(0);
                }
                .notification-success {
                    background-color: #d4edda;
                    color: #155724;
                    border-left: 4px solid #28a745;
                }
                .notification-error {
                    background-color: #f8d7da;
                    color: #721c24;
                    border-left: 4px solid #dc3545;
                }
                .notification-info {
                    background-color: #d1ecf1;
                    color: #0c5460;
                    border-left: 4px solid #17a2b8;
                }
                .notification-close {
                    background: none;
                    border: none;
                    color: inherit;
                    font-size: 16px;
                    cursor: pointer;
                    margin-left: 10px;
                    opacity: 0.7;
                }
                .notification-close:hover {
                    opacity: 1;
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    // Create message element
    const messageElement = document.createElement('div');
    messageElement.className = 'notification-message';
    messageElement.textContent = message;
    
    // Create close button
    const closeButton = document.createElement('button');
    closeButton.className = 'notification-close';
    closeButton.innerHTML = '&times;';
    closeButton.addEventListener('click', function() {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    });
    
    // Append elements to notification
    notification.appendChild(messageElement);
    notification.appendChild(closeButton);
    
    // Append notification to container
    notificationContainer.appendChild(notification);
    
    // Show notification with slight delay for transition
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Auto-remove notification after 5 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 5000);
}

/**
 * Notify when product is available
 * @param {number} productId - Product ID
 */
function notifyWhenAvailable(productId) {
    // Show modal to collect email
    showEmailCollectionModal(productId);
}

/**
 * Show email collection modal
 * @param {number} productId - Product ID
 */
function showEmailCollectionModal(productId) {
    // Check if modal already exists
    let modal = document.getElementById('email-modal');
    
    if (!modal) {
        // Create modal container
        modal = document.createElement('div');
        modal.id = 'email-modal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <span class="modal-close">&times;</span>
                <h3>Get notified when this product is back in stock</h3>
                <p>We'll send you an email once the product becomes available.</p>
                <form id="notify-form">
                    <input type="hidden" name="product_id" value="${productId}">
                    <div class="form-group">
                        <label for="notify-email">Email address</label>
                        <input type="email" id="notify-email" name="email" required placeholder="Your email address">
                    </div>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-bell"></i> Notify Me
                    </button>
                </form>
            </div>
        `;
        
        // Add styles if not already in CSS
        if (!document.getElementById('modal-styles')) {
            const style = document.createElement('style');
            style.id = 'modal-styles';
            style.textContent = `
                .modal {
                    display: none;
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0,0,0,0.7);
                    z-index: 1001;
                    align-items: center;
                    justify-content: center;
                }
                .modal.show {
                    display: flex;
                    animation: fadeIn 0.3s ease;
                }
                .modal-content {
                    background-color: white;
                    padding: 30px;
                    border-radius: 12px;
                    max-width: 500px;
                    width: 90%;
                    box-shadow: 0 5px 30px rgba(0,0,0,0.3);
                    position: relative;
                    transform: scale(0.9);
                    transition: transform 0.3s ease;
                }
                .modal.show .modal-content {
                    transform: scale(1);
                }
                .modal-close {
                    position: absolute;
                    top: 15px;
                    right: 15px;
                    font-size: 24px;
                    cursor: pointer;
                    color: #aaa;
                    transition: color 0.3s ease;
                }
                .modal-close:hover {
                    color: #333;
                }
                .form-group {
                    margin-bottom: 20px;
                }
                .form-group label {
                    display: block;
                    margin-bottom: 8px;
                    font-weight: 600;
                }
                .form-group input {
                    width: 100%;
                    padding: 12px 15px;
                    border: 2px solid #ddd;
                    border-radius: 8px;
                    font-size: 16px;
                    transition: border-color 0.3s ease;
                }
                .form-group input:focus {
                    border-color: var(--primary);
                    outline: none;
                }
            `;
            document.head.appendChild(style);
        }
        
        // Append modal to body
        document.body.appendChild(modal);
        
        // Add event listeners
        const closeBtn = modal.querySelector('.modal-close');
        closeBtn.addEventListener('click', function() {
            closeModal(modal);
        });
        
        // Close modal when clicking outside content
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal(modal);
            }
        });
        
        // Handle form submission
        const form = modal.querySelector('#notify-form');
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = this.querySelector('#notify-email').value;
            
            // Here you would typically send this data to your server
            // For now, we'll just simulate a successful submission
            
            // Show success message
            form.innerHTML = `
                <div class="success-message">
                    <i class="fas fa-check-circle" style="font-size: 48px; color: #28a745; margin-bottom: 15px;"></i>
                    <h3>Thank you!</h3>
                    <p>We'll notify you at <strong>${email}</strong> when this product is back in stock.</p>
                </div>
            `;
            
            // Close modal after 3 seconds
            setTimeout(() => {
                closeModal(modal);
            }, 3000);
        });
    }
    
    // Show modal
    modal.classList.add('show');
    document.body.style.overflow = 'hidden'; // Prevent scrolling
}

/**
 * Close modal
 * @param {HTMLElement} modal - Modal element
 */
function closeModal(modal) {
    modal.classList.remove('show');
    document.body.style.overflow = ''; // Re-enable scrolling
}

// Review management
document.addEventListener('DOMContentLoaded', function() {
    // Edit review functionality
    const editReviewModal = document.getElementById('editReviewModal');
    const editReviewForm = document.getElementById('editReviewForm');
    const editReviewId = document.getElementById('edit-review-id');
    const editReviewComment = document.getElementById('edit-review-comment');
    const cancelEditBtn = document.getElementById('cancelEditReview');
    
    // Delete review functionality
    const deleteReviewModal = document.getElementById('deleteReviewModal');
    const deleteReviewForm = document.getElementById('deleteReviewForm');
    const deleteReviewId = document.getElementById('delete-review-id');
    const deleteProductId = document.getElementById('delete-product-id');
    const cancelDeleteBtn = document.getElementById('cancelDeleteReview');
    
    // Close modals when clicking the X
    document.querySelectorAll('.close-modal').forEach(function(closeBtn) {
        closeBtn.addEventListener('click', function() {
            editReviewModal.style.display = 'none';
            deleteReviewModal.style.display = 'none';
        });
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === editReviewModal) {
            editReviewModal.style.display = 'none';
        }
        if (event.target === deleteReviewModal) {
            deleteReviewModal.style.display = 'none';
        }
    });
    
    // Edit review button click
    document.querySelectorAll('.btn-edit-review').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const reviewId = this.getAttribute('data-review-id');
            const rating = this.getAttribute('data-rating');
            const comment = this.getAttribute('data-comment');
            
            // Set form values
            editReviewId.value = reviewId;
            editReviewComment.value = comment;
            
            // Set rating
            document.querySelector(`#edit-star${rating}`).checked = true;
            
            // Show modal
            editReviewModal.style.display = 'block';
        });
    });
    
    // Delete review button click
    document.querySelectorAll('.btn-delete-review').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const reviewId = this.getAttribute('data-review-id');
            const productId = this.getAttribute('data-product-id');
            
            // Set form values
            deleteReviewId.value = reviewId;
            deleteProductId.value = productId;
            
            // Show modal
            deleteReviewModal.style.display = 'block';
        });
    });
    
    // Cancel buttons
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', function() {
            editReviewModal.style.display = 'none';
        });
    }
    
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', function() {
            deleteReviewModal.style.display = 'none';
        });
    }
});

/**
 * Update notification count display
 * @param {string} selector - CSS selector for the count element
 * @param {number} count - The count to display
 */
function updateNotificationCount(selector, count) {
    const countElement = document.querySelector(selector);
    if (!countElement) return;
    
    // For very large numbers, display 99+
    if (count > 99) {
        countElement.textContent = '99+';
        countElement.classList.add('large');
    } else {
        countElement.textContent = count;
        // Add 'large' class for double-digit numbers
        if (count > 9) {
            countElement.classList.add('large');
        } else {
            countElement.classList.remove('large');
        }
    }
}

// Example usage:
// updateNotificationCount('.fab-count.cart-count', 15);
// updateNotificationCount('.fab-count.wishlist-count', 8);