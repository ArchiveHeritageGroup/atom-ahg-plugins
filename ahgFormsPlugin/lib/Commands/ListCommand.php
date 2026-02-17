<?php

namespace AtomFramework\Console\Commands\Forms;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

class ListCommand extends BaseCommand
{
    protected string $name = 'forms:list';
    protected string $description = 'List form templates';
    protected string $detailedDescription = <<<'EOF'
    List all form templates in the system.

    Examples:
      php bin/atom forms:list                           # List all templates
      php bin/atom forms:list --type=information_object # Filter by type
      php bin/atom forms:list --assignments             # Show template assignments
      php bin/atom forms:list --fields=1                # Show fields for template ID 1
    EOF;

    protected function configure(): void
    {
        $this->addOption('type', null, 'Filter by form type (information_object, accession, actor)');
        $this->addOption('assignments', null, 'Show assignments');
        $this->addOption('fields', null, 'Show fields for specific template ID');
    }

    protected function handle(): int
    {
        $fieldsId = $this->option('fields');
        if ($fieldsId) {
            return $this->showTemplateFields((int) $fieldsId);
        }

        if ($this->hasOption('assignments')) {
            return $this->showAssignments();
        }

        return $this->showTemplates($this->option('type'));
    }

    private function showTemplates(?string $type): int
    {
        $this->info('=== Form Templates ===');

        $query = DB::table('ahg_form_template')
            ->orderBy('form_type')
            ->orderBy('name');

        if ($type) {
            $query->where('form_type', $type);
        }

        $templates = $query->get();

        if ($templates->isEmpty()) {
            $this->line('No templates found');

            return 0;
        }

        $this->info("Found {$templates->count()} templates:");
        $this->newline();

        $currentType = null;
        foreach ($templates as $t) {
            if ($currentType !== $t->form_type) {
                $currentType = $t->form_type;
                $this->bold('  ' . strtoupper($currentType) . ':');
            }

            $flags = [];
            if ($t->is_default) {
                $flags[] = 'DEFAULT';
            }
            if ($t->is_system) {
                $flags[] = 'SYSTEM';
            }
            if (!$t->is_active) {
                $flags[] = 'INACTIVE';
            }
            $flagStr = $flags ? ' [' . implode(', ', $flags) . ']' : '';

            $fieldCount = DB::table('ahg_form_field')
                ->where('template_id', $t->id)
                ->count();

            $this->line("  #{$t->id}: {$t->name}{$flagStr}");
            $this->line("      Fields: {$fieldCount} | Version: {$t->version}");
            if ($t->description) {
                $this->line("      {$t->description}");
            }
        }

        return 0;
    }

    private function showTemplateFields(int $templateId): int
    {
        $template = DB::table('ahg_form_template')
            ->where('id', $templateId)
            ->first();

        if (!$template) {
            $this->error("Template #{$templateId} not found");

            return 1;
        }

        $this->info("=== Fields for: {$template->name} ===");

        $fields = DB::table('ahg_form_field')
            ->where('template_id', $templateId)
            ->orderBy('sort_order')
            ->get();

        if ($fields->isEmpty()) {
            $this->line('No fields defined');

            return 0;
        }

        $currentSection = null;
        $currentTab = null;

        foreach ($fields as $f) {
            if ($f->tab_name !== $currentTab) {
                $currentTab = $f->tab_name;
                if ($currentTab) {
                    $this->newline();
                    $this->bold("  TAB: {$currentTab}");
                }
            }

            if ($f->section_name !== $currentSection) {
                $currentSection = $f->section_name;
                if ($currentSection) {
                    $this->line("  Section: {$currentSection}");
                }
            }

            $flags = [];
            if ($f->is_required) {
                $flags[] = 'required';
            }
            if ($f->is_repeatable) {
                $flags[] = 'repeatable';
            }
            if ($f->is_hidden) {
                $flags[] = 'hidden';
            }
            $flagStr = $flags ? ' (' . implode(', ', $flags) . ')' : '';

            $this->line("    {$f->sort_order}. [{$f->field_type}] {$f->field_name}: {$f->label}{$flagStr}");

            // Show mapping if exists
            $mapping = DB::table('ahg_form_field_mapping')
                ->where('field_id', $f->id)
                ->first();

            if ($mapping) {
                $this->line("       -> {$mapping->target_table}.{$mapping->target_column}");
            }
        }

        return 0;
    }

    private function showAssignments(): int
    {
        $this->info('=== Form Assignments ===');

        $assignments = DB::table('ahg_form_assignment as fa')
            ->join('ahg_form_template as ft', 'ft.id', '=', 'fa.template_id')
            ->leftJoin('repository_i18n as ri', function ($join) {
                $join->on('fa.repository_id', '=', 'ri.id')
                    ->where('ri.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('term_i18n as ti', function ($join) {
                $join->on('fa.level_of_description_id', '=', 'ti.id')
                    ->where('ti.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->select([
                'fa.*',
                'ft.name as template_name',
                'ft.form_type',
                'ri.authorized_form_of_name as repository_name',
                'ti.name as level_name',
            ])
            ->orderBy('fa.priority', 'desc')
            ->get();

        if ($assignments->isEmpty()) {
            $this->line('No assignments found');

            return 0;
        }

        foreach ($assignments as $a) {
            $status = $a->is_active ? 'ACTIVE' : 'INACTIVE';
            $this->info("#{$a->id}: {$a->template_name} [{$status}]");

            $conditions = [];
            if ($a->repository_name) {
                $conditions[] = "Repository: {$a->repository_name}";
            }
            if ($a->level_name) {
                $conditions[] = "Level: {$a->level_name}";
            }
            if ($a->collection_id) {
                $conditions[] = "Collection ID: {$a->collection_id}";
            }
            if (empty($conditions)) {
                $conditions[] = 'All (default)';
            }

            $this->line('  Applies to: ' . implode(', ', $conditions));
            $this->line("  Priority: {$a->priority} | Inherit: " . ($a->inherit_to_children ? 'Yes' : 'No'));
            $this->newline();
        }

        return 0;
    }
}
