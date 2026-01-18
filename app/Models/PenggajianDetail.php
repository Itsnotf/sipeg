<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenggajianDetail extends Model
{
    protected $guarded = ['id'];

    public function penggajian()
    {
        return $this->belongsTo(Penggajian::class);
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }
}
