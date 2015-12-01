<?php

use Illuminate\Database\Seeder;
use App\Http\Controllers\LogfilesController;

class MetersTableSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (LogfilesController::$OT_IDS as $ot_id => $ot_object)
        {
            if ($ot_object['seed'] !== true)
            {
                continue;
            }
            DB::table('meters')->insert([
                'id' => $ot_id,
                'name' => $ot_object['label'],
            ]);
        }
    }
}
