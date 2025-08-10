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
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="../js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4">
  <a class="navbar-brand" href="shop.php">⬅ Back to Shop</a>
  <div class="d-flex align-items-center text-white me-2">
        <?php if (!empty($_SESSION['user']['profilePicture'])): ?>
          <img src="../uploads/<?= htmlspecialchars($_SESSION['user']['profilePicture']) ?>" 
               alt="Profile" class="rounded-circle me-2" 
               style="width: 35px; height: 35px; object-fit: cover;">
        <?php else: ?>
          <img src="../uploads/default.png" 
               alt="Default" class="rounded-circle me-2" 
               style="width: 35px; height: 35px; object-fit: cover;">
        <?php endif; ?>
  <div class="ms-auto text-white">
    <?php if (isset($_SESSION['user'])): ?>
      <?= htmlspecialchars($_SESSION['user']['name']) ?>
      <a href="myOrders.php" class="btn btn-outline-light btn-sm ms-2">📦 My Orders</a>
      <a href="logout.php" class="btn btn-outline-light btn-sm ms-3">Logout</a>
    <?php else: ?>
      <a href="login.php" class="btn btn-outline-light btn-sm me-2">Login</a>
<a href="register.php" class="btn btn-outline-light btn-sm">Register</a>
    <?php endif; ?>
  </div>
</nav>

<div class="container py-4">
  <h2 class="mb-4">🛒 Your Cart</h2>

  <?php if (empty($cart)): ?>
    <div class="alert alert-info text-center">Your cart is empty.</div>
  <?php else: ?>
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
                <form method="POST" action="removeFromCart.php" style="display: inline;">
                  <input type="hidden" name="productId" value="<?= $id ?>">
                  <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remove this item?')">❌ Remove</button>
                </form>
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
      
      <!-- Update Cart Form -->
      <form method="POST" action="updateCart.php">
        <?php foreach ($cart as $id => $item): ?>
          <input type="hidden" name="quantities[<?= $id ?>]" value="<?= $item['quantity'] ?>" id="qty_<?= $id ?>">
        <?php endforeach; ?>
        <div class="text-end mb-4">
          <button type="submit" class="btn btn-warning">🔄 Update Cart</button>
        </div>
      </form>


    <?php if (!isset($_SESSION['user'])): ?>
  <div class="alert alert-info text-center mt-4">
    <h5>🛒 Guest Checkout</h5>
    <p>You can place your order as a guest, or <a href="login.php" class="alert-link">log in</a> to save your information for faster checkout.</p>
    <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#checkoutModal">
      💳 Proceed to Checkout - ₪<?= number_format($total, 2) ?>
    </button>
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
            <form id="checkoutForm" method="POST" action="submitOrder.php">
              <!-- Fulfillment Method -->
              <h6 class="mb-3">Fulfillment Method</h6>
              <div class="row mb-4">
                <div class="col-12">
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="fulfillmentType" id="fulfillShip" value="shipping" checked>
                    <label class="form-check-label" for="fulfillShip">🚚 Ship to Address</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="fulfillmentType" id="fulfillPickup" value="pickup">
                    <label class="form-check-label" for="fulfillPickup">🏬 Reserve for In-Store Pickup</label>
                  </div>
                </div>
                <div class="col-12 small text-muted mt-2" id="fulfillmentHelp">
                  Choose pickup if you want us to hold the items until you collect them.
                </div>
              </div>
              <?php if (!isset($_SESSION['user'])): ?>
              <!-- Guest Information (always collected; could be simplified for pickup) -->
              <h6 class="mb-3">Customer Information</h6>
              <div class="row g-3 mb-4">
                <div class="col-12">
                  <label for="guestName" class="form-label">Full Name</label>
                  <input type="text" class="form-control" id="guestName" name="guestName" required 
                         placeholder="Your full name">
                </div>
                <div class="col-12">
                  <label for="guestEmail" class="form-label">Email Address</label>
                  <input type="email" class="form-control" id="guestEmail" name="guestEmail" required 
                         placeholder="your@email.com">
                </div>
                <div class="col-12">
                  <label for="guestAddress" class="form-label">Shipping Address</label>
                  <textarea class="form-control" id="guestAddress" name="guestAddress" rows="2" 
                            placeholder="Your shipping address" required></textarea>
                </div>
                <div class="col-12">
                  <label for="guestZipCode" class="form-label">ZIP/Postal Code</label>
                  <input type="text" class="form-control" id="guestZipCode" name="guestZipCode" required 
                         placeholder="ZIP or postal code">
                </div>
              </div>
              <hr>
              <?php endif; ?>
              
              <h6 class="mb-3">Payment Information</h6>
              <div class="row g-3">
                <div class="col-12">
                  <label for="cardName" class="form-label">Cardholder Name</label>
                  <input type="text" class="form-control" id="cardName" name="cardName" required 
                         placeholder="Name on card" value="<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['name']) : '' ?>">
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
                
                <div class="col-12 shipping-only">
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
  
  // Sync quantity inputs with hidden form inputs
  document.querySelectorAll('input[name^="quantities["]').forEach(input => {
    input.addEventListener('change', function() {
      const productId = this.name.match(/\[(.*?)\]/)[1];
      const hiddenInput = document.getElementById('qty_' + productId);
      if (hiddenInput) {
        hiddenInput.value = this.value;
      }
    });
  });
  // Toggle address fields for pickup vs shipping
  const fulfillRadios = document.querySelectorAll('input[name="fulfillmentType"]');
  const shippingOnlyEls = document.querySelectorAll('.shipping-only');
  function updateFulfillmentVisibility() {
    const isPickup = document.getElementById('fulfillPickup').checked;
    shippingOnlyEls.forEach(el => {
      el.style.display = isPickup ? 'none' : '';
      // If hiding, clear inputs
      if (isPickup) {
        el.querySelectorAll('input, textarea').forEach(inp => inp.removeAttribute('required'));
      } else {
        el.querySelectorAll('input, textarea').forEach(inp => inp.setAttribute('required','required'));
      }
    });
  }
  fulfillRadios.forEach(r => r.addEventListener('change', updateFulfillmentVisibility));
  updateFulfillmentVisibility();
});
</script>

</body>
</html>
