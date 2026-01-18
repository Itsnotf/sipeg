<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jabatan extends Model
{
    protected $guarded = ['id'];

    public function karyawans()
    {
        return $this->hasMany(Karyawan::class, 'id_jabatan');
    }
}
