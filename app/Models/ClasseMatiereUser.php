<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClasseMatiereUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'classe_id',
        'matiere_id',
        'user_id',
        'coefficient',
        'date_debut',
        'date_fin',
        'statut',
        'is_deleted',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'coefficient' => 'decimal:2',
        'is_deleted' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function matiere()
    {
        return $this->belongsTo(Matiere::class);
    }

    public function enseignant()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function emploisDuTemps()
    {
        return $this->hasMany(EmploiDuTemps::class, 'classe_matiere_user_id');
    }

    public function estActif(): bool
    {
        return $this->statut === 'actif';
    }

    public function estTermine(): bool
    {
        return $this->statut === 'termine';
    }

    public function estSuspendu(): bool
    {
        return $this->statut === 'suspendu';
    }
}