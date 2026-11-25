<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Berita extends Model
{
    protected $table = 'berita';
    protected $primaryKey = 'id_berita';

    public $timestamps = false;

    protected $fillable = [
        'id_guru',
        'created_by',
        'judul',
        'isi',
        'gambar',
        'tanggal_post'
    ];

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'id_guru', 'id_guru');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by', 'id_user');
    }

    public function komentars()
    {
        return $this->hasMany(KomentarBerita::class, 'id_berita', 'id_berita');
    }
}