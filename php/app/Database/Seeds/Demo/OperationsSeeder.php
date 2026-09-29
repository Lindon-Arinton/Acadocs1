<?php

namespace App\Database\Seeds\Demo;

use App\Controllers\Teacher\PerformanceMps;

/**
 * Document Links, parent meetings, Property Management (room inventory per
 * section, entered by that section's adviser or teachers), and the legacy
 * `documents` table — retired as a workflow, but the Admin Dashboard's
 * Document Status / Compliance / Recent Submissions widgets and the Teacher
 * Dashboard's Document Feedback card still read it.
 */
class OperationsSeeder extends DemoSeeder
{
    /** [category, title, description, access_level] */
    private const LINKS = [
        ['Forms', 'School Forms (SF1–SF10)', 'Shared folder of the current DepEd school forms', 'Teachers'],
        ['Forms', 'Leave Application (CS Form 6)', 'Civil Service leave application form', 'All Users'],
        ['Forms', 'Travel Authority Request', 'Request form for official travel outside the division', 'All Users'],
        ['Forms', 'Learner Incident Report', 'Report form for learner incidents and interventions', 'Teachers'],
        ['Forms', 'Supply Requisition Slip', 'Request classroom and office supplies from the ADAS office', 'All Users'],
        ['Questionnaires', 'Learner Profile Survey', 'Survey for advisers on their advisory class profile', 'Teachers'],
        ['Questionnaires', 'Parent Satisfaction Survey', 'Feedback form shared with parents after conferences', 'All Users'],
        ['Questionnaires', 'INSET Evaluation Form', 'Evaluation of the latest in-service training sessions', 'Teachers'],
        ['Questionnaires', 'Canteen Service Feedback', 'Quick feedback form on the school canteen', 'All Users'],
        ['Templates', 'Daily Lesson Log Template', 'Editable DLL template for the current quarter', 'Teachers'],
        ['Templates', 'Class Record (E-Class Record)', 'Spreadsheet class record with automatic computation', 'Teachers'],
        ['Templates', 'Item Analysis Template', 'Spreadsheet for quarterly examination item analysis', 'Teachers'],
        ['Templates', 'Accomplishment Report Template', 'Monthly accomplishment report format', 'All Users'],
        ['Templates', 'Activity Proposal Template', 'Template for school activity proposals', 'All Users'],
        ['Guidelines', 'School Calendar of Activities', 'This school year\'s calendar of activities', 'All Users'],
        ['Guidelines', 'Classroom Assessment Guidelines', 'Summary of the classroom assessment policy', 'Teachers'],
        ['Guidelines', 'Grading System Reference', 'Quick reference for computing quarterly grades', 'Teachers'],
        ['Guidelines', 'Child Protection Policy', 'School child protection policy and reporting flow', 'All Users'],
        ['Guidelines', 'Property Accountability Guide', 'How to update and report classroom property', 'Admin'],
        ['Guidelines', 'Budget Utilization Report', 'MOOE utilization summary for the school year', 'Admin'],
    ];

    /** [title, grade level or null for school-wide] */
    private const MEETINGS = [
        ['General PTA Assembly', null],
        ['Grade 7 Orientation for Parents', 'Grade 7'],
        ['Quarter 1 Card Distribution', null],
        ['Grade 10 Career Guidance Orientation', 'Grade 10'],
        ['Grade 8 Homeroom PTA Meeting', 'Grade 8'],
        ['Grade 9 Homeroom PTA Meeting', 'Grade 9'],
        ['Quarter 2 Card Distribution', null],
        ['Brigada Eskwela Parents\' Briefing', null],
        ['Grade 10 Completion Rites Planning', 'Grade 10'],
        ['Quarter 3 Card Distribution', null],
    ];

    /** [item, min qty, max qty] */
    private const ROOM_ITEMS = [
        ['Student Armchairs', 35, 45], ['Teacher\'s Table', 1, 1], ['Teacher\'s Chair', 1, 1],
        ['Whiteboard', 1, 2], ['Electric Fan', 2, 4], ['LED Lights', 4, 8], ['Bulletin Board', 1, 2],
        ['Cabinet / Shelf', 1, 2], ['Smart TV', 0, 1], ['Wall Clock', 1, 1], ['Trash Bins', 2, 3],
        ['Learner\'s Books (set)', 30, 45],
    ];

    private const DOC_TYPES = ['DLL', 'Lesson Plan', 'Assessment', 'Report'];

    private const DOC_FEEDBACK = [
        'Returned' => ['Please revise the objectives section.', 'Missing signature of the department head.', 'Kindly follow the prescribed format.'],
        'Reviewed' => ['Checked and approved.', 'Well done. Keep it up.', 'Received, thank you.'],
    ];

    public function run()
    {
        $this->seedLinks();
        $this->seedMeetings();
        $this->seedRoomProperties();
        $this->seedDocuments();
    }

    private function seedLinks(): void
    {
        $adders = [$this->principal()['name'], $this->adas()['name']];
        $rows   = [];

        foreach (self::LINKS as $i => [$category, $title, $description, $access]) {
            $rows[] = [
                'category'     => $category,
                'title'        => $title,
                'description'  => $description,
                'url'          => 'https://drive.google.com/drive/folders/demo-' . substr(md5($title), 0, 16),
                'added_by'     => $this->pick($adders),
                'date_added'   => date('Y-m-d', $this->day(-150 + $i * 7)),
                'access_level' => $access,
            ];
        }

        $this->db->table('document_links')->insertBatch($rows);
    }

