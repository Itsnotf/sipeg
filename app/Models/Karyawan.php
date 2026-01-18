<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model
{
    protected $guarded = ['id'];

    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class, 'id_jabatan');
    }

    public function kontrakKaryawans()
    {
        return $this->hasMany(KontrakKaryawan::class);
    }

    public function cashbons()
    {
        return $this->hasMany(Cashbon::class);
    }
}
