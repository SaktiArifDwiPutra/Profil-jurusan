<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Siswa;  // 👈 Tambahkan ini
use App\Models\Guru;   // 👈 Tambahkan ini

class User extends Authenticatable
{
    protected $table = 'user';
    protected $primaryKey = 'id_user';
    public $timestamps = false;

    protected $fillable = [
        'username',
        'password',
        'role',
        'id_guru',
        'id_siswa',
    ];

    protected $hidden = [
        'password',
    ];

    // ✅ Tambahkan relasi ke Siswa
    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    // ✅ Tambahkan relasi ke Guru
    public function guru()
    {
        return $this->belongsTo(Guru::class, 'id_guru', 'id_guru');
    }
}