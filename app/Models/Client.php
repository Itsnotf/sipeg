<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $guarded = ['id'];

    public function kontraks()
    {
        return $this->hasMany(Kontrak::class);
    }
}
