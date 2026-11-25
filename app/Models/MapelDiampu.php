<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Guru;
use App\Models\Mapel;
use App\Models\Kelas;

class MapelDiampu extends Model
{
    protected $table = 'mapel_diampu';
    protected $primaryKey = 'id_ampu';

    public $timestamps = false;

    protected $fillable = ['id_guru', 'id_mapel', 'id_kelas'];

    // ✅ Tambahkan relasi ke Guru
    public function guru()
    {
        return $this->belongsTo(Guru::class, 'id_guru', 'id_guru');
    }

    // Relasi ke Mapel (opsional, tapi bagus)
    public function mapel()
    {
        return $this->belongsTo(Mapel::class, 'id_mapel', 'id_mapel');
    }

    // Relasi ke Kelas (opsional)
    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas');
    }
}