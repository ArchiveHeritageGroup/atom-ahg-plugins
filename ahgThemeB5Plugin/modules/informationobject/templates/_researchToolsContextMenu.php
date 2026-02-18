<?php
/**
 * Research Tools Context Menu - shown on information object view for approved researchers.
 * Provides links to Source Assessment, Annotation Studio, and Add to Collection.
 *
 * @param QubitInformationObject $resource  The information object
 */
$showResearch = false;
$researcherId = null;
if ($sf_user->isAuthenticated()) {
    try {
        $userId = $sf_user->getAttribute('user_id');
        $researcher = \Illuminate\Database\Capsule\Manager::table('research_researcher')
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->first();
        if ($researcher) {
            $showResearch = true;
            $researcherId = $researcher->id;
        }
    } catch (Exception $e) {
        // research_researcher table may not exist
    }
}
?>
<?php if ($showResearch): ?>
<section class="sidebar-section">
  <h4><?php echo __('Research Tools'); ?></h4>
  <ul class="list-unstyled">
    <li>
      <a href="/index.php/research/source-assessment/<?php echo $resource->id; ?>">
        <i class="fas fa-clipboard-check me-1"></i><?php echo __('Source Assessment'); ?>
      </a>
    </li>
    <li>
      <a href="/index.php/research/annotation-studio/<?php echo $resource->id; ?>">
        <i class="fas fa-highlighter me-1"></i><?php echo __('Annotation Studio'); ?>
      </a>
    </li>
    <li>
      <a href="/index.php/research/trust-score/<?php echo $resource->id; ?>">
        <i class="fas fa-star-half-alt me-1"></i><?php echo __('Trust Score'); ?>
      </a>
    </li>
    <li>
      <a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">
        <i class="fas fa-graduation-cap me-1"></i><?php echo __('Research Dashboard'); ?>
      </a>
    </li>
  </ul>
</section>
<?php endif; ?>
