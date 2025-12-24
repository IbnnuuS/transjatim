<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyReport extends Model
{
    use HasFactory;

   protected $fillable = [
    'user_id',
    'nama',
    'divisi',
    'tanggal_laporan',
    'pekerjaan',
    'title',        // NEW
    'pic',          // NEW
    'progress',     // NEW
    'latitude',     // NEW
    'longitude',    // NEW
    'photo_url',    // renamed from foto
    'catatan',
    'status',       // now string: pending, in_progress, done, blocked
];

protected $casts = [
    'tanggal_laporan' => 'datetime',
    'progress' => 'integer',
    'latitude' => 'float',
    'longitude' => 'float',
];

}
