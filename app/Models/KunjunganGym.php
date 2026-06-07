<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KunjunganGym extends Model
{
    protected $table = 'kunjungan_gyms';
    protected $primaryKey = 'kunjungan_id';
    
    protected $fillable = [
        'member_id', 
        'lokasi_id', 
        'tanggal', 
        'waktu_masuk', 
        'waktu_keluar', 
        'status_kunjungan'
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id', 'member_id');
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'lokasi_id', 'lokasi_id');
    }
}