<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penggajian extends Model
{
    protected $guarded = ['id'];

    public function kontrak()
    {
        return $this->belongsTo(Kontrak::class);
    }

    public function penggajianDetails()
    {
        return $this->hasMany(PenggajianDetail::class);
    }
}
