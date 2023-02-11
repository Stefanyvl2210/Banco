<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaccion extends Model
{
    use HasFactory;
    protected $table = 'transacciones';

    protected $fillable = [
        'cuenta_id',
        'num_transaccion',
        'tipo',
        'cantidad',
        'fecha',
    ];

    /**
     * Get the account.
     */
    public function cuenta()
    {
        return $this->belongsTo(Cuenta::class);
    }
}
