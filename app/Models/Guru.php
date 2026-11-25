<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guru extends Model
{
    protected $table = 'guru';
    protected $primaryKey = 'id_guru';

    public $timestamps = false;

    protected $fillable = ['nip', 'nama', 'jenis_kelamin', 'email', 'no_hp'];

    public function mapelDiampu()
    {
        return $this->hasMany(MapelDiampu::class, 'id_guru', 'id_guru');
    }
}