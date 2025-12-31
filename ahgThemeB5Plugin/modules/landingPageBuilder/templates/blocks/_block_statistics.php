<?php
/**
 * Statistics Block Template
 */
$stats = $data ?? [];
$title = $config['title'] ?? '';
$layout = $config['layout'] ?? 'horizontal';
$animate = $config['animate_numbers'] ?? true;
?>

<?php if (!empty($title)): ?>
  <h2 class="h4 text-center mb-4"><?php echo esc_entities($title) ?></h2>
<?php endif ?>

<div class="statistics-block <?php echo $layout === 'vertical' ? '' : 'd-flex justify-content-around flex-wrap' ?>">
  <?php foreach ($stats as $index => $stat): ?>
    <div class="stat-item text-center <?php echo $layout === 'vertical' ? 'mb-4' : 'px-4 py-3' ?>">
      <i class="bi <?php echo $stat['icon'] ?? 'bi-archive' ?> display-4 text-primary mb-2 d-block"></i>
      <div class="stat-counter h2 mb-1" data-count="<?php echo (int)($stat['count'] ?? 0) ?>">
        <?php echo number_format($stat['count'] ?? 0) ?>
      </div>
      <div class="stat-label text-muted"><?php echo esc_entities($stat['label'] ?? '') ?></div>
    </div>
  <?php endforeach ?>
</div>

<?php if ($animate): ?>
<script <?php echo __(sfConfig::get('csp_nonce', '')); ?>>
(function() {
    const counters = document.querySelectorAll('.stat-counter[data-count]');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const target = parseInt(counter.dataset.count, 10);
                const duration = 1500;
                const step = target / (duration / 16);
                let current = 0;
                
                const update = () => {
                    current += step;
                    if (current < target) {
                        counter.textContent = Math.floor(current).toLocaleString();
                        requestAnimationFrame(update);
                    } else {
                        counter.textContent = target.toLocaleString();
                    }
                };
                
                update();
                observer.unobserve(counter);
            }
        });
    }, { threshold: 0.5 });
    
    counters.forEach(counter => observer.observe(counter));
})();
</script>
<?php endif ?>
