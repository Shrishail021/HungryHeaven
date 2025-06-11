// Main JavaScript for Hungry Heaven

document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Handle "Add to Cart" buttons
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const itemId = this.getAttribute('data-id');
            
            // AJAX request to add item to cart
            fetch('customer/ajax/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'item_id=' + itemId + '&quantity=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alert(data.message);
                    
                    // Update cart count
                    const cartBadge = document.querySelector('.fa-shopping-cart').nextElementSibling;
                    if (cartBadge) {
                        cartBadge.textContent = data.cart_count;
                    } else {
                        // Create badge if it doesn't exist
                        const badge = document.createElement('span');
                        badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                        badge.textContent = data.cart_count;
                        document.querySelector('.fa-shopping-cart').parentNode.appendChild(badge);
                    }
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding item to cart');
            });
        });
    });
    
    // Handle quantity input in cart
    const quantityInputs = document.querySelectorAll('.item-quantity');
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            const itemId = this.getAttribute('data-id');
            const quantity = this.value;
            
            if (quantity < 1) {
                this.value = 1;
                return;
            }
            
            // AJAX request to update cart
            fetch('ajax/update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'item_id=' + itemId + '&quantity=' + quantity
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update subtotal
                    document.getElementById('subtotal-' + itemId).textContent = data.subtotal;
                    
                    // Update cart total
                    document.getElementById('cart-total').textContent = data.total;
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating cart');
            });
        });
    });
    
    // Handle remove from cart buttons
    const removeButtons = document.querySelectorAll('.remove-from-cart');
    removeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const itemId = this.getAttribute('data-id');
            
            // AJAX request to remove item from cart
            fetch('ajax/remove_from_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'item_id=' + itemId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove item row from table
                    this.closest('tr').remove();
                    
                    // Update cart total
                    document.getElementById('cart-total').textContent = data.total;
                    
                    // Update cart count in navbar
                    const cartBadge = document.querySelector('.fa-shopping-cart').nextElementSibling;
                    if (cartBadge) {
                        if (data.cart_count > 0) {
                            cartBadge.textContent = data.cart_count;
                        } else {
                            cartBadge.remove();
                        }
                    }
                    
                    // Show empty cart message if cart is empty
                    if (data.cart_count === 0) {
                        const cartTable = document.querySelector('.cart-table');
                        if (cartTable) {
                            cartTable.innerHTML = '<tr><td colspan="5" class="text-center py-4">Your cart is empty</td></tr>';
                        }
                    }
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error removing item from cart');
            });
        });
    });
    
    // Animate counters
    const counters = document.querySelectorAll('.counter-number');
    if (counters.length > 0) {
        const counterObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counter = entry.target;
                    const target = parseInt(counter.getAttribute('data-count'));
                    let count = 0;
                    const updateCounter = () => {
                        const increment = target / 100;
                        if (count < target) {
                            count += increment;
                            counter.innerText = Math.ceil(count);
                            setTimeout(updateCounter, 20);
                        } else {
                            counter.innerText = target;
                        }
                    };
                    updateCounter();
                    observer.unobserve(counter);
                }
            });
        });
        
        counters.forEach(counter => {
            counterObserver.observe(counter);
        });
    }
    
    // Handle delivery type selection in checkout
    const deliveryTypeRadios = document.querySelectorAll('input[name="delivery_type"]');
    if (deliveryTypeRadios.length > 0) {
        deliveryTypeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'delivery') {
                    document.getElementById('address-section').classList.remove('d-none');
                    document.getElementById('payment-section').classList.remove('d-none');
                } else {
                    document.getElementById('address-section').classList.add('d-none');
                    document.getElementById('payment-section').classList.add('d-none');
                }
            });
        });
    }
    
    // Handle payment method selection in checkout
    const paymentMethodRadios = document.querySelectorAll('input[name="payment_method"]');
    if (paymentMethodRadios.length > 0) {
        paymentMethodRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'razorpay') {
                    document.getElementById('razorpay-section').classList.remove('d-none');
                    document.getElementById('cod-section').classList.add('d-none');
                } else if (this.value === 'cod') {
                    document.getElementById('razorpay-section').classList.add('d-none');
                    document.getElementById('cod-section').classList.remove('d-none');
                }
            });
        });
    }
    
    // Handle Google Maps integration for address
    if (document.getElementById('map')) {
        // Initialize Google Maps for address selection
        // Note: This is just a placeholder. You'll need to add your Google Maps API key and implementation.
        function initMap() {
            const defaultLocation = { lat: 20.5937, lng: 78.9629 }; // Default to center of India
            const map = new google.maps.Map(document.getElementById('map'), {
                center: defaultLocation,
                zoom: 12
            });
            
            const marker = new google.maps.Marker({
                position: defaultLocation,
                map: map,
                draggable: true
            });
            
            // Update coordinates when marker is dragged
            google.maps.event.addListener(marker, 'dragend', function() {
                const position = marker.getPosition();
                document.getElementById('latitude').value = position.lat();
                document.getElementById('longitude').value = position.lng();
                
                // Get address from coordinates using reverse geocoding
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode({ location: position }, function(results, status) {
                    if (status === 'OK') {
                        if (results[0]) {
                            document.getElementById('address').value = results[0].formatted_address;
                        }
                    }
                });
            });
            
            // Try to get user's current location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    map.setCenter(userLocation);
                    marker.setPosition(userLocation);
                    
                    document.getElementById('latitude').value = userLocation.lat;
                    document.getElementById('longitude').value = userLocation.lng;
                    
                    // Get address from coordinates
                    const geocoder = new google.maps.Geocoder();
                    geocoder.geocode({ location: userLocation }, function(results, status) {
                        if (status === 'OK') {
                            if (results[0]) {
                                document.getElementById('address').value = results[0].formatted_address;
                            }
                        }
                    });
                });
            }
        }
        
        // Call initMap function when Google Maps API is loaded
        window.initMap = initMap;
    }
    
    // Initialize Razorpay checkout
    if (document.getElementById('razorpay-button')) {
        document.getElementById('razorpay-button').addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get order details from form
            const formData = new FormData(document.getElementById('checkout-form'));
            
            // Create Razorpay order
            fetch('ajax/create_razorpay_order.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Initialize Razorpay payment
                    const options = {
                        key: data.key_id,
                        amount: data.amount,
                        currency: 'INR',
                        name: 'Hungry Heaven',
                        description: 'Food Order Payment',
                        order_id: data.order_id,
                        handler: function(response) {
                            // Submit payment details to server
                            document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                            document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
                            document.getElementById('razorpay_signature').value = response.razorpay_signature;
                            document.getElementById('checkout-form').submit();
                        },
                        prefill: {
                            name: data.customer_name,
                            email: data.customer_email,
                            contact: data.customer_phone
                        },
                        theme: {
                            color: '#e74c3c'
                        }
                    };
                    
                    const rzp = new Razorpay(options);
                    rzp.open();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error creating payment');
            });
        });
    }
});
