<?php
require_once 'config.php'; require_once 'partials.php';
// The service details come from the group's existing MySQL database.
$services = $pdo->query('SELECT service_id, service_name, description, duration_minutes, price, service_status FROM services ORDER BY service_id')->fetchAll();
$images = ['Hair Styling'=>'hair.png','Nail Care'=>'nails.png','Facial and Skincare'=>'facial.png'];
header_html('Bloom Beauty | Our Services');
?>
<main class="inner"><h1>Our Services</h1><p>Explore our salon treatments, prices and durations.</p>
<div class="filters" aria-label="Filter services"><button class="filter active" data-filter="all">All services</button><button class="filter" data-filter="available">Available</button><button class="filter" data-filter="unavailable">Unavailable</button></div>
<div class="cards service-grid">
<?php foreach($services as $service): $img=$images[$service['service_name']] ?? 'hero.png'; ?>
<article class="service-card" data-status="<?=h(strtolower($service['service_status']))?>"><img src="assets/<?=h($img)?>" alt="<?=h($service['service_name'])?>"><div class="service-body"><h2><?=h($service['service_name'])?></h2><p><?=h($service['description'])?></p><p><strong>Rs <?=number_format((float)$service['price'],2)?></strong> · <?=h($service['duration_minutes'])?> minutes</p><p class="status"><?=h($service['service_status'])?></p><?php if($service['service_status']==='Available'): ?><a class="button" href="booking.php?service=<?=urlencode((string)$service['service_id'])?>">Book Now</a><?php endif;?></div></article>
<?php endforeach; ?>
</div><p class="empty" hidden>No services match this filter.</p></main><?php footer_html(); ?>
