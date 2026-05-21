<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Instruktur extends Authenticatable
{
    use Notifiable;

    protected $table = 'instrukturs';
    protected $primaryKey = 'instruktur_id';

    protected $fillable = [
        'nama',
        'username',
        'password',
        'spesialisasi',
        'lokasi_id',
        'foto', // Pastikan ini ada
    ];

    protected $hidden = [
        'password',
    ];

    protected $appends = ['foto_url', 'rating_avg'];

    public function getFotoUrlAttribute()
    {
        return $this->foto ? asset('storage/' . $this->foto) : null;
    }

    // Relasi ke tabel Lokasi (Cabang)
    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'lokasi_id', 'lokasi_id');
    }

    public function ratings()
    {
        return $this->hasMany(InstrukturRating::class, 'instruktur_id', 'instruktur_id');
    }

    public function memberPaketPts()
    {
        return $this->hasMany(MemberPaketPt::class, 'instruktur_id', 'instruktur_id');
    }

    public function getRatingAvgAttribute()
    {
        return number_format($this->ratings()->avg('rating') ?? 0, 1);
    }
}