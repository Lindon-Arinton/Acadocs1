<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('users')->insertBatch([
            ['name' => 'Carmen Lopez', 'email' => 'secretary@school.edu', 'password' => '$2y$10$iVemOAuYsrWtDhAe3BmrJe2qYKXSUFiTNFDH851mKd31CMTTxmDYi', 'role' => 'secretary', 'ac_no' => null, 'position' => null],

            // Matabungkay NHS staff roster (real accounts, keyed by biometric AC-No)
            ['name' => 'Jorge Bautista', 'email' => 'jorge.bautista002@deped.gov.ph', 'password' => '$2y$10$mZiXiBtaycSs2DmiVudkq.NT6PlyGsTBSLisKR0G6tH6HQzsVL2Au', 'role' => 'admin', 'ac_no' => '25', 'position' => 'Principal III'],
            ['name' => 'Rhonnel Magyaya', 'email' => 'rhonnel.magyaya@deped.gov.ph', 'password' => '$2y$10$EoWuuEchqWy.jPRi50J/8e1ucyvKvJFL6xcq3YJAshBThctyEzOz.', 'role' => 'adas', 'ac_no' => '26', 'position' => 'ADAS II'],

            ['name' => 'Judith Abitong', 'email' => 'judith.abitong@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '5', 'position' => 'Teacher I'],
            ['name' => 'Elizabeth Badillo', 'email' => 'elizabeth.amado@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '6', 'position' => 'Teacher I'],
            ['name' => 'Mark Clinton Borja', 'email' => 'mark.borja@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '12', 'position' => 'Teacher I'],
            ['name' => 'Judith De Villa', 'email' => 'judith.devilla001@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '14', 'position' => 'Teacher III'],
            ['name' => 'Porferia Dela Guerra', 'email' => 'porferia.delaguerra002@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '18', 'position' => 'Teacher III'],
            ['name' => 'Maureen Layca Delos Reyes', 'email' => 'maureenlayca.delosreyes@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '10', 'position' => 'Teacher I'],
            ['name' => 'Remelyn Diaz', 'email' => 'remelyn.labajo@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '33', 'position' => 'Teacher III'],
            ['name' => 'Jimmilyn Fameronag', 'email' => 'jimmilyn.fameronag@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '23', 'position' => 'Teacher III'],
            ['name' => 'Jerico Fameronag', 'email' => 'jerico.fameronag@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '24', 'position' => 'Teacher III'],
            ['name' => 'Merian Gonzales', 'email' => 'merian.gonzales@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '16', 'position' => 'Teacher III'],
            ['name' => 'John Carlo Hernandez', 'email' => 'johncarlo.hernandez@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '45', 'position' => 'Teacher III'],
            ['name' => 'Abegail Incilan', 'email' => 'abegail.incilan@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '38', 'position' => 'Teacher I'],
            ['name' => 'Agnes Javier', 'email' => 'agnes.javier004@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '36', 'position' => 'Master Teacher II'],
            ['name' => 'Danica Roma Javier', 'email' => 'danica.javier@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '44', 'position' => 'Teacher I'],
            ['name' => 'Nancy Maano', 'email' => 'maano.nancy.noceda@gmail.com', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '11', 'position' => 'Teacher I'],
            ['name' => 'Michael Macalindong', 'email' => 'michael.macalindong@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '22', 'position' => 'Teacher III'],
            ['name' => 'Rhea Magyaya', 'email' => 'rhea.magyaya@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '15', 'position' => 'Master Teacher II'],
            ['name' => 'Beverly Iodine Mapa', 'email' => 'beverlyiodine.mapa001@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '8', 'position' => 'Teacher III'],
            ['name' => 'Evangeline Mendoza', 'email' => 'evangeline.mendoza011@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '7', 'position' => 'Teacher III'],
            ['name' => 'Ruelito Mendoza', 'email' => 'ruelito.mendoza002@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '17', 'position' => 'Teacher III'],
            ['name' => 'Robelyn Ordonia', 'email' => 'robelyn.ordonia@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '29', 'position' => 'Teacher III'],
            ['name' => 'Angelique Piscal', 'email' => 'angelique.piscal@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '1', 'position' => 'Teacher I'],
            ['name' => 'Rechelle Ramos', 'email' => 'rechelle.ramos001@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '20', 'position' => 'Teacher III'],
            ['name' => 'Joanne Ricalde', 'email' => 'joanne.ricalde@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '21', 'position' => 'Teacher III'],
            ['name' => 'Gil Robles', 'email' => 'gil.robles001@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '4', 'position' => 'Teacher II'],
            ['name' => 'Annie Rollon', 'email' => 'annie.delavega001@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '31', 'position' => 'Teacher II'],
            ['name' => 'Edmarie Sagala', 'email' => 'edmarie.sagala001@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '9', 'position' => 'Teacher III'],
            ['name' => 'Julius Salviejo', 'email' => 'julius.salviejo@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '19', 'position' => 'Teacher I'],
            ['name' => 'Shiela Mae Sanchez', 'email' => 'shielamae.sanchez002@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '27', 'position' => 'Teacher I'],
            ['name' => 'Geryl Sandoval', 'email' => 'geryl.aguila@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '13', 'position' => 'Teacher III'],
            ['name' => 'Jorge Taguibao', 'email' => 'jorge.taguibao@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '2', 'position' => 'Teacher III'],
            ['name' => 'Joy Valdez', 'email' => 'joy.valdez003@deped.gov.ph', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher', 'ac_no' => '3', 'position' => 'Master Teacher I'],
        ]);

        // Plain-text passwords for reference (never store these):
        // secretary@school.edu    -> sec123
        // Principal (admin)       -> admin123
        // ADAS staff              -> adas123
        // All teacher accounts    -> teacher123
    }
}
