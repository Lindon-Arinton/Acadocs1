<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskSubmissionFileModel extends Model
{
    protected $table = 'task_submission_files';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['task_submission_id', 'file_path', 'file_name'];

    public function forSubmission(int $submissionId): array
    {
        return $this->where('task_submission_id', $submissionId)->orderBy('id', 'ASC')->findAll();
    }

    public function forSubmissions(array $submissionIds): array
    {
        if ($submissionIds === []) {
            return [];
        }

        $grouped = [];
        foreach ($this->whereIn('task_submission_id', $submissionIds)->orderBy('id', 'ASC')->findAll() as $f) {
            $grouped[$f['task_submission_id']][] = $f;
        }

        return $grouped;
    }
    /**
     * Where the reviewer's marked-up copy of a submitted file lives (saved
     * from the in-app annotator as a PDF, next to the original upload).
     */
    public static function annotatedPath(array $file): string
    {
        return dirname($file['file_path']) . DIRECTORY_SEPARATOR . 'annotated' . DIRECTORY_SEPARATOR . $file['id'] . '.pdf';
    }

    public static function hasAnnotation(array $file): bool
    {
        return is_file(self::annotatedPath($file));
    }

    /** Deletes a file row's upload and its annotated copy, if any. */
    public function deleteWithFiles(array $file): void
    {
        foreach ([$file['file_path'], self::annotatedPath($file)] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        $this->delete($file['id']);
    }
}
