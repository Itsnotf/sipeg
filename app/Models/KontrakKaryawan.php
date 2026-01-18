<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KontrakKaryawan extends Model
{
    protected $guarded = ['id'];

    public function kontrak()
    {
        return $this->belongsTo(Kontrak::class);
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }
}