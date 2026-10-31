<?php
session_start();

// Initialize session cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Product database array matching your Figma design
$products = [
    1 => [
        'id' => 1,
        'title' => 'MOEV SCALP CLARIFYING SHAMPOO 500ml',
        'desc' => 'Removes excess oil and buildup for a cleaner, lighter scalp without stripping natural moisture or leaving tightness. Helps reduce the appearance of visible flakes while maintaining scalp balance with a mildly acidic pH formula. Leaves scalp feeling hydrated, refreshed, and comfortable after every wash. Suitable for all hair types seeking clarifying care with a lightweight, fresh finish.',
        'price' => 1450.00,
        'image' => 'https://via.placeholder.com/220x260/ffffff/a82323?text=moev'
    ],
    2 => [
        'id' => 2,
        'title' => 'HETRAS SIMKONG ANTI-HAIR LOSS SHAMPOO - FLOWERSHOP 1,013ML',
        'desc' => 'Gently cleanses the scalp while helping to strengthen weakened hair and reduce excess sebum that can contribute to hair thinning. Enriched with nourishing ingredients to support a healthy scalp environment, leaving hair feeling refreshed and more resilient. Delivers a luxurious Bombshell fragrance with every wash, making daily hair care a sensorial experience.',
        'price' => 1800.00,
        'image' => 'https://via.placeholder.com/200x260/2c201d/ffffff?text=Hetras+96%25'
    ],
    3 => [
        'id' => 3,
        'title' => "D'ALBA PROFESSIONAL REPAIRING SCALP THERAPY SERUM SHAMPOO 275ML",
        'desc' => 'A repairing shampoo for scalp and hair, designed for gentle cleansing, hydration, and a smoother-looking finish through the lengths.',
        'price' => 850.00,
        'image' => 'https://via.placeholder.com/120x220/ffffff/d4af37?text=d\'Alba'
    ],
    4 => [
        'id' => 4,
        'title' => 'TSUBAKI PREMIUM VOLUME & REPAIR CONDITIONER 450ML',
        'desc' => '',
        'price' => 1350.00,
        'image' => 'https://via.placeholder.com/100x160/ffffff/d4af37?text=Tsubaki'
    ],
    5 => [
        'id' => 5,
        'title' => 'TSUBAKI PREMIUM EX DAMAGE CARE & REPAIR CONDITIONER (450ML)',
        'desc' => '',
        'price' => 1350.00,
        'image' => 'https://via.placeholder.com/100x160/111111/ffffff?text=Tsubaki+EX'
    ]
];

