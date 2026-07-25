<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TeacherSeeder extends Seeder
{
    public function run()
    {
        // employee_id is derived from each teacher's biometric AC-No (see UserSeeder).
        $rows = [
            ['ac_no' => '5',  'name' => 'Judith Abitong',              'email' => 'judith.abitong@deped.gov.ph'],
            ['ac_no' => '6',  'name' => 'Elizabeth Badillo',           'email' => 'elizabeth.amado@deped.gov.ph'],
            ['ac_no' => '12', 'name' => 'Mark Clinton Borja',          'email' => 'mark.borja@deped.gov.ph'],
            ['ac_no' => '14', 'name' => 'Judith De Villa',             'email' => 'judith.devilla001@deped.gov.ph'],
            ['ac_no' => '18', 'name' => 'Porferia Dela Guerra',        'email' => 'porferia.delaguerra002@deped.gov.ph'],
            ['ac_no' => '10', 'name' => 'Maureen Layca Delos Reyes',   'email' => 'maureenlayca.delosreyes@deped.gov.ph'],
            ['ac_no' => '33', 'name' => 'Remelyn Diaz',                'email' => 'remelyn.labajo@deped.gov.ph'],
            ['ac_no' => '23', 'name' => 'Jimmilyn Fameronag',          'email' => 'jimmilyn.fameronag@deped.gov.ph'],
            ['ac_no' => '24', 'name' => 'Jerico Fameronag',            'email' => 'jerico.fameronag@deped.gov.ph'],
            ['ac_no' => '16', 'name' => 'Merian Gonzales',             'email' => 'merian.gonzales@deped.gov.ph'],
            ['ac_no' => '45', 'name' => 'John Carlo Hernandez',        'email' => 'johncarlo.hernandez@deped.gov.ph'],
            ['ac_no' => '38', 'name' => 'Abegail Incilan',             'email' => 'abegail.incilan@deped.gov.ph'],
            ['ac_no' => '36', 'name' => 'Agnes Javier',                'email' => 'agnes.javier004@deped.gov.ph'],
            ['ac_no' => '44', 'name' => 'Danica Roma Javier',          'email' => 'danica.javier@deped.gov.ph'],
            ['ac_no' => '11', 'name' => 'Nancy Maano',                 'email' => 'maano.nancy.noceda@gmail.com'],
            ['ac_no' => '22', 'name' => 'Michael Macalindong',         'email' => 'michael.macalindong@deped.gov.ph'],
            ['ac_no' => '15', 'name' => 'Rhea Magyaya',                'email' => 'rhea.magyaya@deped.gov.ph'],
            ['ac_no' => '8',  'name' => 'Beverly Iodine Mapa',         'email' => 'beverlyiodine.mapa001@deped.gov.ph'],
            ['ac_no' => '7',  'name' => 'Evangeline Mendoza',          'email' => 'evangeline.mendoza011@deped.gov.ph'],
            ['ac_no' => '17', 'name' => 'Ruelito Mendoza',             'email' => 'ruelito.mendoza002@deped.gov.ph'],
            ['ac_no' => '29', 'name' => 'Robelyn Ordonia',             'email' => 'robelyn.ordonia@deped.gov.ph'],
            ['ac_no' => '1',  'name' => 'Angelique Piscal',            'email' => 'angelique.piscal@deped.gov.ph'],
            ['ac_no' => '20', 'name' => 'Rechelle Ramos',              'email' => 'rechelle.ramos001@deped.gov.ph'],
            ['ac_no' => '21', 'name' => 'Joanne Ricalde',              'email' => 'joanne.ricalde@deped.gov.ph'],
            ['ac_no' => '4',  'name' => 'Gil Robles',                  'email' => 'gil.robles001@deped.gov.ph'],
            ['ac_no' => '31', 'name' => 'Annie Rollon',                'email' => 'annie.delavega001@deped.gov.ph'],
            ['ac_no' => '9',  'name' => 'Edmarie Sagala',              'email' => 'edmarie.sagala001@deped.gov.ph'],
            ['ac_no' => '19', 'name' => 'Julius Salviejo',             'email' => 'julius.salviejo@deped.gov.ph'],
            ['ac_no' => '27', 'name' => 'Shiela Mae Sanchez',          'email' => 'shielamae.sanchez002@deped.gov.ph'],
            ['ac_no' => '13', 'name' => 'Geryl Sandoval',              'email' => 'geryl.aguila@deped.gov.ph'],
            ['ac_no' => '2',  'name' => 'Jorge Taguibao',              'email' => 'jorge.taguibao@deped.gov.ph'],
            ['ac_no' => '3',  'name' => 'Joy Valdez',                  'email' => 'joy.valdez003@deped.gov.ph'],
        ];

        foreach ($rows as $row) {
            $user = $this->db->table('users')->select('id')->where('email', $row['email'])->get()->getRow();

            $this->db->table('teachers')->insert([
                'employee_id'     => 'T-' . str_pad($row['ac_no'], 3, '0', STR_PAD_LEFT),
                'name'            => $row['name'],
                'email'           => $row['email'],
                'grade_level'     => null,
                'submission_rate' => 0.00,
                'user_id'         => $user->id ?? null,
            ]);
        }
    }
}
