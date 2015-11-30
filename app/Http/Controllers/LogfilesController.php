<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Metric;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LogfilesController extends Controller
{
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
                        'log_date' => 'required|date_format:Y-m-d'
                ]
        );
        if ($validator->fails() === true)
        {
            return redirect()->route('logfiles.create')->withErrors($validator)->withInput();
        }
        $log_date = Carbon::createFromFormat('Y-m-d', $request->input('log_date'))->startOfDay();
        $log_date_str = $request->input('log_date');
        $log_file = $request->file('log_file');

        $path = realpath($log_file->getPathname());
        if ($path === false)
        {
            abort(404);
        }
        $linecount = 0;
        $handle = fopen($path, "r");

        /* Prepare lines data array */
        $lines = array();
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
            $log_time = Carbon::createFromFormat('Y-m-d H:i:s.u', $log_date_str . ' ' . $timestring);
            $ot_target = substr($command, 0, 1);
            $ot_type = substr($command, 1, 1);
            $ot_id = intval(substr($command, 3, 2), 16);
            $ot_payload = intval(substr($command, -4), 16);
            if (($ot_target === 'B') || ($ot_target === 'T') || ($ot_target === 'A') || ($ot_target === 'R') || ($ot_target === 'E'))
            {
                if (($ot_type === '1') || ($ot_type === '4') || ($ot_type === 'C') || ($ot_type === '9'))
                {
                    if (array_key_exists($ot_id, BackendController::$OT_IDS) === true)
                    {
                        $topic = BackendController::$OT_IDS[$ot_id];
                        switch (BackendController::$OT_IDS_TYPE[$ot_id])
                        {
                            case 'flag8':
                            {
                                if ($ot_id === 0)
                                {
                                    if($ot_payload !== 0)
                                    {
                                        $message = sprintf('%016b', $ot_payload);
                                    }
                                    foreach (BackendController::$OT_FLAME_IDS as $flame_id => $flame_label)
                                    {
                                        $value = (int)($ot_payload & (1 >> $flame_id));
                                        $metric = new Metric();
                                        $metric->meter_id = 256 + $flame_id;
                                        $metric->datetime = $log_time;
                                        $metric->value = (float)$value;
                                        //$metric->save();
                                    }
                                }
                            }
                                break;
                            case 'f8.8':
                            {
                                $message = (float)$ot_payload / 256.0;
                                $metric = new Metric();
                                $metric->meter_id = $ot_id;
                                $metric->datetime = $log_time;
                                $metric->value = (float)$message;
                                //$metric->save();
                            }
                                break;
                            case 'u16':
                            {
                                $message = $ot_payload;
                                $metric = new Metric();
                                $metric->meter_id = $ot_id;
                                $metric->datetime = $log_time;
                                $metric->value = (float)$message;
                                //$metric->save();
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
            $linecount++;
        }
        fclose($handle);
        redirect()->route('logfiles');
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
}