// Handle form post submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $qty = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;
    
    if (isset($products[$p_id])) {
        $_SESSION['cart'][$p_id] = ($_SESSION['cart'][$p_id] ?? 0) + $qty;
        $message = "Added " . $qty . " item(s) to cart successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bloom Beauty - Shop</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background-color: #ffffff; color: #333; }

        header { display: flex; justify-content: space-between; align-items: center; padding: 18px 50px; background: #fff; border-bottom: 1px solid #eee; }
        .logo { font-size: 1rem; font-weight: bold; color: #222; }
        nav { display: flex; align-items: center; gap: 25px; }
        nav a { text-decoration: none; color: #333; font-size: 0.85rem; }
        .login-btn { background-color: #000; color: #fff !important; padding: 6px 18px; border-radius: 3px; font-size: 0.8rem; }

        .hero { height: 280px; background: linear-gradient(rgba(0,0,0,0.2), rgba(0,0,0,0.2)), url('https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=1400&q=80') center/cover no-repeat; display: flex; justify-content: center; align-items: center; }
        .hero h1 { color: #fff; font-size: 2.2rem; letter-spacing: 3px; font-weight: 500; }

        .container { max-width: 950px; margin: 40px auto; padding: 0 20px; }
        .alert-success { background: #d4edda; color: #155724; padding: 12px; text-align: center; border-radius: 4px; margin-bottom: 30px; font-size: 0.85rem; }

        .product-row { display: flex; align-items: center; gap: 50px; margin-bottom: 60px; }
        .product-row.reverse { flex-direction: row-reverse; }
        .product-details { flex: 1; }
        .product-image { flex: 1; text-align: center; }
        .product-image img { max-width: 220px; height: auto; }

        .blue-bg-card { background-color: #f0f7f8; padding: 30px; text-align: center; border-radius: 4px; }
        .blue-bg-card img { max-width: 140px; }

        .product-title { font-size: 0.95rem; font-weight: bold; color: #222; text-transform: uppercase; margin-bottom: 12px; line-height: 1.4; }
        .product-desc { font-size: 0.78rem; color: #777; line-height: 1.5; margin-bottom: 12px; }
        .price { color: #d92525; font-weight: bold; font-size: 0.9rem; }
        .vat { font-size: 0.7rem; color: #999; margin-bottom: 15px; }

        .quantity-control { display: inline-flex; align-items: center; border: 1px solid #777; background-color: #dcdcdc; margin-bottom: 15px; }
        .qty-btn { background: none; border: none; width: 32px; height: 32px; font-size: 1rem; font-weight: bold; cursor: pointer; color: #333; }
        .qty-val { width: 30px; text-align: center; border: none; background: transparent; font-weight: bold; font-size: 0.85rem; outline: none; }
        .qty-label { font-size: 0.7rem; font-weight: bold; letter-spacing: 1px; color: #333; text-transform: uppercase; }

        .button-group { display: flex; gap: 10px; }
        .btn { padding: 9px 18px; font-size: 0.7rem; font-weight: bold; letter-spacing: 0.5px; cursor: pointer; border-radius: 2px; text-transform: uppercase; }
        .btn-buy-now { background-color: #e52828; color: #fff; border: 1px solid #e52828; }
        .btn-add-cart { background-color: #fff; color: #e52828; border: 1px solid #e52828; }

        .grid-section { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 40px; }

        footer { background-color: #f4c4b7; color: #333; padding: 40px 60px; margin-top: 80px; font-size: 0.78rem; line-height: 1.6; }
        .footer-container { max-width: 950px; margin: 0 auto; display: flex; justify-content: space-between; }
    </style>
</head>
<body>

    <header>
        <div class="logo">Bloom Beauty</div>
        <nav>
            <a href="#">Home</a>
            <a href="#">Services</a>
            <a href="#">Products</a>
            <a href="#" class="login-btn">Login</a>
        </nav>
    </header>

    <div class="hero">
        <h1>SHOP</h1>
    </div>

    <main class="container">

        <?php if ($message): ?>
            <div class="alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- Product 1: MOEV -->
        <?php $p1 = $products[1]; ?>
        <div class="product-row">
            <div class="product-details">
                <h2 class="product-title"><?php echo htmlspecialchars($p1['title']); ?></h2>
                <p class="product-desc"><?php echo htmlspecialchars($p1['desc']); ?></p>
                <div class="price">Rs <?php echo number_format($p1['price'], 2); ?></div>
                <div class="vat">VAT included</div>

                <form method="POST">
                    <input type="hidden" name="product_id" value="<?php echo $p1['id']; ?>">
                    <div class="quantity-control">
                        <button type="button" class="qty-btn" onclick="adjustQty(this, -1)">-</button>
                        <span class="qty-label">QUANTITY</span>
                        <input type="text" name="quantity" class="qty-val" value="1" readonly>
                        <button type="button" class="qty-btn" onclick="adjustQty(this, 1)">+</button>
                    </div>
                    <div class="button-group">
                        <button type="submit" name="action" value="buy" class="btn btn-buy-now">BUY IT NOW</button>
                        <button type="submit" name="action" value="cart" class="btn btn-add-cart">ADD TO CART</button>
                    </div>
                </form>
            </div>
            <div class="product-image">
                <img src="<?php echo $p1['image']; ?>" alt="MOEV Shampoo">
            </div>
        </div>

        <!-- Product 2: HETRAS -->
        <?php $p2 = $products[2]; ?>
        <div class="product-row reverse">
            <div class="product-details">
                <h2 class="product-title"><?php echo htmlspecialchars($p2['title']); ?></h2>
                <p class="product-desc"><?php echo htmlspecialchars($p2['desc']); ?></p>
                <div class="price">Rs <?php echo number_format($p2['price'], 2); ?></div>
                <div class="vat">VAT included</div>

                <form method="POST">
                    <input type="hidden" name="product_id" value="<?php echo $p2['id']; ?>">
                    <div class="quantity-control">
                        <button type="button" class="qty-btn" onclick="adjustQty(this, -1)">-</button>
                        <span class="qty-label">QUANTITY</span>
                        <input type="text" name="quantity" class="qty-val" value="1" readonly>
                        <button type="button" class="qty-btn" onclick="adjustQty(this, 1)">+</button>
                    </div>
                    <div class="button-group">
                        <button type="submit" name="action" value="buy" class="btn btn-buy-now">BUY IT NOW</button>
                        <button type="submit" name="action" value="cart" class="btn btn-add-cart">ADD TO CART</button>
                    </div>
                </form>
            </div>
            <div class="product-image">
                <img src="<?php echo $p2['image']; ?>" alt="Hetras Shampoo">
            </div>
        </div>

        <!-- Two Column Grid Section -->
        <div class="grid-section">

            <!-- Left: D'Alba -->
            <?php $p3 = $products[3]; ?>
            <div>
                <div class="blue-bg-card">
                    <img src="<?php echo $p3['image']; ?>" alt="d'Alba Shampoo">
                </div>
                <div style="margin-top: 15px;">
                    <h2 class="product-title"><?php echo htmlspecialchars($p3['title']); ?></h2>
                    <p class="product-desc"><?php echo htmlspecialchars($p3['desc']); ?></p>
                    <div class="price">Rs <?php echo number_format($p3['price'], 2); ?></div>
                    <div class="vat">VAT included</div>

                    <form method="POST">
                        <input type="hidden" name="product_id" value="<?php echo $p3['id']; ?>">
                        <div class="quantity-control">
                            <button type="button" class="qty-btn" onclick="adjustQty(this, -1)">-</button>
                            <span class="qty-label">QUANTITY</span>
                            <input type="text" name="quantity" class="qty-val" value="1" readonly>
                            <button type="button" class="qty-btn" onclick="adjustQty(this, 1)">+</button>
                        </div>
                        <div class="button-group">
                            <button type="submit" name="action" value="buy" class="btn btn-buy-now">BUY IT NOW</button>
                            <button type="submit" name="action" value="cart" class="btn btn-add-cart">ADD TO CART</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right: Tsubaki Items -->
            <div>
                <!-- Tsubaki Volume -->
                <?php $p4 = $products[4]; ?>
                <div style="margin-bottom: 40px; text-align: right;">
                    <img src="<?php echo $p4['image']; ?>" alt="Tsubaki Volume" style="margin-bottom: 10px;">
                    <h2 class="product-title"><?php echo htmlspecialchars($p4['title']); ?></h2>
                    <div class="price">Rs <?php echo number_format($p4['price'], 2); ?></div>
                    <div class="vat">VAT included</div>

                    <form method="POST">
                        <input type="hidden" name="product_id" value="<?php echo $p4['id']; ?>">
                        <div class="quantity-control">
                            <button type="button" class="qty-btn" onclick="adjustQty(this, -1)">-</button>
                            <span class="qty-label">QUANTITY</span>
                            <input type="text" name="quantity" class="qty-val" value="1" readonly>
                            <button type="button" class="qty-btn" onclick="adjustQty(this, 1)">+</button>
                        </div>
                        <div class="button-group" style="justify-content: flex-end;">
                            <button type="submit" name="action" value="buy" class="btn btn-buy-now">BUY IT NOW</button>
                            <button type="submit" name="action" value="cart" class="btn btn-add-cart">ADD TO CART</button>
                        </div>
                    </form>
                </div>

                <!-- Tsubaki EX Damage -->
                <?php $p5 = $products[5]; ?>
                <div style="text-align: right;">
                    <img src="<?php echo $p5['image']; ?>" alt="Tsubaki EX" style="margin-bottom: 10px;">
                    <h2 class="product-title"><?php echo htmlspecialchars($p5['title']); ?></h2>
                    <div class="price">Rs <?php echo number_format($p5['price'], 2); ?></div>
                    <div class="vat">VAT included</div>

                    <form method="POST">
                        <input type="hidden" name="product_id" value="<?php echo $p5['id']; ?>">
                        <div class="quantity-control">
                            <button type="button" class="qty-btn" onclick="adjustQty(this, -1)">-</button>
                            <span class="qty-label">QUANTITY</span>
                            <input type="text" name="quantity" class="qty-val" value="1" readonly>
                            <button type="button" class="qty-btn" onclick="adjustQty(this, 1)">+</button>
                        </div>
                        <div class="button-group" style="justify-content: flex-end;">
                            <button type="submit" name="action" value="buy" class="btn btn-buy-now">BUY IT NOW</button>
                            <button type="submit" name="action" value="cart" class="btn btn-add-cart">ADD TO CART</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>

    </main>

    <footer>
        <div class="footer-container">
            <div>
                <h4>Follow us</h4>
                <div style="margin-top:5px;">🌐 📷 👤</div>
            </div>
            <div>
                <p>Reduit, 80837</p>
                <p>+230 574 7314</p>
                <p>+230 575 2281</p>
                <p style="margin-top: 8px;"><strong>Opening & closing time:</strong></p>
                <p>Monday, Wednesday & Saturday: 09h00 - 17h00</p>
                <p>Tuesday, Thursday & Friday: 07h30 - 18h00</p>
            </div>
        </div>
    </footer>

    <script>
        function adjustQty(btn, change) {
            const form = btn.closest('form');
            const input = form.querySelector('.qty-val');
            let current = parseInt(input.value) || 1;
            current += change;
            if (current < 1) current = 1;
            input.value = current;
        }
    </script>
</body>
</html>