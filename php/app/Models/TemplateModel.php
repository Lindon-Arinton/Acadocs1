<?php

namespace App\Models;

use CodeIgniter\Model;

class TemplateModel extends Model
{
    protected $table = 'templates';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'category_id', 'title', 'description', 'file_path', 'file_name', 'file_ext', 'file_size', 'uploaded_by', 'date_added',
    ];
    protected $afterFind = ['localizeFilePaths'];

    /**
     * file_path is stored absolute, so rows imported from another machine
     * (or another checkout folder) point somewhere that doesn't exist here.
     * The files themselves are committed under writable/uploads/templates/
     * {category_id}/, so fall back to the same file name in this install's
     * copy of that folder.
     */
    public static function localPath(array $template): string
    {
        $path = $template['file_path'];

        if (is_file($path) || ! isset($template['category_id'])) {
            return $path;
        }

        $local = WRITEPATH . 'uploads/templates/' . $template['category_id'] . DIRECTORY_SEPARATOR . basename(str_replace('\\', '/', $path));

        return is_file($local) ? $local : $path;
    }

    protected function localizeFilePaths(array $data): array
    {
        if (empty($data['data'])) {
            return $data;
        }

        if ($data['singleton']) {
            $data['data']['file_path'] = self::localPath($data['data']);
        } else {
            foreach ($data['data'] as &$row) {
                $row['file_path'] = self::localPath($row);
            }
            unset($row);
        }

        return $data;
    }

    public function distinctExtensions(): array
    {
        return array_column(
            $this->distinct()->select('file_ext')->orderBy('file_ext', 'ASC')->findAll(),
            'file_ext'
        );
    }
}
