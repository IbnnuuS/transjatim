<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User; // <-- tambahkan

class Schedule extends Model
{
    protected $fillable = [
        'user_id',
        'tanggal',
        'periode',
        'divisi',
        'judul',
        'catatan',
        'jam_mulai',
        'jam_selesai',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jam_mulai' => 'datetime:H:i',
        'jam_selesai' => 'datetime:H:i',
    ];

    // Relasi ke user/karyawan
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // (Opsional) alias agar seragam dengan istilah "employee"
    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
