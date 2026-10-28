<?php

namespace App\Models;

use CodeIgniter\Model;

/** Optional, nestable folders inside a Templates section. */
class TemplateFolderModel extends Model
{
    protected $table = 'template_folders';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['category_id', 'parent_id', 'name', 'created_by'];

    /**
     * Every folder keyed by id, each with its full 'path' ("Leave / 2026"),
     * ordered by section, then path.
     *
     * @return array<int,array<string,mixed>>
     */
    public function allWithPaths(): array
    {
        $folders = [];
        foreach ($this->orderBy('name', 'ASC')->findAll() as $f) {
            $folders[(int) $f['id']] = $f;
        }
        foreach ($folders as $id => $f) {
            $names = [];
            for ($cur = $f, $guard = 0; $cur && $guard < 50; $cur = $folders[(int) $cur['parent_id']] ?? null, $guard++) {
                array_unshift($names, $cur['name']);
            }
            $folders[$id]['path'] = implode(' / ', $names);
        }
        uasort($folders, static fn ($a, $b) => [(int) $a['category_id'], mb_strtolower($a['path'])] <=> [(int) $b['category_id'], mb_strtolower($b['path'])]);

        return $folders;
    }

    /** True when $folderId is $ancestorId or sits somewhere inside it. */
    public function isWithin(int $folderId, int $ancestorId): bool
    {
        for ($cur = $this->find($folderId), $guard = 0; $cur && $guard < 50; $cur = $cur['parent_id'] ? $this->find((int) $cur['parent_id']) : null, $guard++) {
            if ((int) $cur['id'] === $ancestorId) {
                return true;
            }
        }

        return false;
    }
}
