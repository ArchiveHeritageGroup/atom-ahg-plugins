<?php
  $r = (float) ($rating ?? 0);
  $c = (int) ($count ?? 0);
  $fullStars = (int) floor($r);
  $halfStar = ($r - $fullStars) >= 0.5;
  $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
?>
<span class="text-warning" title="<?php echo number_format($r, 1); ?>/5">
  <?php for ($i = 0; $i < $fullStars; $i++): ?>
    <i class="fas fa-star"></i>
  <?php endfor; ?>
  <?php if ($halfStar): ?>
    <i class="fas fa-star-half-alt"></i>
  <?php endif; ?>
  <?php for ($i = 0; $i < $emptyStars; $i++): ?>
    <i class="far fa-star"></i>
  <?php endfor; ?>
</span>
<?php if ($c > 0): ?>
  <small class="text-muted">(<?php echo number_format($c); ?>)</small>
<?php endif; ?>
