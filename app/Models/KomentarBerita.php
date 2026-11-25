<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KomentarBerita extends Model
{
    protected $table = 'komentar_berita';
    protected $primaryKey = 'id_komentar';

    public $timestamps = false;

    protected $fillable = ['id_berita', 'id_user', 'komentar', 'tanggal_post'];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}