<?php

/**
 * Install the Records in Context (RiC) descriptive standard on this instance.
 *
 * Idempotent. Creates the RiC term (code 'ric') in the information-object
 * template taxonomy (id 70) via the AtoM API - so the object row, nested set,
 * slug and i18n are all correct - and ensures the ric_record_meta table exists.
 * Once seeded, "Records in Context (RiC)" appears in the descriptive-standard
 * selector alongside ISAD(G)/RAD/DACS, and a RiC record renders through the
 * normal record template with the RiC capture panel.
 *
 *   php symfony ric:install
 *
 * Clear the cache + reload php-fpm afterwards.
 */
class ricInstallTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
        ]);

        $this->namespace = 'ric';
        $this->name = 'install';
        $this->briefDescription = 'Install the Records in Context (RiC) descriptive standard';
    }

    public function execute($arguments = [], $options = [])
    {
        new sfDatabaseManager($this->configuration);
        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }

        // QubitTerm::save() (slug + nested set + search) needs a Symfony context.
        // Use the task's own cli configuration so prod caches are untouched.
        if (!sfContext::hasInstance()) {
            sfContext::createInstance($this->configuration);
        }

        // Skip search indexing during the seed - the cli context has no search
        // config loaded (arOpenSearch would fatal on a null language list). The
        // term is picked up on the next reindex; the standard selector reads the
        // term table directly, so it appears immediately regardless.
        if (class_exists('QubitSearch')) {
            QubitSearch::disable();
        }

        $taxonomyId = QubitTaxonomy::INFORMATION_OBJECT_TEMPLATE_ID; // 70

        // 1. Ensure the ric_record_meta table (additive; safe to re-run).
        \Illuminate\Database\Capsule\Manager::statement(
            'CREATE TABLE IF NOT EXISTS ric_record_meta ('
            . ' id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'
            . ' object_id INT NOT NULL,'
            . " entity_type VARCHAR(50) NOT NULL DEFAULT 'Record',"
            . ' ric_data LONGTEXT NULL,'
            . ' created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,'
            . ' updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,'
            . ' UNIQUE KEY uq_ric_record_object (object_id),'
            . ' KEY idx_ric_record_entity (entity_type)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $this->logSection('ric', 'ric_record_meta table ensured');

        // 2. Seed the RiC standard term if not already present (idempotent).
        $existing = \Illuminate\Database\Capsule\Manager::table('term')
            ->where('taxonomy_id', $taxonomyId)
            ->where('code', 'ric')
            ->value('id');

        if ($existing) {
            $this->logSection('ric', "RiC standard term already present (id {$existing}) - nothing to do");

            return;
        }

        $term = QubitTerm::createTerm($taxonomyId, 'Records in Context (RiC)', 'en');
        $term->code = 'ric';
        $term->save();

        $this->logSection('ric', "Created RiC standard term (id {$term->id}, code 'ric')");
        $this->logSection('ric', 'Done. Clear cache + reload php-fpm; RiC now appears in the standard selector.');
    }
}
