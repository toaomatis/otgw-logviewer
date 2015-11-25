<?php

use Illuminate\Database\Seeder;

class MessagesTableSeeder extends Seeder
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
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (MessagesTableSeeder::$OT_IDS as $ot_id => $ot_label) {
            DB::table('messages')->insert([
                'id' => $ot_id,
                'label' => $ot_label,
            ]);
        }
    }
}
