<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Student extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = ['nim', 'nama', 'password'];

    protected $hidden = ['password'];

    // Password otomatis di-hash (bcrypt) saat disimpan.
    protected $casts = ['password' => 'hashed'];

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}
