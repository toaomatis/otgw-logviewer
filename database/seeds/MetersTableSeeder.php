<?php

use Illuminate\Database\Seeder;

class MetersTableSeeder extends Seeder
{
    public static $OT_IDS = [
        0 => "flame_status",
        1 => "control_setpoint",
        9 => "remote_override_setpoint",
        16 => "room_setpoint",
        24 => "room_temperature",
        25 => "boiler_water_temperature",
        26 => "dhw_temperature",
        28 => "return_water_temperature",
        116 => "burner_starts",
        117 => "ch_pump_starts",
        119 => "dhw_burner_starts",
        120 => "burner_operation_hours",
        121 => "ch_pump_operation_hours",
        123 => "dhw_burner_operation_hours",

        256 => 'ch_enable',
        257 => 'dhw_enable',
        258 => 'cooling_enable',
        259 => 'otc_active',
        260 => 'ch2_enable',
        261 => 'summer_winter',
        262 => 'dhw_blocking',
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (MetersTableSeeder::$OT_IDS as $ot_id => $ot_label) {
            DB::table('meters')->insert([
                'id' => $ot_id,
                'name' => $ot_label,
            ]);
        }
    }
}