    private function seedMeetings(): void
    {
        $enrolment = [];
        foreach ($this->db->table('enrollment_by_level')->orderBy('school_year', 'DESC')->get()->getResultArray() as $row) {
            $enrolment[$row['grade_level']] ??= (int) $row['students'];
        }
        $schoolTotal = array_sum($enrolment) ?: 700;

        $rows = [];
        foreach (self::MEETINGS as $i => [$title, $grade]) {
            // Roughly monthly from ~9 months ago; the last two are upcoming.
            $dateTs   = $this->day(-270 + $i * 30 + mt_rand(-5, 5));
            $expected = $grade !== null ? ($enrolment[$grade] ?? 170) : $schoolTotal;
            $actual   = $dateTs < time() ? (int) round($expected * mt_rand(58, 92) / 100) : null;

            $rows[] = [
                'title'             => $title,
                'date'              => date('Y-m-d', $dateTs),
                'expected_parents'  => $expected,
                'actual_attendance' => $actual,
                'attendance_rate'   => $actual === null ? null : round($actual / $expected * 100, 2),
                'created_at'        => $this->at(min($dateTs - 14 * 86400, time())),
            ];
        }

        $this->db->table('parent_meetings')->insertBatch($rows);
    }

    private function seedRoomProperties(): void
    {
        $teachers = $this->teachers();
        $rows     = [];

        foreach ($this->sectionsByGrade() as $grade => $sections) {
            if (! in_array($grade, PerformanceMps::GRADE_LEVELS, true)) {
                continue;
            }

            foreach ($sections as $section) {
                // The section's adviser records the inventory; otherwise someone who teaches there.
                $recorder = null;
                $teaching = [];
                foreach ($teachers as $t) {
                    if ($t['advisory'] !== null && stripos($t['advisory'], $section) === 0) {
                        $recorder = $t;
                    }
                    foreach ($t['subjects'] as $s) {
                        if ($s['grade_level'] === $grade && $s['section'] === $section) {
                            $teaching[] = $t;
                        }
                    }
                }
                $recorder ??= $teaching !== [] ? $this->pick($teaching) : null;

                $items = self::ROOM_ITEMS;
                shuffle($items);
                foreach (array_slice($items, 0, mt_rand(7, count($items))) as [$item, $min, $max]) {
                    $quantity = mt_rand($min, $max);
                    if ($quantity === 0) {
                        continue;
                    }
                    $roll   = mt_rand(1, 100);
                    $rows[] = [
                        'section'          => $section,
                        'grade'            => $grade,
                        'item_name'        => $item,
                        'quantity'         => $quantity,
                        'condition_status' => $roll <= 20 ? 'Excellent' : ($roll <= 70 ? 'Good' : ($roll <= 92 ? 'Fair' : 'Poor')),
                        'uploaded_by'      => $recorder['name'] ?? null,
                        'created_at'       => $this->at($this->day(-mt_rand(10, 100)) + mt_rand(7, 17) * 3600),
                    ];
                }
            }
        }

        $this->db->table('room_properties')->insertBatch($rows);
    }

    /** ~8 historical documents per teacher over the past school year, with template files and principal feedback. */
    private function seedDocuments(): void
    {
        $templates = $this->templateFiles();
        $principal = $this->principal();
        $now       = time();

        foreach ($this->teachers() as $teacher) {
            if ($teacher['teacher_id'] === null || $teacher['subjects'] === []) {
                continue;
            }

            for ($n = mt_rand(5, 11); $n > 0; $n--) {
                $load        = $this->pick($teacher['subjects']);
                $template    = $this->pick($templates);
                $submittedTs = $this->day(-mt_rand(2, 330)) + mt_rand(7, 18) * 3600;
                $age         = $now - $submittedTs;
                $roll        = mt_rand(1, 100);
                $status      = $age > 21 * 86400
                    ? ($roll <= 55 ? 'Reviewed' : ($roll <= 75 ? 'Submitted' : ($roll <= 88 ? 'Returned' : 'Pending')))
                    : ($roll <= 20 ? 'Reviewed' : ($roll <= 55 ? 'Submitted' : ($roll <= 65 ? 'Returned' : 'Pending')));

                $path = $this->placeFile($template, WRITEPATH . 'uploads/documents', $submittedTs);

                $this->db->table('documents')->insert([
                    'teacher_id'     => $teacher['teacher_id'],
                    'type'           => $this->pick(self::DOC_TYPES),
                    'subject'        => $load['subject'],
                    'grade_level'    => $load['grade_level'],
                    'date_submitted' => $this->at($submittedTs),
                    // Stored relative to ROOTPATH (see Shared\DocumentFile).
                    'file_path'      => 'writable/uploads/documents/' . basename($path),
                    'status'         => $status,
                    'created_at'     => $this->at($submittedTs),
                ]);
                $documentId = (int) $this->db->insertID();

                if (isset(self::DOC_FEEDBACK[$status]) && ($status === 'Returned' || $this->chance(40))) {
                    $this->db->table('document_feedback')->insert([
                        'document_id' => $documentId,
                        'author'      => $principal['name'],
                        'comment'     => $this->pick(self::DOC_FEEDBACK[$status]),
                        'date'        => date('Y-m-d', min($submittedTs + mt_rand(1, 5) * 86400, $now)),
                    ]);
                }
            }
        }
    }
}
