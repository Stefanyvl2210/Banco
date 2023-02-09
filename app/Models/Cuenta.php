<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cuenta extends Model
{
    use HasFactory;
    protected $table = 'cuentas';

    protected $fillable = [
        'user_id',
        'num_cuenta',
        'tipo',
        'saldo',
    ];

    /**
     * Get the user for the account.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get transacciones
     */
    public function transacciones()
    {
        return $this->hasMany(Transaccion::class);
    }
}
