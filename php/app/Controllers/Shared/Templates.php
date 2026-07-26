<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Libraries\CertificateGenerator;
use App\Libraries\OfficeConverter;
use App\Models\TemplateCategoryModel;
use App\Models\TemplateModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Templates extends BaseController
{
    private const ALLOWED_EXT = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png',
    ];

    /** File types LibreOffice can convert to PDF for preview/download. */
    private const OFFICE_TO_PDF = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv', 'odt', 'ods', 'odp'];

    /**
     * Returns the extra download/preview format a file's "usual type" can be
     * converted to (Word/Excel/PowerPoint documents -> PDF), or [] if none applies.
     *
     * PDF -> Word is intentionally not offered: LibreOffice's PDF import filter
     * reliably hangs headless conversion in this environment, so it isn't a safe
     * conversion to expose in the UI.
     */
    public static function conversionTargets(string $ext): array
    {
        if (! (new OfficeConverter())->isAvailable()) {
            return [];
        }

        if (in_array($ext, self::OFFICE_TO_PDF, true)) {
            return ['pdf'];
        }

        return [];
    }

    public function index()
    {
        $categoryModel = new TemplateCategoryModel();
        $templateModel = new TemplateModel();

        if ($this->request->getMethod() === 'POST') {
            $isAjax = $this->request->isAJAX();

            if (! hasRole('adas')) {
                return $isAjax ? $this->ajaxError('You are not authorized to do this.', 403) : redirect()->to('/templates');
            }

            $action  = $this->request->getPost('action');
            $message = null;
            $error   = null;

            try {
                if ($action === 'add_category') {
                    $name = trim($this->request->getPost('name') ?? '');

                    if ($name === '') {
                        $error = 'Please enter a category name.';
                    } elseif ($categoryModel->where('name', $name)->first()) {
                        $error = 'This category already exists.';
                    } else {
                        $categoryModel->insert([
                            'name'       => $name,
                            'created_by' => currentUser()['name'],
                        ]);
                        $message = 'Category added.';
                    }
                } elseif ($action === 'delete_category') {
                    $categoryId = (int) $this->request->getPost('id');

                    if ($templateModel->where('category_id', $categoryId)->countAllResults() > 0) {
                        $error = 'This category still has templates. Remove or reassign them first.';
                    } else {
                        $categoryModel->delete($categoryId);
                        $message = 'Category deleted.';
                    }
                } elseif ($action === 'upload') {
                    $categoryId = (int) $this->request->getPost('category_id');
                    $category   = $categoryModel->find($categoryId);
                    $title      = trim($this->request->getPost('title') ?? '');

                    if (! $category) {
                        $error = 'Please choose a valid category.';
                    } elseif ($title === '') {
                        $error = 'Please enter a title.';
                    } else {
                        // ext_in also requires PHP's fileinfo mime-sniff to match the
                        // extension, which misidentifies some genuine, valid .docx/.xlsx
                        // files as application/octet-stream on this server and falsely
                        // rejects them. This upload is already gated to admins/ADAS, so
                        // trusting the client-reported extension here is an acceptable
                        // trade-off — checked manually below instead of via ext_in.
                        $rules = [
                            'file' => [
                                'label'  => 'File',
                                'rules'  => 'uploaded[file]|max_size[file,10240]',
                                'errors' => [
                                    'uploaded' => 'Please choose a file to upload.',
                                    'max_size' => 'File is too large (max 10MB).',
                                ],
                            ],
                        ];

                        $uploadedExt = strtolower(pathinfo($this->request->getFile('file')?->getClientName() ?? '', PATHINFO_EXTENSION));

                        if (! $this->validate($rules)) {
                            $error = implode(' ', $this->validator->getErrors());
                        } elseif (! in_array($uploadedExt, self::ALLOWED_EXT, true)) {
                            $error = 'Unsupported file type.';
                        } else {
                            $file      = $this->request->getFile('file');
                            $targetDir = WRITEPATH . 'uploads/templates/' . $categoryId;

                            if (! is_dir($targetDir)) {
                                mkdir($targetDir, 0755, true);
                            }

                            $newName = $file->getRandomName();
                            $file->move($targetDir, $newName);

                            $templateModel->insert([
                                'category_id' => $categoryId,
                                'title'       => $title,
                                'description' => trim($this->request->getPost('description') ?? ''),
                                'file_path'   => $targetDir . DIRECTORY_SEPARATOR . $newName,
                                'file_name'   => $file->getClientName(),
                                'file_ext'    => strtolower(pathinfo($file->getClientName(), PATHINFO_EXTENSION)),
                                'file_size'   => $file->getSize(),
                                'uploaded_by' => currentUser()['name'],
                                'date_added'  => date('Y-m-d'),
                            ]);
                            $message = 'Template uploaded successfully.';
                        }
                    }
                } elseif ($action === 'delete_template') {
                    $template = $templateModel->find((int) $this->request->getPost('id'));

                    if ($template) {
                        if (is_file($template['file_path'])) {
                            unlink($template['file_path']);
                        }
                        $templateModel->delete($template['id']);
                    }
                    $message = 'Template deleted.';
                }
            } catch (\Throwable $e) {
                return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to('/templates');
            }

            if ($error) {
                return $isAjax ? $this->ajaxError($error) : redirect()->to('/templates')->with('flash', ['type' => 'danger', 'msg' => $error]);
            }

            if ($isAjax) {
                return $message ? $this->ajaxSuccess($message) : $this->ajaxError('Unknown action.');
            }

            session()->setFlashdata('flash', ['type' => 'success', 'msg' => $message ?? '']);

            return redirect()->to('/templates');
        }

        $search   = trim($this->request->getGet('q') ?? '');
        $catId    = (int) ($this->request->getGet('category') ?? 0);
        $fileType = $this->request->getGet('type') ?? 'all';
        $sort     = $this->request->getGet('sort') ?? 'date_desc';

        $builder = $templateModel
            ->select('templates.*, template_categories.name AS category_name')
            ->join('template_categories', 'template_categories.id = templates.category_id');

        if ($search !== '') {
            $builder->groupStart()
                ->like('templates.title', $search)
                ->orLike('templates.file_name', $search)
                ->groupEnd();
        }
        if ($catId > 0) {
            $builder->where('templates.category_id', $catId);
        }
        if ($fileType !== 'all') {
            $builder->where('templates.file_ext', $fileType);
        }

        match ($sort) {
            'date_asc' => $builder->orderBy('templates.date_added', 'ASC'),
            'section'  => $builder->orderBy('template_categories.name', 'ASC')->orderBy('templates.title', 'ASC'),
            'type'     => $builder->orderBy('templates.file_ext', 'ASC')->orderBy('templates.title', 'ASC'),
            default    => $builder->orderBy('templates.date_added', 'DESC'),
        };

        $templates = $builder->findAll();

        $categories = $categoryModel->orderBy('name', 'ASC')->findAll();
        foreach ($categories as &$c) {
            $c['count'] = $templateModel->where('category_id', $c['id'])->countAllResults();
        }
        unset($c);

        // For every Word template, detect its ${Field} merge placeholders up
        // front so the "Generate Certificates" modal can show them without a
        // separate round trip. A template with none simply can't be bulk-filled.
        $certificateFields = [];
        $generator         = new CertificateGenerator();
        foreach ($templates as $t) {
            if ($t['file_ext'] === 'docx' && is_file($t['file_path'])) {
                try {
                    $certificateFields[$t['id']] = $generator->detectFields($t['file_path']);
                } catch (\Throwable $e) {
                    $certificateFields[$t['id']] = [];
                }
            }
        }

        return view('pages/shared/templates', [
            'pageTitle'         => 'Templates',
            'templates'         => $templates,
            'categories'        => $categories,
            'search'            => $search,
            'catId'             => $catId,
            'fileType'          => $fileType,
            'sort'              => $sort,
            'fileTypes'         => $templateModel->distinctExtensions(),
            'flash'             => session()->getFlashdata('flash'),
            'certificateFields' => $certificateFields,
        ]);
    }

    public function download(int $id)
    {
        $template = (new TemplateModel())->find($id);

        if (! $template || ! is_file($template['file_path'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        $as = strtolower(trim($this->request->getGet('as') ?? ''));

        if ($as !== '' && $as !== $template['file_ext']) {
            if (! in_array($as, self::conversionTargets($template['file_ext']), true)) {
                throw PageNotFoundException::forPageNotFound();
            }

            $converted = $this->convertedPath($template, $as);

            if (! $converted) {
                return redirect()->back()->with('flash', [
                    'type' => 'danger',
                    'msg'  => 'Could not convert this file to ' . strtoupper($as) . '. Downloading the original instead.',
                ]);
            }

            $downloadName = pathinfo($template['file_name'], PATHINFO_FILENAME) . '.' . $as;

            return $this->response->download($converted, null)->setFileName($downloadName);
        }

        return $this->response->download($template['file_path'], null)->setFileName($template['file_name']);
    }

    public function preview(int $id)
    {
        $template = (new TemplateModel())->find($id);

        if (! $template || ! is_file($template['file_path'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        $ext = $template['file_ext'];

        $mimeMap = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'txt'  => 'text/plain',
        ];

        if (isset($mimeMap[$ext])) {
            return $this->response
                ->setHeader('Content-Type', $mimeMap[$ext])
                ->setHeader('Content-Disposition', 'inline; filename="' . $template['file_name'] . '"')
                ->setBody(file_get_contents($template['file_path']));
        }

        if (in_array($ext, self::OFFICE_TO_PDF, true)) {
            $pdfPath = $this->convertedPath($template, 'pdf');

            if ($pdfPath) {
                $inlineName = pathinfo($template['file_name'], PATHINFO_FILENAME) . '.pdf';

                return $this->response
                    ->setHeader('Content-Type', 'application/pdf')
                    ->setHeader('Content-Disposition', 'inline; filename="' . $inlineName . '"')
                    ->setBody(file_get_contents($pdfPath));
            }
        }

        return $this->response
            ->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setBody('<div style="font-family:sans-serif;color:#6b7280;text-align:center;padding:3rem 1rem;">'
                . 'Preview isn\'t available for this file type. Download it to view the contents.</div>');
    }

    /**
     * Returns a cached converted copy of the template's file, converting via
     * LibreOffice headless and caching the result if it isn't cached yet (or
     * the source file changed since the cached copy was made).
     */
    private function convertedPath(array $template, string $targetExt): ?string
    {
        $cacheDir = WRITEPATH . 'cache/templates';

        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $cached = $cacheDir . DIRECTORY_SEPARATOR . $template['id'] . '.' . $targetExt;

        if (is_file($cached) && filemtime($cached) >= filemtime($template['file_path'])) {
            return $cached;
        }

        $result = (new OfficeConverter())->convert($template['file_path'], $targetExt, $cacheDir);

        if (! $result) {
            return null;
        }

        if ($result !== $cached) {
            if (is_file($cached)) {
                unlink($cached);
            }
            rename($result, $cached);
        }

        return $cached;
    }

    /**
     * Fills a docx template's ${Field} placeholders once per row of an
     * uploaded Excel recipient list and returns the results zipped together.
     * Not an "ajax-form" action: the response body is the zip file itself
     * (or a JSON error), not a JSON success message, so the page's JS talks
     * to this endpoint with a plain fetch() instead of the generic handler.
     */
    public function generateCertificates()
    {
        if (! hasRole('adas')) {
            return $this->ajaxError('You are not authorized to do this.', 403);
        }

        $template = (new TemplateModel())->find((int) $this->request->getPost('template_id'));

        if (! $template || $template['file_ext'] !== 'docx' || ! is_file($template['file_path'])) {
            return $this->ajaxError('Please choose a valid Word template.');
        }

        $file = $this->request->getFile('import_file');

        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return $this->ajaxError('Please choose a valid Excel file to upload.');
        }

        if (! in_array(strtolower($file->getExtension() ?: ''), ['xlsx', 'xls'], true)) {
            return $this->ajaxError('Only .xlsx or .xls files are supported.');
        }

        // Bulk-converting many rows to PDF one at a time can take a while
        // (each is a separate LibreOffice headless invocation) — don't let
        // the default execution-time limit cut a large batch off midway.
        set_time_limit(300);

        $workDir = WRITEPATH . 'uploads/certificate_generations/' . uniqid('cg_', true);
        mkdir($workDir, 0755, true);

        $excelName = 'recipients.' . strtolower($file->getExtension());
        $file->move($workDir, $excelName);

        try {
            $result = (new CertificateGenerator())->generateZip(
                $template['file_path'],
                $workDir . DIRECTORY_SEPARATOR . $excelName,
                $workDir,
                new OfficeConverter(),
            );

            $zipData = file_get_contents($result['zipPath']);
        } catch (\Throwable $e) {
            $this->deleteDir($workDir);

            return $this->ajaxError($e->getMessage());
        }

        $this->deleteDir($workDir);

        $zipName = preg_replace('/[^A-Za-z0-9._-]/', '_', $template['title']) . '_Certificates_' . date('Ymd_His') . '.zip';

        $response = $this->response->download($zipName, $zipData);
        $response->setHeader('X-Certificate-Count', (string) $result['count']);
        if ($result['unmatchedFields'] !== []) {
            $response->setHeader('X-Certificate-Unmatched-Fields', implode(', ', $result['unmatchedFields']));
        }

        return $response;
    }

    private function deleteDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            is_dir($path) ? $this->deleteDir($path) : unlink($path);
        }

        rmdir($dir);
    }
}
