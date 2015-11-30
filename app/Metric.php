<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Metric extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['datetime'];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
            'value' => 'double',
    ];

    /**
     * Get the meter that owns the metric.
     */
    public function meter()
    {
        return $this->belongsTo('App\Meter');
    }
}
