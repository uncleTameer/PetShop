<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$cart = $_SESSION['cart'] ?? [];
$total = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Cart</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4">
  <a class="navbar-brand" href="shop.php">⬅ Back to Shop</a>
  <div class="d-flex align-items-center text-white me-2">
        <?php if (!empty($_SESSION['user']['profilePicture'])): ?>
          <img src="uploads/<?= htmlspecialchars($_SESSION['user']['profilePicture']) ?>" 
               alt="Profile" class="rounded-circle me-2" 
               style="width: 35px; height: 35px; object-fit: cover;">
        <?php else: ?>
          <img src="uploads/default.png" 
               alt="Default" class="rounded-circle me-2" 
               style="width: 35px; height: 35px; object-fit: cover;">
        <?php endif; ?>
  <div class="ms-auto text-white">
    <?php if (isset($_SESSION['user'])): ?>
      <?= htmlspecialchars($_SESSION['user']['name']) ?>
      <a href="myOrders.php" class="btn btn-outline-light btn-sm ms-2">📦 My Orders</a>
      <a href="php/logout.php" class="btn btn-outline-light btn-sm ms-3">Logout</a>
    <?php else: ?>
      <a href="php/login.php" class="btn btn-outline-light btn-sm me-2">Login</a>
      <a href="php/register.php" class="btn btn-outline-light btn-sm">Register</a>
    <?php endif; ?>
  </div>
</nav>

<div class="container py-4">
  <h2 class="mb-4">🛒 Your Cart</h2>

  <?php if (empty($cart)): ?>
    <div class="alert alert-info text-center">Your cart is empty.</div>
  <?php else: ?>
    <form id="updateCartForm">
      <input type="hidden" name="action" value="update">
      <table class="table table-bordered text-center align-middle">
        <thead class="table-dark">
          <tr>
            <th>Product</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Subtotal</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cart as $id => $item): 
            $subtotal = $item['price'] * $item['quantity'];
            $total += $subtotal;
          ?>
            <tr>
              <td><?= htmlspecialchars($item['name']) ?></td>
              <td>₪<?= number_format($item['price'], 2) ?></td>
              <td>
                <input type="number" name="quantities[<?= $id ?>]" value="<?= $item['quantity'] ?>" min="1" class="form-control form-control-sm w-50 mx-auto">
              </td>
              <td>₪<?= number_format($subtotal, 2) ?></td>
              <td>
                <button class="btn btn-sm btn-danger remove-item" data-product-id="<?= $id ?>">❌ Remove</button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="table-light">
          <tr>
            <th colspan="3" class="text-end">Total</th>
            <th>₪<?= number_format($total, 2) ?></th>
            <th></th>
          </tr>
        </tfoot>
      </table>
      <div class="text-end mb-4">
        <button type="submit" class="btn btn-warning">🔄 Update Cart</button>
      </div>
    </form>


    <?php if (!isset($_SESSION['user'])): ?>
  <div class="alert alert-warning text-center mt-4">
    ⚠️ You must <a href="php/login.php">log in</a> to place your order.
  </div>
<?php else: ?>
  <h4>🧾 Checkout</h4>
  <div class="text-center">
    <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#checkoutModal">
      💳 Proceed to Checkout - ₪<?= number_format($total, 2) ?>
    </button>
  </div>
<?php endif; ?>

  <?php endif; ?>
</div>

