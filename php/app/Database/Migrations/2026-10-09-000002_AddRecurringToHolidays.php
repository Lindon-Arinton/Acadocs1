<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Holidays can repeat every year (same month and day), and the Philippine
 * national holidays are loaded once so they apply every year without being
 * re-entered. Movable ones (Maundy Thursday, Good Friday, Black Saturday,
 * National Heroes Day) aren't stored: HolidayModel::movableFor() computes them.
 */
class AddRecurringToHolidays extends Migration
{
    /** [date, label, recurring] — the year of a recurring row doesn't matter. */
    public const HOLIDAYS = [
        ['2026-01-01', "New Year's Day", 1],
        ['2026-04-09', 'Day of Valor (Araw ng Kagitingan)', 1],
        ['2026-05-01', 'Labor Day', 1],
        ['2026-06-12', 'Independence Day', 1],
        ['2026-08-21', 'Ninoy Aquino Day', 1],
        ['2026-11-01', "All Saints' Day", 1],
        ['2026-11-02', "All Souls' Day", 1],
        ['2026-11-30', 'Bonifacio Day', 1],
        ['2026-12-08', 'Feast of the Immaculate Conception of Mary', 1],
        ['2026-12-24', 'Christmas Eve', 1],
        ['2026-12-25', 'Christmas Day', 1],
        ['2026-12-30', 'Rizal Day', 1],
        ['2026-12-31', "Last Day of the Year (New Year's Eve)", 1],
        // Lunar date: set again each year under Manage Holidays.
        ['2026-02-17', 'Chinese New Year', 0],
        // EDSA Anniversary (Feb 25) is a special *working* holiday: a regular school day, so not listed.
    ];

    public function up()
    {
        $this->forge->addColumn('holidays', [
            'recurring' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0, 'after' => 'label'],
        ]);

        foreach (self::HOLIDAYS as [$date, $label, $recurring]) {
            $this->db->query(
                'INSERT INTO holidays (date, label, recurring) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE label = VALUES(label), recurring = VALUES(recurring)',
                [$date, $label, $recurring]
            );
        }
    }

    public function down()
    {
        $this->forge->dropColumn('holidays', 'recurring');
    }
}
