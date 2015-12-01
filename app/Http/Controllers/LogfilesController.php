<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Metric;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LogfilesController extends Controller
{
    public static $OT_IDS = [
        0 => [
            'label' => 'flame_status',
            'seed' => false,
            'type' => 'flag8'
        ],
        1 => [
            'label' => 'control_setpoint',
            'seed' => true,
            'type' => 'f8.8'
        ],
        9 => [
            'label' => 'remote_override_setpoint',
            'seed' => true,
            'type' => 'f8.8'
        ],
        16 => [
            'label' => 'room_setpoint',
            'seed' => true,
            'type' => 'f8.8',
        ],
        24 => [
            'label' => 'room_temperature',
            'seed' => true,
            'type' => 'f8.8',
        ],
        25 => [
            'label' => 'boiler_water_temperature',
            'seed' => true,
            'type' => 'f8.8',
        ],
        26 => [
            'label' => 'dhw_temperature',
            'seed' => true,
            'type' => 'f8.8',
        ],
        28 => [
            'label' => 'return_water_temperature',
            'seed' => true,
            'type' => 'f8.8',
        ],
        116 => [
            'label' => 'burner_starts',
            'seed' => true,
            'type' => 'u16',
        ],
        117 => [
            'label' => 'ch_pump_starts',
            'seed' => true,
            'type' => 'u16',
        ],
        119 => [
            'label' => 'dhw_burner_starts',
            'seed' => true,
            'type' => 'u16',
        ],
        120 => [
            'label' => 'burner_operation_hours',
            'seed' => true,
            'type' => 'u16',
        ],
        121 => [
            'label' => 'ch_pump_operation_hours',
            'seed' => true,
            'type' => 'u16',
        ],
        123 => [
            'label' => 'dhw_burner_operation_hours',
            'seed' => true,
            'type' => 'u16',
        ],
        256 => [
            'label' => 'fault_indicator',
            'seed' => true,
            'type' => '1b',
        ],
        257 => [
            'label' => 'ch_active',
            'seed' => true,
            'type' => '1b'
        ],
        258 => [
            'label' => 'dhw_active',
            'seed' => true,
            'type' => '1b',
        ],
        259 => [
            'label' => 'flame_status',
            'seed' => true,
            'type' => '1b',
        ],
        260 => [
            'label' => 'cooling_active',
            'seed' => true,
            'type' => '1b',
        ],
        261 => [
            'label' => 'ch2_active',
            'seed' => true,
            'type' => '1b',
        ],
        262 => [
            'label' => 'diagnostic_indicator',
            'seed' => true,
            'type' => '1b',
        ],
        263 => [
            'label' => 'electricity_production',
            'seed' => true,
            'type' => '1b',
        ],
    ];

    public static $OT_FLAME_IDS = [
        0 => 256,
        1 => 257,
        2 => 258,
        3 => 259,
        4 => 260,
        5 => 261,
        6 => 262,
        7 => 263,
    ];

    /* Prepare lines data array */
    private $last_logged = array();

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('logfiles.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('logfiles.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),
            [
                'log_file' => 'required',
                'log_date' => 'required|date_format:Ymd'
            ]
        );
        if ($validator->fails() === true)
        {
            return redirect()->route('logfiles.create')->withErrors($validator)->withInput();
        }
        $log_date_str = $request->input('log_date');
        $log_file = $request->file('log_file');

        $path = realpath($log_file->getPathname());
        if ($path === false)
        {
            abort(404);
        }
        $handle = fopen($path, "r");

        foreach (LogfilesController::$OT_IDS as $meter_id => $meter)
        {
            $this->last_logged[$meter_id]['value'] = -1;
            $this->last_logged[$meter_id]['datetime'] = null;
        }
        while (!feof($handle))
        {
            $line = fgets($handle);
            if ($line[0] === ' ')
            {
                continue;
            }
            $fields = preg_split('/\s+/', $line, 3, PREG_SPLIT_NO_EMPTY);
            if (count($fields) < 3)
            {
                continue;
            }
            list($timestring, $command, $suffix) = $fields;
            $log_time = Carbon::createFromFormat('Ymd H:i:s.u', $log_date_str . ' ' . $timestring);
            $ot_target = substr($command, 0, 1);
            $ot_type = substr($command, 1, 1);
            $ot_id = intval(substr($command, 3, 2), 16);
            $ot_payload = intval(substr($command, -4), 16);
            if (($ot_target === 'B') || ($ot_target === 'T') || ($ot_target === 'A') || ($ot_target === 'R') || ($ot_target === 'E'))
            {
                if (($ot_type === '1') || ($ot_type === '4') || ($ot_type === 'C') || ($ot_type === '9'))
                {
                    if (array_key_exists($ot_id, LogfilesController::$OT_IDS) === true)
                    {
                        $type = LogfilesController::$OT_IDS[$ot_id]['type'];
                        switch ($type)
                        {
                            case 'flag8':
                            {
                                if ($ot_id === 0)
                                {
                                    foreach (LogfilesController::$OT_FLAME_IDS as $flame_id => $meter_id)
                                    {
                                        $value = (int)(($ot_payload >> $flame_id) & 1);
                                        $this->saveMetric($meter_id, $value, $log_time);
                                    }
                                }
                            }
                                break;
                            case 'f8.8':
                            {
                                $value = (float)$ot_payload / 256.0;
                                $this->saveMetric($ot_id, $value, $log_time);
                            }
                                break;
                            case 'u16':
                            {
                                $value = $ot_payload;
                                $this->saveMetric($ot_id, $value, $log_time);
                            }
                                break;
                            default:
                            {
                                abort(500);
                            }
                                break;
                        }
                    }
                }
            }
        }
        fclose($handle);
        return redirect()->route('logfiles.create');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    private function saveMetric($meter_id, $value, $datetime)
    {
        if ($this->last_logged[$meter_id]['value'] !== $value)
        {
            if ($this->last_logged[$meter_id]['datetime'] !== null)
            {
                $metric_last = new Metric();
                $metric_last->meter_id = $meter_id;
                $metric_last->value = $this->last_logged[$meter_id]['value'];
                $metric_last->datetime = $this->last_logged[$meter_id]['datetime'];
                $metric_last->save();
            }
            $metric = new Metric();
            $metric->meter_id = $meter_id;
            $metric->value = $value;
            $metric->datetime = $datetime;
            $metric->save();
            $this->last_logged[$meter_id]['datetime'] = null;
            $this->last_logged[$meter_id]['value'] = $value;
        } else
        {
            $this->last_logged[$meter_id]['datetime'] = $datetime;
        }
    }
}
