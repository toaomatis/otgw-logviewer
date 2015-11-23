<?php

namespace App\Http\Controllers;

use App\Http\Requests;

class BackendController extends Controller
{
    const MAX_LINES = 100;

    public static $OT_IDS = [
        //0 => "flame_status",
        1 => "control_setpoint",
        //9 => "remote_override_setpoint",
        16 => "room_setpoint",
        24 => "room_temperature",
        25 => "boiler_water_temperature",
        26 => "dhw_temperature",
        28 => "return_water_temperature",
        //116 => "burner_starts",
        //117 => "ch_pump_starts",
        //119 => "dhw_burner_starts",
        //120 => "burner_operation_hours",
        //121 => "ch_pump_operation_hours",
        //123 => "dhw_burner_operation_hours",
    ];

    public static $OT_IDS_TYPE = [
        0 => "flag8",
        1 => "f8.8",
        9 => "f8.8",
        16 => "f8.8",
        24 => "f8.8",
        25 => "f8.8",
        26 => "f8.8",
        28 => "f8.8",
        116 => "u16",
        117 => "u16",
        119 => "u16",
        120 => "u16",
        121 => "u16",
        123 => "u16"
    ];

    public
    function logfiles()
    {
        $log_dir = $_ENV['LOG_DIR'];
        $all_files = scandir($log_dir);
        $files = array();
        foreach ($all_files as $file) {
            if ((starts_with($file, 'otlog-') === true) && (ends_with($file, '.txt') === true)) {
                $files[] = $file;
            }
        }
        return response()->json($files);
    }

    public
    function logfile($filename, $extension, $start = 0, $stop = 86400)
    {
        $log_dir = $_ENV['LOG_DIR'];
        $logfile = $log_dir . $filename . '.' . $extension;
        $path = realpath($logfile);
        if ($path === false) {
            abort(404);
        }
        $start = (int)$start;
        $stop = (int)$stop;
        $linecount = 0;
        $handle = fopen($path, "r");
        $stepsize = ($stop - $start) / BackendController::MAX_LINES;
        $lines = array();
        $lines['ot_ids'] = BackendController::$OT_IDS;
        $lines['ot_data'] = array();
        while (!feof($handle)) {
            $line = fgets($handle);
            if ($line[0] === ' ') {
                continue;
            }
            $fields = preg_split('/\s+/', $line, 3, PREG_SPLIT_NO_EMPTY);
            if (count($fields) < 3) {
                continue;
            }
            list($timestring, $command, $suffix) = $fields;
            $timestamp = $this->stringToTimestamp($timestring);
            if ($timestamp < $start) {
                continue;
            } elseif ($timestamp > $stop) {
                break;
            }
            $units = $this->stringToUnits($timestring);
            $ot_target = substr($command, 0, 1);
            $ot_type = substr($command, 1, 1);
            $ot_id = intval(substr($command, 3, 2), 16);
            $ot_payload = intval(substr($command, -4), 16);
            if (($ot_target === 'B') || ($ot_target === 'T') || ($ot_target === 'A') || ($ot_target === 'R') || ($ot_target === 'E')) {
                if (($ot_type === '1') || ($ot_type === '4') || ($ot_type === 'C') || ($ot_type === '9')) {
                    if (array_key_exists($ot_id, BackendController::$OT_IDS) === true) {
                        $topic = BackendController::$OT_IDS[$ot_id];
                        switch (BackendController::$OT_IDS_TYPE[$ot_id]) {
                            case 'flag8': {
                                $message = sprintf('%016b', $ot_payload);
                            }
                                break;
                            case 'f8.8': {
                                $message = round((float)$ot_payload / 256.0, 2);
                            }
                                break;
                            case 'u16': {
                                $message = $ot_payload;
                            }
                                break;
                        }
                        $array_idx = sprintf('%02d:%02d:00', $units[0], $units[1]);
                        if(array_key_exists($array_idx, $lines['ot_data']) === false)
                        {
                            foreach(BackendController::$OT_IDS as $id_key => $id_value)
                            {
                                $lines['ot_data'][$array_idx][$id_key] = null;
                            }
                        }
                        $lines['ot_data'][$array_idx][$ot_id] = $message;
                    }
                }
            }
            $linecount++;
        }
        fclose($handle);

        return response()->json($lines);
    }

    private function stringToTimestamp($string)
    {
        $timestamp = 0;
        $fields = explode(':', $string);
        $timestamp += ((int)$fields[0] * (60 * 60));
        $timestamp += ((int)$fields[1] * (60));
        $timestamp += (float)$fields[2];
        return $timestamp;
    }

    private function stringToUnits($string)
    {
        $fields = explode(':', $string);
        $units[] = (int)$fields[0];
        $units[] = (int)$fields[1];
        $units[] = (float)$fields[2];
        return $units;
    }
}
