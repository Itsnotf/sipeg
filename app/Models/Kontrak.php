<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kontrak extends Model
{
    protected $guarded = ['id'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function kontrakDokumens()
    {
        return $this->hasMany(KontrakDokumen::class);
    }

    public function kontrakKaryawans()
    {
        return $this->hasMany(KontrakKaryawan::class);
    }

    public function penggajians()
    {
        return $this->hasMany(Penggajian::class);
    }
}
