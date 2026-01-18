<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KontrakDokumen extends Model
{
    protected $guarded = ['id'];

    public function kontrak()
    {
        return $this->belongsTo(Kontrak::class);
    }
}