<!-- Credit Card Checkout Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="checkoutModalLabel">💳 Secure Checkout</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-8">
            <h6 class="mb-3">Order Summary</h6>
            <div class="table-responsive">
              <table class="table table-sm">
                <tbody>
                  <?php foreach ($cart as $id => $item): 
                    $subtotal = $item['price'] * $item['quantity'];
                  ?>
                    <tr>
                      <td><?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?></td>
                      <td class="text-end">₪<?= number_format($subtotal, 2) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                  <tr>
                    <th>Total</th>
                    <th class="text-end">₪<?= number_format($total, 2) ?></th>
                  </tr>
                </tfoot>
              </table>
            </div>
            
            <hr>
            
            <h6 class="mb-3">Payment Information</h6>
            <form id="checkoutForm" method="POST" action="php/submitOrder.php">
              <div class="row g-3">
                <div class="col-12">
                  <label for="cardName" class="form-label">Cardholder Name</label>
                  <input type="text" class="form-control" id="cardName" name="cardName" required 
                         placeholder="Name on card" value="<?= htmlspecialchars($_SESSION['user']['name']) ?>">
                </div>
                
                <div class="col-12">
                  <label for="cardNumber" class="form-label">Card Number</label>
                  <div class="input-group">
                    <input type="text" class="form-control" id="cardNumber" name="cardNumber" required 
                           placeholder="1234 5678 9012 3456" maxlength="19">
                    <span class="input-group-text">
                      <i class="fas fa-credit-card"></i>
                    </span>
                  </div>
                </div>
                
                <div class="col-md-6">
                  <label for="expiryDate" class="form-label">Expiry Date</label>
                  <input type="text" class="form-control" id="expiryDate" name="expiryDate" required 
                         placeholder="MM/YY" maxlength="5">
                </div>
                
                <div class="col-md-6">
                  <label for="cvv" class="form-label">CVV</label>
                  <input type="text" class="form-control" id="cvv" name="cvv" required 
                         placeholder="123" maxlength="4">
                </div>
                
                <div class="col-12">
                  <label for="billingAddress" class="form-label">Billing Address</label>
                  <textarea class="form-control" id="billingAddress" name="billingAddress" rows="2" 
                            placeholder="Enter your billing address"></textarea>
                </div>
              </div>
            </form>
          </div>
          
          <div class="col-md-4">
            <div class="card bg-light">
              <div class="card-body">
                <h6 class="card-title">🔒 Secure Payment</h6>
                <p class="card-text small">
                  <i class="fas fa-shield-alt text-success"></i> Your payment information is encrypted and secure.<br><br>
                  <i class="fas fa-lock text-success"></i> We use industry-standard SSL encryption.<br><br>
                  <i class="fas fa-check-circle text-success"></i> Your card details are never stored on our servers.
                </p>
                <div class="text-center">
                  <img src="https://cdn-icons-png.flaticon.com/512/179/179249.png" alt="Visa" style="height: 30px; margin: 2px;">
                  <img src="https://cdn-icons-png.flaticon.com/512/179/179457.png" alt="Mastercard" style="height: 30px; margin: 2px;">
                  <img src="https://cdn-icons-png.flaticon.com/512/179/179308.png" alt="American Express" style="height: 30px; margin: 2px;">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" form="checkoutForm" class="btn btn-success">
          <i class="fas fa-lock"></i> Pay ₪<?= number_format($total, 2) ?>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Toast Notification -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div id="cartToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header">
      <strong class="me-auto">🛒 Cart Update</strong>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body" id="toastMessage">
      <!-- Message will be inserted here -->
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Credit card form formatting and validation
  const cardNumber = document.getElementById('cardNumber');
  const expiryDate = document.getElementById('expiryDate');
  const cvv = document.getElementById('cvv');
  
  // Format card number with spaces
  if (cardNumber) {
    cardNumber.addEventListener('input', function(e) {
      let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
      let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
      e.target.value = formattedValue;
    });
  }
  
  // Format expiry date
  if (expiryDate) {
    expiryDate.addEventListener('input', function(e) {
      let value = e.target.value.replace(/\D/g, '');
      if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
      }
      e.target.value = value;
    });
  }
  
  // Format CVV (numbers only)
  if (cvv) {
    cvv.addEventListener('input', function(e) {
      e.target.value = e.target.value.replace(/\D/g, '');
    });
  }
  
  // Handle checkout form submission
  const checkoutForm = document.getElementById('checkoutForm');
  if (checkoutForm) {
    checkoutForm.addEventListener('submit', function(e) {
      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      
      // Show loading state
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Payment...';
      submitBtn.disabled = true;
      
      // Simulate payment processing delay
      setTimeout(() => {
        // Continue with normal form submission
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
      }, 2000);
    });
  }

  // Handle cart update form
  const updateCartForm = document.getElementById('updateCartForm');
  if (updateCartForm) {
    updateCartForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      const button = this.querySelector('button[type="submit"]');
      const originalText = button.innerHTML;
      
      // Show loading state
      button.innerHTML = '⏳ Updating...';
      button.disabled = true;
      
      fetch('php/cartOperations.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        // Show toast message
        const toast = document.getElementById('cartToast');
        const toastMessage = document.getElementById('toastMessage');
        
        if (data.success) {
          // Update cart count in navbar
          const cartButton = document.querySelector('a[href="cart.php"]');
          if (cartButton) {
            cartButton.innerHTML = `🛒 Cart (${data.cartCount})`;
          }
          
          // Update total in footer
          const totalCell = document.querySelector('tfoot th:nth-child(4)');
          if (totalCell) {
            totalCell.textContent = `₪${data.newTotal}`;
          }
          
          toastMessage.innerHTML = `
            <div class="text-success">
              <strong>✅ ${data.message}</strong><br>
              <small>New total: ₪${data.newTotal}</small>
            </div>
          `;
        } else {
          toastMessage.innerHTML = `
            <div class="text-danger">
              <strong>❌ ${data.message}</strong>
            </div>
          `;
        }
        
        // Show the toast
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
      })
      .catch(error => {
        console.error('Error:', error);
        const toast = document.getElementById('cartToast');
        const toastMessage = document.getElementById('toastMessage');
        toastMessage.innerHTML = `
          <div class="text-danger">
            <strong>❌ An error occurred. Please try again.</strong>
          </div>
        `;
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
      })
      .finally(() => {
        // Restore button state
        button.innerHTML = originalText;
        button.disabled = false;
      });
    });
  }

  // Handle remove item buttons
  document.querySelectorAll('.remove-item').forEach(button => {
    button.addEventListener('click', function() {
      const productId = this.getAttribute('data-product-id');
      const row = this.closest('tr');
      const originalText = this.innerHTML;
      
      // Show loading state
      this.innerHTML = '⏳ Removing...';
      this.disabled = true;
      
      fetch(`php/cartOperations.php?action=remove&id=${productId}`, {
        method: 'GET'
      })
      .then(response => response.json())
      .then(data => {
        // Show toast message
        const toast = document.getElementById('cartToast');
        const toastMessage = document.getElementById('toastMessage');
        
        if (data.success) {
          // Remove the row from the table
          row.remove();
          
          // Update cart count in navbar
          const cartButton = document.querySelector('a[href="cart.php"]');
          if (cartButton) {
            cartButton.innerHTML = `🛒 Cart (${data.cartCount})`;
          }
          
          // Check if cart is empty
          const tbody = document.querySelector('tbody');
          if (tbody.children.length === 0) {
            location.reload(); // Reload to show empty cart message
            return;
          }
          
          // Recalculate total
          let total = 0;
          document.querySelectorAll('tbody tr').forEach(row => {
            const subtotalText = row.querySelector('td:nth-child(4)').textContent;
            const subtotal = parseFloat(subtotalText.replace('₪', '').replace(',', ''));
            total += subtotal;
          });
          
          // Update total in footer
          const totalCell = document.querySelector('tfoot th:nth-child(4)');
          if (totalCell) {
            totalCell.textContent = `₪${total.toFixed(2)}`;
          }
          
          toastMessage.innerHTML = `
            <div class="text-success">
              <strong>✅ ${data.message}</strong><br>
              <small>Items in cart: ${data.cartCount}</small>
            </div>
          `;
        } else {
          toastMessage.innerHTML = `
            <div class="text-danger">
              <strong>❌ ${data.message}</strong>
            </div>
          `;
        }
        
        // Show the toast
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
      })
      .catch(error => {
        console.error('Error:', error);
        const toast = document.getElementById('cartToast');
        const toastMessage = document.getElementById('toastMessage');
        toastMessage.innerHTML = `
          <div class="text-danger">
            <strong>❌ An error occurred. Please try again.</strong>
          </div>
        `;
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
      })
      .finally(() => {
        // Restore button state if not removed
        if (this.parentNode) {
          this.innerHTML = originalText;
          this.disabled = false;
        }
      });
    });
  });
});
</script>

</body>
</html>
