<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendances';

    protected $fillable = [
        'employee_id',
        'date',

        'status',     // present|izin|leave|absent|late
        'division',
        'in_time',
        'out_time',
        'overtime_minutes',
        'overtime_reason',
        'note',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'employee_id');
    }
}
