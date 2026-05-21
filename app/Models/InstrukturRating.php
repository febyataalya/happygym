<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstrukturRating extends Model
{
    protected $table = 'instruktur_ratings';

    protected $fillable = [
        'instruktur_id',
        'member_id',
        'booking_pt_id',
        'rating',
        'review'
    ];

    public function instruktur()
    {
        return $this->belongsTo(Instruktur::class, 'instruktur_id', 'instruktur_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id', 'member_id');
    }
}
