<?php
// index.php - Bloom Beauty Homepage
$page_title = "Bloom Beauty - Home";
$current_page = "home";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo $page_title; ?></title>
  
  <!-- Google Fonts & Font Awesome Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <link rel="stylesheet" href="style.css?v=2.5" />
</head>
<body>

  <!-- Navigation Header -->
  <header class="site-header">
    <div class="logo">Bloom Beauty</div>
    <nav class="nav-links">
      <a href="index.php" class="<?php echo ($current_page == 'home') ? 'active' : ''; ?>">Home</a>
      <a href="services.php">Services</a>
      <a href="products.php">Products</a>
      <a href="login.php" class="btn-login">Login</a>
    </nav>
  </header>

  <!-- Hero Banner -->
  <section class="hero-banner">
    <img src="img/hero-banner.svg" alt="Bloom Beauty Floral Banner" class="hero-banner-img" />
  </section>

  <section class="hero-content">
    <h1>Discover Your Beauty At<br />Bloom Beauty</h1>
    <p>Book your favourite beauty services, shop our salon products, and enjoy a simple and convenient beauty experience.</p>
    <a href="services.php" class="btn-primary">Book Appointment</a>
  </section>

  <!-- Our Services Section -->
  <section class="services-section">
    <h2>Our Services</h2>
    <div class="services-grid">
      <?php
      // Service cards data array
      $services = [
          [
              "image" => "img/hair.svg",
              "alt" => "Hair Styling",
              "title" => "Hair Styling",
              "description" => "Professional haircuts, Styling and hair"
          ],
          [
              "image" => "img/nail.svg",
              "alt" => "Nail Care",
              "title" => "Nail Care",
              "description" => "Professional manicure and nail care for beautiful, well-groomed nails"
          ],
          [
              "image" => "img/facial.svg",
              "alt" => "Facial and Skincare",
              "title" => "Facial and Skincare",
              "description" => "Relax and refresh your skin with our professional facial and skincare treatments"
          ]
      ];

      foreach ($services as $service) {
          echo '<div class="service-card">';
          echo '<div class="service-img-wrapper">';
          echo '<img src="' . $service['image'] . '" alt="' . $service['alt'] . '" class="services-group" />';
          echo '</div>';
          echo '<h3>' . $service['title'] . '</h3>';
          echo '<p>' . $service['description'] . '</p>';
          echo '</div>';
      }
      ?>
    </div>
  </section>

  <!-- Footer Section -->
  <footer class="site-footer">
    <div class="footer-container">
      
      <!-- Footer Left -->
      <div class="footer-left">
        <h4>Follow us</h4>
        <div class="social-icons">
          <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
          <a href="#" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
          <a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
          <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
        </div>
      </div>

      <!-- Footer Right -->
      <div class="footer-right">
        <p class="address">Reduit, 80837</p>
        <p>+230 674 7014</p>
        <p>+230 670 2261</p>
        <p class="schedule-title">Opening & closing time:</p>
        <p>Monday, Wednesday & Saturday<br />07h30 – 17h30</p>
        <p class="schedule-spacing">Tuesday, Thursday &<br />Friday 07h30 – 19h00</p>
      </div>

    </div>
  </footer>

</body>
</html>