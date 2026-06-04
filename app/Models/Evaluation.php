<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evaluation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'classe_id',
        'matiere_id',
        'trimestre_id',
        'user_id',
        'nom',
        'type',
        'date_evaluation',
        'heure_debut',
        'heure_fin',
        'coefficient',
        'bareme',
        'is_deleted',
    ];

    protected $casts = [
        'date_evaluation' => 'date',
        'heure_debut' => 'datetime:H:i',
        'heure_fin' => 'datetime:H:i',
        'coefficient' => 'decimal:2',
        'bareme' => 'decimal:2',
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

    public function trimestre()
    {
        return $this->belongsTo(Trimestre::class);
    }

    public function createur()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function estDevoir(): bool
    {
        return $this->type === 'devoir';
    }

    public function estInterrogation(): bool
    {
        return $this->type === 'interrogation';
    }

    public function estComposition(): bool
    {
        return $this->type === 'composition';
    }

    public function estTest(): bool
    {
        return $this->type === 'test';
    }
}