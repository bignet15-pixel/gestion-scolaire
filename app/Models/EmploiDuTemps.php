<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmploiDuTemps extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'emploi_du_temps';

    protected $fillable = [
        'classe_matiere_user_id',
        'jour',
        'heure_debut',
        'heure_fin',
        'salle',
        'date_debut',
        'date_fin',
        'is_deleted',
    ];

    protected $casts = [
        'heure_debut' => 'datetime:H:i',
        'heure_fin' => 'datetime:H:i',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'is_deleted' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function affectation()
    {
        return $this->belongsTo(ClasseMatiereUser::class, 'classe_matiere_user_id')
            ->withTrashed();
    }

    public function classe()
    {
        return $this->affectation?->classe;
    }

    public function matiere()
    {
        return $this->affectation?->matiere;
    }

    public function enseignant()
    {
        return $this->affectation?->enseignant;
    }
}
