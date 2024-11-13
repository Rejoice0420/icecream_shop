<?php session_start(); ?>
<!DOCTYPE html>
<html>
    <head>
        <title>Checkout</title>
    </head>
    <body>
        <h2>Order Summary</h2>
        <div id="order-summary">
        <script>

            let grandTotal = 0;  // Declare grandTotal globally

            document.addEventListener('DOMContentLoaded', () => {
                const cartData = JSON.parse(sessionStorage.getItem('cart'));
                const orderSummary = document.getElementById('order-summary');

                if (cartData && cartData.length > 0) {
                    cartData.forEach(item => {
                        const toppingTotal = item.selectedToppings.length * 0.5;
                        const itemTotal = (item.productPrice * item.quantity) + (toppingTotal * item.quantity);

                        orderSummary.innerHTML += `
                            <div>
                                <h3>${item.productName}</h3>
                                <p>Quantity: ${item.quantity}</p>
                                <p>Price per item: $${item.productPrice}</p>
                                <p>Toppings: ${item.selectedToppings.join(', ')}</p>
                                <p>Topping Charge: $${(toppingTotal * item.quantity).toFixed(2)}</p>
                                <p>Total: $${itemTotal.toFixed(2)}</p>
                            </div><hr>
                        `;

                        grandTotal += itemTotal; // Update global grandTotal
                    });

                    // Display the grand total in the order summary
                    orderSummary.innerHTML += `<h3 id="grandTotal">Grand Total: $${grandTotal.toFixed(2)}</h3>`;
                } else {
                    orderSummary.innerHTML = "<p>No items in the cart.</p>";
                }
            });
        </script>

        </div>
        <button onclick="showCustomerForm()">Proceed to Pay</button>
        <div id="customer-form" style="display:none;">
            <form id="customerDetailsForm">
                <label>Name: <input type="text" name="name" required></label><br>
                <!-- <label>Email: <input type="email" name="email" required></label><br> -->
                <label>Phone: <input type="text" name="phone" required></label><br>
                <button type="button" onclick="submitOrder()">Submit Order</button>
            </form>
        </div>

        <script>
            // Show the customer form when "Proceed to Pay" button is clicked
            function showCustomerForm() {
                document.getElementById('customer-form').style.display = 'block';
            }

            // Submit the order when the user clicks "Submit Order" after filling in their details
            async function submitOrder() {
                // Ensure the form is valid before proceeding
                const form = document.getElementById('customerDetailsForm');
                if (!form.checkValidity()) {
                    alert("Please fill in all fields");
                    return; // Stop submission if form is invalid
                }

                const formData = new FormData(form);
                const cartData = sessionStorage.getItem('cart');
                const grandTotal = document.getElementById('grandTotal').textContent.replace('Grand Total: $', '');

                formData.append('cart', cartData);
                formData.append('grandTotal', grandTotal);

                const response = await fetch('submit_order.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    alert('Order placed successfully!');
                    sessionStorage.removeItem('cart');
                    window.location.href = 'index.html'; // Redirect to confirmation page
                } else {
                    alert('Error placing order');
                }
            }
        </script>
    </body>
</html>
