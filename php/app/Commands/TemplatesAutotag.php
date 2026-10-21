<?php

namespace App\Commands;

use App\Libraries\TemplateClassifier;
use App\Models\TemplateModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Fills in auto tags and keywords for templates uploaded before
 * auto-tagging existed, and can test how well auto-classification works on
 * the templates already filed.
 *   php spark templates:autotag            only templates without tags
 *   php spark templates:autotag --all      redo tags for every template
 *   php spark templates:autotag --check    test classification (changes nothing)
 */
class TemplatesAutotag extends BaseCommand
{
    protected $group       = 'ACADOCS';
    protected $name        = 'templates:autotag';
    protected $description = 'Auto-tags existing templates, or tests the auto-classification (--check).';
    protected $usage       = 'templates:autotag [--all] [--check]';
    protected $options     = ['--all' => 'Re-tag every template, not only untagged ones.', '--check' => 'Leave-one-out accuracy test of section suggestions.'];

    public function run(array $params)
    {
        $model      = new TemplateModel();
        $classifier = new TemplateClassifier();
        $templates  = $model->select('templates.*, template_categories.name AS category_name')
            ->join('template_categories', 'template_categories.id = templates.category_id')
            ->orderBy('templates.id')->findAll();

        if (CLI::getOption('check') !== null) {
            $right = 0;
            foreach ($templates as $t) {
                $r  = $classifier->withoutTemplate((int) $t['id'])->analyze($t['file_name'], $t['title'], $t['file_path'], $t['file_ext']);
                $ok = (int) $r['category_id'] === (int) $t['category_id'];
                $right += $ok ? 1 : 0;
                if (! $ok) {
                    CLI::write(sprintf('✗ %s — filed in %s, suggested %s (%d%%)', $t['title'], $t['category_name'], $r['category_name'] ?? 'nothing', $r['confidence'] * 100), 'yellow');
                }
            }
            CLI::write(sprintf('%d of %d templates (%.0f%%) would be put in the section they are in now.', $right, count($templates), $templates ? $right / count($templates) * 100 : 0), 'green');

            return EXIT_SUCCESS;
        }

        $all  = CLI::getOption('all') !== null;
        $done = 0;
        // Two passes: keywords first, so the second pass learns from them.
        foreach ([1, 2] as $pass) {
            $classifier->withoutTemplate(null);
            foreach ($templates as $t) {
                if (! $all && $pass === 2 && ! empty($t['tags'])) {
                    continue;
                }
                $r    = $classifier->analyze($t['file_name'], $t['title'], $t['file_path'], $t['file_ext']);
                $data = ['keywords' => $r['keywords']];
                if ($pass === 2 && ($all || empty($t['tags']))) {
                    $data['tags'] = implode(', ', $r['tags']);
                    $done++;
                    CLI::write('• ' . $t['title'] . ': ' . $data['tags'], 'light_gray');
                }
                $model->update((int) $t['id'], $data);
            }
        }
        CLI::write('Tagged ' . $done . ' template(s).', 'green');

        return EXIT_SUCCESS;
    }
}
