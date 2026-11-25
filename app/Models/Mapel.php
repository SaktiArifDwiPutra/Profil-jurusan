<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mapel extends Model
{
    protected $table = 'mapel';
    protected $primaryKey = 'id_mapel';

    public $timestamps = false;

    protected $fillable = ['nama_mapel', 'kategori'];

    // Relasi ke mapel_diampu (untuk menampilkan guru pengampu)
    public function mapelDiampu()
    {
        return $this->hasMany(\App\Models\MapelDiampu::class, 'id_mapel', 'id_mapel');
    }
}