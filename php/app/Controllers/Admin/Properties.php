<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Teacher\PerformanceMps;
use App\Models\RoomPropertyModel;
use App\Models\TeacherSubjectModel;

class Properties extends BaseController
{
    public function index()
    {
        $model = new RoomPropertyModel();

        if ($this->request->getMethod() === 'POST') {
            $isAjax = $this->request->isAJAX();

            if (! hasRole('teacher')) {
                return $isAjax ? $this->ajaxError('You are not authorized to do this.', 403) : redirect()->to('/property-management');
            }

            $action  = $this->request->getPost('action');
            $message = null;

            try {
                if ($action === 'add') {
                    $grade   = (string) $this->request->getPost('grade');
                    $section = (string) $this->request->getPost('section');
                    if (! in_array($section, $this->sectionsByGrade()[$grade] ?? [], true)) {
                        return $isAjax ? $this->ajaxError('Please pick a valid grade and section.') : redirect()->to('/property-management');
                    }

                    $model->insert([
                        'section'          => $section,
                        'grade'            => $grade,
                        'item_name'        => $this->request->getPost('item_name'),
                        'quantity'         => (int) ($this->request->getPost('quantity') ?: 1),
                        'condition_status' => $this->request->getPost('condition_status'),
                        'uploaded_by'      => currentUser()['name'] ?? null,
                    ]);
                    $message = 'Item added successfully.';
                } elseif ($action === 'delete') {
                    $model->delete((int) $this->request->getPost('id'));
                    $message = 'Item removed.';
                }
            } catch (\Throwable $e) {
                return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to('/property-management');
            }

            if ($isAjax) {
                return $message ? $this->ajaxSuccess($message) : $this->ajaxError('Unknown action.');
            }

            session()->setFlashdata('flash', ['type' => 'success', 'msg' => $message ?? '']);

            return redirect()->to('/property-management');
        }

        $grade     = $this->request->getGet('grade') ?? 'all';
        $condition = $this->request->getGet('condition') ?? 'all';
        $search    = trim($this->request->getGet('q') ?? '');
        $sort      = $this->request->getGet('sort') ?? 'grade_az';

        $builder = $model;
        if ($grade !== 'all') {
            $builder->where('grade', $grade);
        }
        if ($condition !== 'all') {
            $builder->where('condition_status', $condition);
        }
        if ($search !== '') {
            $builder->groupStart()
                ->like('section', $search)
                ->orLike('item_name', $search)
                ->groupEnd();
        }

        match ($sort) {
            'item_az'   => $builder->orderBy('item_name', 'ASC'),
            'condition' => $builder->orderBy('condition_status', 'ASC')->orderBy('item_name', 'ASC'),
            'newest'    => $builder->orderBy('created_at', 'DESC'),
            default     => $builder->orderBy('grade', 'ASC')->orderBy('section', 'ASC')->orderBy('item_name', 'ASC'),
        };

        $items = $builder->findAll();

        $grades     = array_column((new RoomPropertyModel())->distinct()->select('grade')->orderBy('grade')->findAll(), 'grade');
        $conditions = ['Excellent', 'Good', 'Fair', 'Poor'];

        $condStats = array_count_values(array_column($items, 'condition_status'));

        return view('pages/admin/properties', [
            'pageTitle'  => 'Property Management',
            'items'      => $items,
            'grade'      => $grade,
            'condition'  => $condition,
            'search'     => $search,
            'sort'       => $sort,
            'grades'     => $grades,
            'sectionsByGrade' => $this->sectionsByGrade(),
            'conditions' => $conditions,
            'condStats'  => $condStats,
            'flash'      => session()->getFlashdata('flash'),
        ]);
    }

    /**
     * Every section the school has, grouped by grade level — taken from the
     * teachers' subject loads (teacher_subjects), which is where sections are
     * defined. Drives the Add Item grade/section dropdowns.
     *
     * @return array<string,list<string>>
     */
    private function sectionsByGrade(): array
    {
        $map = array_fill_keys(PerformanceMps::GRADE_LEVELS, []);

        $rows = (new TeacherSubjectModel())->distinct()
            ->select('grade_level, section')
            ->where('section IS NOT NULL')
            ->where('section !=', '')
            ->orderBy('section')
            ->findAll();

        foreach ($rows as $row) {
            if (isset($map[$row['grade_level']])) {
                $map[$row['grade_level']][] = $row['section'];
            }
        }

        return $map;
    }
}
