<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Meter extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
            'name' => 'string',
    ];

    /**
     * Get the metrics for the meter.
     */
    public function metrics()
    {
        return $this->hasMany('App\Metric');
    }
}
