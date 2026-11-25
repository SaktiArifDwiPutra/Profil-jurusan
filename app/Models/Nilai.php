<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nilai extends Model
{
    protected $table = 'nilai';
    protected $primaryKey = 'id_nilai';

    public $timestamps = false;

    protected $fillable = ['id_siswa', 'id_ampu', 'semester', 'nilai_tugas', 'nilai_uts', 'nilai_akhir'];

    public function ampu()
    {
        return $this->belongsTo(MapelDiampu::class, 'id_ampu', 'id_ampu');
    }
    
    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function kelas()
    {
        return $this->belongsTo(Siswa::class, 'id_kelas', 'id_kelas');
    }
}