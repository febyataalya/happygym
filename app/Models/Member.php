<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable; 
use Illuminate\Notifications\Notifiable;

class Member extends Authenticatable 
{
    use Notifiable;

    protected $table = 'members';
    protected $primaryKey = 'member_id';

    protected $fillable = [
        'nama', 'email', 'password', 'foto', 'no_hp', 'status_membership', 
        'tanggal_mulai_member', 'tanggal_berakhir_member', 'lokasi_id'
    ];

    protected $hidden = [
        'password',
    ];

    protected $appends = ['foto_url'];

    public function getFotoUrlAttribute()
    {
        return $this->foto ? asset('storage/' . $this->foto) : null;
    }

    public function paketPts()
    {
        return $this->hasMany(MemberPaketPt::class, 'member_id', 'member_id')->where('status', 'Aktif');
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'lokasi_id', 'lokasi_id');
    }

    public function dataFisik()
    {
        return $this->hasMany(DataFisikMember::class, 'member_id', 'member_id')->orderBy('tanggal_pencatatan', 'desc');
    }

    public function pembayarans()
    {
        return $this->hasMany(Pembayaran::class, 'member_id', 'member_id');
    }
}