<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cashbon extends Model
{
    protected $guarded = ['id'];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }
}
