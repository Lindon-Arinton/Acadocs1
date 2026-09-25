<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TeacherSeeder extends Seeder
{
    public function run()
    {
        // employee_id is derived from each teacher's biometric AC-No (see UserSeeder).
        // grade_level/advisory/subjects reflect the MANAHIS Teacher Survey responses
        // (Google Form, collected 2026-09-21); blank in that survey means the teacher
        // has no advisory class this school year.
        //
        // Each subjects entry is [subject, grade, section] — one row per section the
        // teacher actually handles, matching how the survey itself was filled out
        // (e.g. "1. MATIPID - AP", "2. MATIYAGA - AP", ...) rather than a blended
        // count. teachers.grade_level stays a human-readable summary (the advisory's
        // own grade, or the union of grades taught when there's no advisory); the
        // per-row grade below is what actually drives MPS entry per PerformanceMps.
        //
        // Three responses were cut off in the survey's PDF export and are only
        // partially captured here — flagged inline; verify against the raw form
        // response before relying on them for MPS entry: Maureen Delos Reyes,
        // Evangeline Mendoza, Rechelle Ramos.
        $rows = [
            ['ac_no' => '5',  'name' => 'Judith Abitong',              'email' => 'judith.abitong@deped.gov.ph',            'grade_level' => '9',          'advisory' => 'MATATAG',                 'subjects' => [
                ['MAPEH', 'Grade 9', 'Matatag'], ['MAPEH', 'Grade 9', 'Magiting'], ['MAPEH', 'Grade 9', 'Masunurin'], ['MAPEH', 'Grade 9', 'Matapat'], ['MAPEH', 'Grade 9', 'Maunawain'],
            ]],
            ['ac_no' => '6',  'name' => 'Elizabeth Badillo',           'email' => 'elizabeth.amado@deped.gov.ph',           'grade_level' => '9',          'advisory' => 'MAGITING',                'subjects' => [
                ['SCIENCE', 'Grade 9', 'Magiting'], ['SCIENCE', 'Grade 9', 'Maunawain'], ['SCIENCE', 'Grade 9', 'Matatag'], ['SCIENCE', 'Grade 9', 'Masunurin'], ['V.E', 'Grade 9', 'Magiting'],
            ]],
            ['ac_no' => '12', 'name' => 'Mark Clinton Borja',          'email' => 'mark.borja@deped.gov.ph',                'grade_level' => '7 AND 8',    'advisory' => null,                      'subjects' => [
                ['MATH', 'Grade 7', 'Mapagkalinga'], ['MATH', 'Grade 7', 'Mapagmahal'], ['MATH', 'Grade 7', 'Mapagmalasakit'], ['MATH', 'Grade 7', 'Masayahin'],
                ['MAPEH', 'Grade 7', 'Masayahin'], ['HG', 'Grade 8', 'Matiyaga'], ['HG', 'Grade 8', 'Matipid'],
            ]],
            ['ac_no' => '14', 'name' => 'Judith De Villa',             'email' => 'judith.devilla001@deped.gov.ph',         'grade_level' => '7',          'advisory' => 'MAPAGMAHAL',              'subjects' => [
                ['ENGLISH', 'Grade 7', 'Mapagmahal'], ['ENGLISH', 'Grade 7', 'Mapagkalinga'], ['SPFL-CM', 'Grade 7', 'Mapagmahal'], ['SPFL-CM', 'Grade 9', 'Matapat'],
            ]],
            ['ac_no' => '18', 'name' => 'Porferia Dela Guerra',        'email' => 'porferia.delaguerra002@deped.gov.ph',    'grade_level' => '7',          'advisory' => 'MAPAGKALINGA',            'subjects' => [
                ['SCIENCE', 'Grade 7', 'Mapagkalinga'], ['SCIENCE', 'Grade 7', 'Mapagmahal'], ['SCIENCE', 'Grade 7', 'Mapagmalasakit'], ['SCIENCE', 'Grade 7', 'Masayahin'], ['HG', 'Grade 7', 'Mapagkalinga'],
            ]],
            ['ac_no' => '10', 'name' => 'Maureen Layca Delos Reyes',   'email' => 'maureenlayca.delosreyes@deped.gov.ph',   'grade_level' => '9',          'advisory' => 'MAUNAWAIN',               'subjects' => [
                ['TLE', 'Grade 9', 'Maunawain'], // survey response cut off after item 2 ("Masunur...") — incomplete
            ]],
            ['ac_no' => '33', 'name' => 'Remelyn Diaz',                'email' => 'remelyn.labajo@deped.gov.ph',            'grade_level' => '8',          'advisory' => 'MATIPID',                 'subjects' => [
                ['ENGLISH', 'Grade 8', 'Matipid'], ['ENGLISH', 'Grade 8', 'Maagap'], ['ENGLISH', 'Grade 8', 'Matiyaga'], ['SPFL', 'Grade 8', 'Matipid'], ['SPFL', 'Grade 10', 'Masigasig'],
            ]],
            ['ac_no' => '23', 'name' => 'Jimmilyn Fameronag',          'email' => 'jimmilyn.fameronag@deped.gov.ph',        'grade_level' => '9 AND 10',   'advisory' => null,                      'subjects' => [
                ['V.E', 'Grade 10', 'Masikap'], ['V.E', 'Grade 10', 'Masipag'], ['V.E', 'Grade 10', 'Masikhay'], ['V.E', 'Grade 10', 'Masigasig'], ['HG', 'Grade 9', 'Matapat'], ['HG', 'Grade 9', 'Matatag'],
            ]],
            ['ac_no' => '24', 'name' => 'Jerico Fameronag',            'email' => 'jerico.fameronag@deped.gov.ph',          'grade_level' => '9',          'advisory' => null,                      'subjects' => [
                ['AP', 'Grade 9', 'Masunurin'], ['AP', 'Grade 9', 'Maunawain'], ['AP', 'Grade 9', 'Matatag'], ['AP', 'Grade 9', 'Matapat'], ['AP', 'Grade 9', 'Magiting'],
            ]],
            ['ac_no' => '16', 'name' => 'Merian Gonzales',             'email' => 'merian.gonzales@deped.gov.ph',           'grade_level' => '7',          'advisory' => 'MAPAGMAHAL (Co-Adviser)', 'subjects' => [
                ['MAPEH', 'Grade 7', 'Mapagmahal'], ['MAPEH', 'Grade 7', 'Mapagkalinga'], ['MAPEH', 'Grade 7', 'Masayahin'], ['ENGLISH', 'Grade 7', 'Mapagkalinga'], ['ENGLISH', 'Grade 7', 'Masayahin'],
            ]],
            ['ac_no' => '45', 'name' => 'John Carlo Hernandez',        'email' => 'johncarlo.hernandez@deped.gov.ph',       'grade_level' => '8 AND 9',    'advisory' => null,                      'subjects' => [
                ['TLE', 'Grade 8', 'Matipid'], ['TLE', 'Grade 8', 'Maagap'], ['TLE', 'Grade 8', 'Malikhain'], ['TLE', 'Grade 8', 'Magalang'],
                ['TLE', 'Grade 9', 'Magiting'], ['RESEARCH', 'Grade 8', 'Matiyaga'],
            ]],
            ['ac_no' => '38', 'name' => 'Abegail Incilan',             'email' => 'abegail.incilan@deped.gov.ph',           'grade_level' => '7, 8 AND 9', 'advisory' => null,                      'subjects' => [
                ['TLE', 'Grade 7', 'Masayahin'], ['TLE', 'Grade 7', 'Mapagmahal'], ['TLE', 'Grade 7', 'Mapagmalasakit'],
                ['ESP', 'Grade 8', 'Matipid'], ['ESP', 'Grade 9', 'Maunawain'], ['ESP', 'Grade 9', 'Matapat'],
            ]],
            ['ac_no' => '36', 'name' => 'Agnes Javier',                'email' => 'agnes.javier004@deped.gov.ph',           'grade_level' => '8 AND 10',   'advisory' => null,                      'subjects' => [
                ['ENGLISH', 'Grade 10', 'Masigasig'], ['ENGLISH', 'Grade 10', 'Masikhay'], ['ENGLISH', 'Grade 10', 'Masipag'], ['ENGLISH', 'Grade 10', 'Masikap'], ['ENGLISH', 'Grade 8', 'Malikhain'],
            ]],
            ['ac_no' => '44', 'name' => 'Danica Roma Javier',          'email' => 'danica.javier@deped.gov.ph',             'grade_level' => '9 AND 10',   'advisory' => null,                      'subjects' => [
                ['MAPEH', 'Grade 10', 'Masikap'], ['MAPEH', 'Grade 10', 'Masipag'], ['MAPEH', 'Grade 10', 'Masikhay'], ['MAPEH', 'Grade 10', 'Masigasig'], ['RESEARCH', 'Grade 9', 'Maunawain'],
            ]],
            ['ac_no' => '11', 'name' => 'Nancy Maano',                 'email' => 'maano.nancy.noceda@gmail.com',           'grade_level' => '9 AND 10',   'advisory' => null,                      'subjects' => [
                ['ESP', 'Grade 9', 'Masunurin'], ['ESP', 'Grade 9', 'Matatag'],
                ['FILIPINO', 'Grade 10', 'Masikap'], ['FILIPINO', 'Grade 10', 'Masipag'], ['FILIPINO', 'Grade 10', 'Masikhay'], ['FILIPINO', 'Grade 10', 'Masigasig'],
            ]],
            ['ac_no' => '22', 'name' => 'Michael Macalindong',         'email' => 'michael.macalindong@deped.gov.ph',       'grade_level' => '9',          'advisory' => null,                      'subjects' => [
                ['MATH', 'Grade 9', 'Masunurin'], ['MATH', 'Grade 9', 'Maunawain'], ['MATH', 'Grade 9', 'Matatag'], ['MATH', 'Grade 9', 'Matapat'],
            ]],
            ['ac_no' => '15', 'name' => 'Rhea Magyaya',                'email' => 'rhea.magyaya@deped.gov.ph',              'grade_level' => '8',          'advisory' => 'MAGALANG',                'subjects' => [
                ['MATH', 'Grade 8', 'Magalang'], ['MATH', 'Grade 8', 'Maagap'], ['MATH', 'Grade 8', 'Matiyaga'], ['MATH', 'Grade 8', 'Malikhain'], ['MATH', 'Grade 8', 'Matipid'],
            ]],
            ['ac_no' => '8',  'name' => 'Beverly Iodine Mapa',         'email' => 'beverlyiodine.mapa001@deped.gov.ph',     'grade_level' => '7',          'advisory' => 'MASAYAHIN',               'subjects' => [
                ['AP', 'Grade 7', 'Mapagkalinga'], ['AP', 'Grade 7', 'Mapagmalasakit'], ['AP', 'Grade 7', 'Mapagmahal'], ['AP', 'Grade 7', 'Masayahin'], ['HG', 'Grade 7', 'Masayahin'],
            ]],
            ['ac_no' => '7',  'name' => 'Evangeline Mendoza',          'email' => 'evangeline.mendoza011@deped.gov.ph',     'grade_level' => '9',          'advisory' => 'MASUNURIN',               'subjects' => [
                // survey response cut off ("...MATA...") — grade-9 cohort assumed complete, including Magiting
                ['FILIPINO', 'Grade 9', 'Masunurin'], ['FILIPINO', 'Grade 9', 'Maunawain'], ['FILIPINO', 'Grade 9', 'Matatag'], ['FILIPINO', 'Grade 9', 'Matapat'], ['FILIPINO', 'Grade 9', 'Magiting'],
            ]],
            ['ac_no' => '17', 'name' => 'Ruelito Mendoza',             'email' => 'ruelito.mendoza002@deped.gov.ph',        'grade_level' => '8',          'advisory' => null,                      'subjects' => [
                ['SCIENCE', 'Grade 8', 'Matipid'], ['SCIENCE', 'Grade 8', 'Matiyaga'], ['SCIENCE', 'Grade 8', 'Magalang'], ['SCIENCE', 'Grade 8', 'Malikhain'], ['SCIENCE', 'Grade 8', 'Maagap'],
                ['HG', 'Grade 8', 'Magalang'], ['HG', 'Grade 8', 'Maagap'],
            ]],
            ['ac_no' => '29', 'name' => 'Robelyn Ordonia',             'email' => 'robelyn.ordonia@deped.gov.ph',           'grade_level' => '10',         'advisory' => 'MASIPAG',                 'subjects' => [
                ['AP', 'Grade 10', 'Masipag'], ['AP', 'Grade 10', 'Masikap'], ['AP', 'Grade 10', 'Masikhay'], ['AP', 'Grade 10', 'Masigasig'], ['HG', 'Grade 8', 'Malikhain'],
            ]],
            ['ac_no' => '1',  'name' => 'Angelique Piscal',            'email' => 'angelique.piscal@deped.gov.ph',          'grade_level' => '8',          'advisory' => 'MALIKHAIN',               'subjects' => [
                ['AP', 'Grade 8', 'Matipid'], ['AP', 'Grade 8', 'Matiyaga'], ['AP', 'Grade 8', 'Magalang'], ['AP', 'Grade 8', 'Malikhain'], ['AP', 'Grade 8', 'Maagap'],
            ]],
            ['ac_no' => '20', 'name' => 'Rechelle Ramos',              'email' => 'rechelle.ramos001@deped.gov.ph',         'grade_level' => '7 AND 8',    'advisory' => null,                      'subjects' => [
                ['TLE', 'Grade 7', 'Mapagkalinga'], ['TLE', 'Grade 8', 'Matiyaga'], // survey response cut off after 2 items — incomplete
            ]],
            ['ac_no' => '21', 'name' => 'Joanne Ricalde',              'email' => 'joanne.ricalde@deped.gov.ph',            'grade_level' => '7',          'advisory' => 'MAPAGMALASAKIT',          'subjects' => [
                ['V.E', 'Grade 7', 'Mapagmalasakit'], ['V.E', 'Grade 7', 'Mapagmahal'], ['V.E', 'Grade 7', 'Mapagkalinga'], ['V.E', 'Grade 7', 'Masayahin'],
            ]],
            ['ac_no' => '4',  'name' => 'Gil Robles',                  'email' => 'gil.robles001@deped.gov.ph',             'grade_level' => '7 AND 8',    'advisory' => null,                      'subjects' => [
                ['FILIPINO', 'Grade 7', 'Mapagkalinga'], ['FILIPINO', 'Grade 7', 'Mapagmalasakit'], ['FILIPINO', 'Grade 7', 'Mapagmahal'], ['FILIPINO', 'Grade 7', 'Masayahin'], ['ENGLISH', 'Grade 8', 'Magalang'],
            ]],
            ['ac_no' => '31', 'name' => 'Annie Rollon',                'email' => 'annie.delavega001@deped.gov.ph',         'grade_level' => '8',          'advisory' => 'MATIYAGA',                'subjects' => [
                ['FILIPINO', 'Grade 8', 'Matiyaga'], ['FILIPINO', 'Grade 8', 'Matipid'], ['FILIPINO', 'Grade 8', 'Magalang'], ['FILIPINO', 'Grade 8', 'Malikhain'], ['FILIPINO', 'Grade 8', 'Maagap'],
            ]],
            ['ac_no' => '9',  'name' => 'Edmarie Sagala',              'email' => 'edmarie.sagala001@deped.gov.ph',         'grade_level' => '10',         'advisory' => 'MASIKHAY',                'subjects' => [
                ['TLE', 'Grade 10', 'Masikhay'], ['TLE', 'Grade 10', 'Masikap'], ['TLE', 'Grade 10', 'Masipag'], ['TLE', 'Grade 10', 'Masigasig'],
            ]],
            ['ac_no' => '19', 'name' => 'Julius Salviejo',             'email' => 'julius.salviejo@deped.gov.ph',           'grade_level' => '8 AND 10',   'advisory' => null,                      'subjects' => [
                ['MAPEH', 'Grade 8', 'Matipid'], ['MAPEH', 'Grade 8', 'Matiyaga'], ['MAPEH', 'Grade 8', 'Magalang'], ['MAPEH', 'Grade 8', 'Maagap'], ['MAPEH', 'Grade 8', 'Malikhain'],
                ['RESEARCH', 'Grade 10', 'Masikhay'],
            ]],
            ['ac_no' => '27', 'name' => 'Shiela Mae Sanchez',          'email' => 'shielamae.sanchez002@deped.gov.ph',      'grade_level' => '8',          'advisory' => 'MAAGAP',                  'subjects' => [
                ['V.E', 'Grade 8', 'Maagap'], ['V.E', 'Grade 8', 'Magalang'], ['V.E', 'Grade 8', 'Matiyaga'], ['V.E', 'Grade 8', 'Malikhain'], ['MATH', 'Grade 9', 'Magiting'],
            ]],
            ['ac_no' => '13', 'name' => 'Geryl Sandoval',              'email' => 'geryl.aguila@deped.gov.ph',              'grade_level' => '9',          'advisory' => 'MATAPAT',                 'subjects' => [
                ['ENGLISH', 'Grade 9', 'Masunurin'], ['ENGLISH', 'Grade 9', 'Maunawain'], ['ENGLISH', 'Grade 9', 'Matatag'], ['ENGLISH', 'Grade 9', 'Matapat'], ['ENGLISH', 'Grade 9', 'Magiting'],
            ]],
            ['ac_no' => '2',  'name' => 'Jorge Taguibao',              'email' => 'jorge.taguibao@deped.gov.ph',            'grade_level' => '10',         'advisory' => null,                      'subjects' => [
                ['MATH', 'Grade 10', 'Masigasig'], ['MATH', 'Grade 10', 'Masikhay'], ['MATH', 'Grade 10', 'Masipag'], ['MATH', 'Grade 10', 'Masikap'],
                ['HG', 'Grade 10', 'Masigasig'], ['HG', 'Grade 10', 'Masikhay'], ['HG', 'Grade 10', 'Masipag'], ['HG', 'Grade 10', 'Masikap'],
            ]],
            ['ac_no' => '3',  'name' => 'Joy Valdez',                  'email' => 'joy.valdez003@deped.gov.ph',             'grade_level' => '9 AND 10',   'advisory' => 'MASIKAP',                 'subjects' => [
                ['SCIENCE', 'Grade 10', 'Masikap'], ['SCIENCE', 'Grade 10', 'Masipag'], ['SCIENCE', 'Grade 10', 'Masikhay'], ['SCIENCE', 'Grade 10', 'Masigasig'],
                ['SCIENCE', 'Grade 9', 'Matapat'], // survey's 5th section "Mayapat" read as "Matapat" (Grade 9) typo
            ]],
        ];

        foreach ($rows as $row) {
            $user = $this->db->table('users')->select('id')->where('email', $row['email'])->get()->getRow();

            $this->db->table('teachers')->insert([
                'employee_id'     => 'T-' . str_pad($row['ac_no'], 3, '0', STR_PAD_LEFT),
                'name'            => $row['name'],
                'email'           => $row['email'],
                'grade_level'     => $row['grade_level'],
                'advisory'        => $row['advisory'],
                'submission_rate' => 0.00,
                'user_id'         => $user->id ?? null,
            ]);

            $teacherId = $this->db->insertID();
            foreach ($row['subjects'] as [$subject, $grade, $section]) {
                $this->db->table('teacher_subjects')->insert([
                    'teacher_id'  => $teacherId,
                    'subject'     => $subject,
                    'grade_level' => $grade,
                    'section'     => $section,
                ]);
            }
        }
    }
}
