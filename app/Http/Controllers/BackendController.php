<?php

namespace App\Http\Controllers;

use App\Http\Requests;

class BackendController extends Controller
{
    const MAX_LINES = 100;

    public static $OT_IDS_TYPE = [
        0 => "",
        1 => "",
        9 => "",
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
        foreach ($all_files as $file)
        {
            if ((starts_with($file, 'otlog-') === true) && (ends_with($file, '.txt') === true))
            {
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
        if ($path === false)
        {
            abort(404);
        }
        $start = (int)$start;
        $stop = (int)$stop;
        $linecount = 0;
        $handle = fopen($path, "r");
        $stepsize = ($stop - $start) / BackendController::MAX_LINES;
        $lines = array();
        /* Prepare cols data array */
        $cols = array();
        $cols[] = array('id' => 0, 'label' => 'Timestamp', 'type' => 'string');
        foreach (BackendController::$OT_IDS as $ot_id => $ot_label)
        {
            if ($ot_id === 0)
            {
                foreach (BackendController::$OT_FLAME_IDS as $flame_id => $flame_label)
                {
                    $cols[] = array('id' => 1024 + $flame_id, 'label' => $flame_label, 'type' => 'number');
                }
                continue;
            }
            $cols[] = array('id' => $ot_id, 'label' => $ot_label, 'type' => 'number');
        }

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
            $timestamp = $this->stringToTimestamp($timestring);
            if ($timestamp < $start)
            {
                continue;
            } elseif ($timestamp > $stop)
            {
                break;
            }
            $units = $this->stringToUnits($timestring);
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
                        $array_idx = sprintf('%02d:%02d:00', $units[0], $units[1]);
                        if (array_key_exists($array_idx, $lines) === false)
                        {
                            foreach (BackendController::$OT_IDS as $id_key => $id_value)
                            {
                                if ($id_key === 0)
                                {
                                    foreach (BackendController::$OT_FLAME_IDS as $flame_id => $flame_label)
                                    {
                                        $lines[$array_idx][1024 + $flame_id] = 0;
                                    }
                                    continue;
                                }
                                $lines[$array_idx][$id_key] = null;
                            }
                        }
                        switch (BackendController::$OT_IDS_TYPE[$ot_id])
                        {
                            case 'flag8':
                            {
                                $message = sprintf('%016b', $ot_payload);
                                foreach (BackendController::$OT_FLAME_IDS as $flame_id => $flame_label)
                                {
                                    $value = (int)($ot_payload & (1 >> $flame_id));
                                    $lines[$array_idx][1024 + $flame_id] = $value;
                                }
                            }
                                break;
                            case 'f8.8':
                            {
                                $message = round((float)$ot_payload / 256.0, 2);
                                $lines[$array_idx][$ot_id] = $message;
                            }
                                break;
                            case 'u16':
                            {
                                $message = $ot_payload;
                                $lines[$array_idx][$ot_id] = $message;
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
        /* Prepare rows data array */
        $rows = array();
        foreach ($lines as $key => $values)
        {
            $row = array();
            $row[] = array('v' => $key);
            foreach ($values as $ot_id => $ot_value)
            {
                $row[] = array('v' => $ot_value);
            }
            $rows[]['c'] = $row;
        }
        $chart = array();
        $chart['cols'] = $cols;
        $chart['rows'] = $rows;
        return response()->json($chart);
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
