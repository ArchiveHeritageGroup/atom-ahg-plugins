<?php
/**
 * Community Activity partial.
 *
 * Shows recent contributions and activity.
 */
?>

<section class="heritage-activity py-5">
    <div class="container-xxl">

        <!-- Section Header -->
        <div class="mb-4">
            <h2 class="h3 mb-1">Community</h2>
            <p class="text-muted mb-0">Recent contributions from our community</p>
        </div>

        <div class="row g-4">
            <!-- Activity Feed -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($activities as $activity): ?>
                        <li class="list-group-item py-3">
                            <div class="d-flex align-items-start">
                                <!-- Avatar -->
                                <div class="heritage-avatar flex-shrink-0 me-3">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                                         style="width: 40px; height: 40px;">
                                        <?php echo strtoupper(substr($activity['user'] ?? 'A', 0, 1)); ?>
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><?php echo esc_specialchars($activity['user']); ?></strong>
                                            <span class="text-muted">
                                                <?php echo $activity['action'] === 'create' ? 'added' : 'updated'; ?>
                                            </span>
                                            <?php if (!empty($activity['item_id'])): ?>
                                            <a href="/<?php echo $activity['item_id']; ?>" class="text-decoration-none">
                                                <?php echo esc_specialchars($activity['item_title']); ?>
                                            </a>
                                            <?php else: ?>
                                            <span><?php echo esc_specialchars($activity['item_title']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted ms-2 flex-shrink-0">
                                            <?php echo esc_specialchars($activity['time_ago']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Call to Action -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm bg-primary text-white h-100">
                    <div class="card-body d-flex flex-column justify-content-center text-center p-4">
                        <i class="fas fa-users-fill display-4 mb-3 opacity-75"></i>
                        <h3 class="h5 mb-3">Help us tell the story</h3>
                        <p class="mb-4 opacity-75">
                            Join our community of contributors and help preserve our heritage for future generations.
                        </p>
                        <a href="<?php echo url_for(['module' => 'user', 'action' => 'login']); ?>" class="btn btn-outline-light">
                            Start Contributing <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>
