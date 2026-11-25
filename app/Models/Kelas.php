<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    protected $table = 'kelas';
    protected $primaryKey = 'id_kelas';

    // ✅ Tambahkan ini — daftar kolom yang boleh diisi via create()/update()
    protected $fillable = [
        'tingkat',
        'kelas',
        'id_wali'
    ];
    public $timestamps = false;
    // Opsional: jika kamu ingin cast id_wali sebagai integer
    protected $casts = [
        'id_wali' => 'integer'
    ];

    public function wali()
    {
        return $this->belongsTo(Guru::class, 'id_wali', 'id_guru');
    }
}