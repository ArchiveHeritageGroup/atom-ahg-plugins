<?php
/**
 * Stats Section partial.
 *
 * Displays collection statistics with animated counters.
 */

// Define icons for stat types
$statIcons = [
    'total_items' => 'bi-archive',
    'total_collections' => 'bi-collection',
    'total_digital_objects' => 'fas fa-image',
    'total_contributors' => 'bi-people',
];
?>

<section class="heritage-stats py-5">
    <div class="container-xxl">

        <div class="row g-4 justify-content-center">
            <?php foreach ($stats as $key => $stat): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="text-center p-4">
                    <!-- Icon -->
                    <i class="<?php echo $statIcons[$key] ?? 'bi-bar-chart'; ?> display-4 text-primary mb-3"></i>

                    <!-- Number -->
                    <div class="heritage-stat-number display-5 fw-bold mb-2"
                         data-count="<?php echo (int) $stat['value']; ?>">
                        0
                    </div>

                    <!-- Label -->
                    <div class="text-muted">
                        <?php echo esc_specialchars($stat['label']); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<script>
// Animate counters when they come into view
document.addEventListener('DOMContentLoaded', function() {
    const counters = document.querySelectorAll('.heritage-stat-number');

    const animateCounter = (el) => {
        const target = parseInt(el.dataset.count, 10);
        const duration = 2000;
        const step = target / (duration / 16);
        let current = 0;

        const update = () => {
            current += step;
            if (current < target) {
                el.textContent = Math.floor(current).toLocaleString();
                requestAnimationFrame(update);
            } else {
                el.textContent = target.toLocaleString();
            }
        };

        update();
    };

    // Intersection Observer for animation on scroll
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !entry.target.dataset.animated) {
                    entry.target.dataset.animated = 'true';
                    animateCounter(entry.target);
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(counter => observer.observe(counter));
    } else {
        counters.forEach(counter => animateCounter(counter));
    }
});
</script>
