<?php
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function header_html($title='Bloom Beauty') {
 echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'.h($title).'</title><link rel="stylesheet" href="assets/style.css"></head><body><header class="topbar"><a class="brand" href="index.php">Bloom Beauty</a><nav aria-label="Main navigation"><a href="index.php">Home</a><a href="services.php">Services</a><a href="products.php">Products</a><a class="nav-button" href="login.php">Login</a></nav></header>';
}
function footer_html() {
 echo '<footer><div><div>Follow us</div><p>● &nbsp;▣ &nbsp;▶ &nbsp;◎</p></div><div><p>Reduit, 80837</p><p>+230 674 7014<br>+230 670 2261</p><p>Opening &amp; closing time<br>Monday, Wednesday &amp; Saturday<br>07h30 – 17h30</p><p>Tuesday, Thursday &amp;<br>Friday 07h30 – 19h00</p></div></footer><script src="https://code.jquery.com/jquery-3.7.1.min.js"></script><script src="assets/app.js"></script></body></html>';
}
