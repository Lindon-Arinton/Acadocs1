<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TeacherSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('teachers')->insertBatch([
            ['employee_id' => 'T-001', 'name' => 'Maria Santos',       'email' => 'maria.santos@school.edu',       'grade_level' => 'Grade 7',    'submission_rate' => 95.00, 'user_id' => 2],
            ['employee_id' => 'T-002', 'name' => 'Juan dela Cruz',     'email' => 'juan.delacruz@school.edu',      'grade_level' => 'Grade 7',    'submission_rate' => 88.00, 'user_id' => 3],
            ['employee_id' => 'T-003', 'name' => 'Ana Reyes',          'email' => 'ana.reyes@school.edu',          'grade_level' => 'Grade 8',    'submission_rate' => 92.00, 'user_id' => null],
            ['employee_id' => 'T-004', 'name' => 'Pedro Garcia',       'email' => 'pedro.garcia@school.edu',       'grade_level' => 'Grade 8',    'submission_rate' => 100.00, 'user_id' => null],
            ['employee_id' => 'T-005', 'name' => 'Rosa Mendoza',       'email' => 'rosa.mendoza@school.edu',       'grade_level' => 'Grade 9',    'submission_rate' => 78.00, 'user_id' => null],
            ['employee_id' => 'T-006', 'name' => 'Carlos Bautista',    'email' => 'carlos.bautista@school.edu',    'grade_level' => 'Grade 9',    'submission_rate' => 85.00, 'user_id' => null],
            ['employee_id' => 'T-007', 'name' => 'Sofia Torres',       'email' => 'sofia.torres@school.edu',       'grade_level' => 'Grade 10',   'submission_rate' => 90.00, 'user_id' => null],
            ['employee_id' => 'T-008', 'name' => 'Miguel Ramos',       'email' => 'miguel.ramos@school.edu',       'grade_level' => 'Grade 10',   'submission_rate' => 97.00, 'user_id' => null],
            ['employee_id' => 'T-009', 'name' => 'Elena Cruz',         'email' => 'elena.cruz@school.edu',         'grade_level' => 'Grade 7',    'submission_rate' => 94.00, 'user_id' => null],
            ['employee_id' => 'T-010', 'name' => 'Roberto Lim',        'email' => 'roberto.lim@school.edu',        'grade_level' => 'Grade 7',    'submission_rate' => 89.00, 'user_id' => null],
            ['employee_id' => 'T-011', 'name' => 'Teresa Valdez',      'email' => 'teresa.valdez@school.edu',      'grade_level' => 'Grade 7',    'submission_rate' => 91.00, 'user_id' => null],
            ['employee_id' => 'T-012', 'name' => 'Ricardo Santos',     'email' => 'ricardo.santos@school.edu',     'grade_level' => 'Grade 7',    'submission_rate' => 96.00, 'user_id' => null],
            ['employee_id' => 'T-013', 'name' => 'Isabel Martinez',    'email' => 'isabel.martinez@school.edu',    'grade_level' => 'Grade 8',    'submission_rate' => 87.00, 'user_id' => null],
            ['employee_id' => 'T-014', 'name' => 'Fernando Diaz',      'email' => 'fernando.diaz@school.edu',      'grade_level' => 'Grade 8',    'submission_rate' => 93.00, 'user_id' => null],
            ['employee_id' => 'T-015', 'name' => 'Corazon Flores',     'email' => 'corazon.flores@school.edu',     'grade_level' => 'Grade 8',    'submission_rate' => 88.00, 'user_id' => null],
            ['employee_id' => 'T-016', 'name' => 'Antonio Rivera',     'email' => 'antonio.rivera@school.edu',     'grade_level' => 'Grade 8',    'submission_rate' => 99.00, 'user_id' => null],
            ['employee_id' => 'T-017', 'name' => 'Margarita Luna',     'email' => 'margarita.luna@school.edu',     'grade_level' => 'Grade 9',    'submission_rate' => 82.00, 'user_id' => null],
            ['employee_id' => 'T-018', 'name' => 'Benjamin Castro',    'email' => 'benjamin.castro@school.edu',    'grade_level' => 'Grade 9',    'submission_rate' => 86.00, 'user_id' => null],
            ['employee_id' => 'T-019', 'name' => 'Cristina Morales',   'email' => 'cristina.morales@school.edu',   'grade_level' => 'Grade 9',    'submission_rate' => 91.00, 'user_id' => null],
            ['employee_id' => 'T-020', 'name' => 'Francisco Gomez',    'email' => 'francisco.gomez@school.edu',    'grade_level' => 'Grade 9',    'submission_rate' => 95.00, 'user_id' => null],
            ['employee_id' => 'T-021', 'name' => 'Angelica Reyes',     'email' => 'angelica.reyes@school.edu',     'grade_level' => 'Grade 10',   'submission_rate' => 90.00, 'user_id' => null],
            ['employee_id' => 'T-022', 'name' => 'Gabriel Ramos',      'email' => 'gabriel.ramos@school.edu',      'grade_level' => 'Grade 10',   'submission_rate' => 88.00, 'user_id' => null],
            ['employee_id' => 'T-023', 'name' => 'Victoria Santos',    'email' => 'victoria.santos@school.edu',    'grade_level' => 'Grade 10',   'submission_rate' => 92.00, 'user_id' => null],
            ['employee_id' => 'T-024', 'name' => 'Domingo Cruz',       'email' => 'domingo.cruz@school.edu',       'grade_level' => 'Grade 10',   'submission_rate' => 97.00, 'user_id' => null],
            ['employee_id' => 'T-025', 'name' => 'Luz Fernandez',      'email' => 'luz.fernandez@school.edu',      'grade_level' => 'Grade 8',    'submission_rate' => 84.00, 'user_id' => null],
            ['employee_id' => 'T-026', 'name' => 'Emilio Torres',      'email' => 'emilio.torres@school.edu',      'grade_level' => 'Grade 8',    'submission_rate' => 89.00, 'user_id' => null],
            ['employee_id' => 'T-027', 'name' => 'Patricia Gutierrez', 'email' => 'patricia.gutierrez@school.edu', 'grade_level' => 'Grade 10',   'submission_rate' => 93.00, 'user_id' => null],
            ['employee_id' => 'T-028', 'name' => 'Alfredo Medina',     'email' => 'alfredo.medina@school.edu',     'grade_level' => 'Grade 10',   'submission_rate' => 91.00, 'user_id' => null],
            ['employee_id' => 'T-029', 'name' => 'Carmen Lopez',       'email' => 'carmen.lopez@school.edu',       'grade_level' => 'Grade 7',    'submission_rate' => 86.00, 'user_id' => null],
            ['employee_id' => 'T-030', 'name' => 'Jose Ramirez',       'email' => 'jose.ramirez@school.edu',       'grade_level' => 'Grade 7',    'submission_rate' => 94.00, 'user_id' => null],
            ['employee_id' => 'T-031', 'name' => 'Rosario Ortiz',      'email' => 'rosario.ortiz@school.edu',      'grade_level' => 'Grade 9',    'submission_rate' => 88.00, 'user_id' => null],
            ['employee_id' => 'T-032', 'name' => 'Luis Chavez',        'email' => 'luis.chavez@school.edu',        'grade_level' => 'Grade 9',    'submission_rate' => 90.00, 'user_id' => null],
            ['employee_id' => 'T-033', 'name' => 'Linda Aquino',       'email' => 'linda.aquino@school.edu',       'grade_level' => 'All Levels', 'submission_rate' => 85.00, 'user_id' => null],
        ]);

        $this->db->table('teacher_subjects')->insertBatch([
            ['teacher_id' => 1,  'subject' => 'Mathematics'],
            ['teacher_id' => 2,  'subject' => 'Science'],
            ['teacher_id' => 3,  'subject' => 'English'],
            ['teacher_id' => 4,  'subject' => 'Filipino'],
            ['teacher_id' => 5,  'subject' => 'Mathematics'],
            ['teacher_id' => 6,  'subject' => 'Science'],
            ['teacher_id' => 7,  'subject' => 'English'],
            ['teacher_id' => 8,  'subject' => 'Filipino'],
            ['teacher_id' => 9,  'subject' => 'Araling Panlipunan'],
            ['teacher_id' => 10, 'subject' => 'MAPEH'],
            ['teacher_id' => 11, 'subject' => 'TLE'],
            ['teacher_id' => 12, 'subject' => 'ESP'],
            ['teacher_id' => 13, 'subject' => 'Araling Panlipunan'],
            ['teacher_id' => 14, 'subject' => 'MAPEH'],
            ['teacher_id' => 15, 'subject' => 'TLE'],
            ['teacher_id' => 16, 'subject' => 'ESP'],
            ['teacher_id' => 17, 'subject' => 'Araling Panlipunan'],
            ['teacher_id' => 18, 'subject' => 'MAPEH'],
            ['teacher_id' => 19, 'subject' => 'TLE'],
            ['teacher_id' => 20, 'subject' => 'ESP'],
            ['teacher_id' => 21, 'subject' => 'Araling Panlipunan'],
            ['teacher_id' => 22, 'subject' => 'MAPEH'],
            ['teacher_id' => 23, 'subject' => 'TLE'],
            ['teacher_id' => 24, 'subject' => 'ESP'],
            ['teacher_id' => 25, 'subject' => 'Mathematics'],
            ['teacher_id' => 26, 'subject' => 'Science'],
            ['teacher_id' => 27, 'subject' => 'Mathematics'],
            ['teacher_id' => 28, 'subject' => 'Science'],
            ['teacher_id' => 29, 'subject' => 'English'],
            ['teacher_id' => 30, 'subject' => 'Filipino'],
            ['teacher_id' => 31, 'subject' => 'English'],
            ['teacher_id' => 32, 'subject' => 'Filipino'],
            ['teacher_id' => 33, 'subject' => 'Computer'],
        ]);
    }
}
