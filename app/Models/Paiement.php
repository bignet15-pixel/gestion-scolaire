<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Paiement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'inscription_id',
        'user_id',
        'numero_paiement',
        'montant',
        'date_paiement',
        'mode_paiement',
        'contact_parent',
        'contact_gestionnaire',
        'is_deleted',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_paiement' => 'date',
        'is_deleted' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function inscription()
    {
        return $this->belongsTo(Inscription::class);
    }

    public function gestionnaire()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function eleve()
    {
        return $this->inscription?->eleve;
    }

    public function classe()
    {
        return $this->inscription?->classe;
    }

    public function anneeScolaire()
    {
        return $this->inscription?->anneeScolaire;
    }
}