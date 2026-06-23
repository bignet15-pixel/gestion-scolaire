<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Annonce extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPES = [
        'information' => 'Information',
        'reunion' => 'Réunion',
        'examen' => 'Examen / composition',
        'paiement' => 'Paiement',
        'resultats' => 'Résultats / bulletin',
        'discipline' => 'Discipline',
        'urgence' => 'Urgence',
        'autre' => 'Autre',
    ];

    public const PRIORITES = [
        'normale' => 'Normale',
        'importante' => 'Importante',
        'urgente' => 'Urgente',
    ];

    public const CIBLES = [
        'tous' => 'Tous les utilisateurs',
        'parents' => 'Tous les parents',
        'enseignants' => 'Tous les enseignants',
        'classe' => 'Une classe précise',
    ];

    protected $fillable = [
        'titre',
        'contenu',
        'type',
        'priorite',
        'cible',
        'classe_id',
        'publie_par',
        'est_publiee',
        'date_publication',
        'date_expiration',
        'is_deleted',
    ];

    protected $casts = [
        'est_publiee' => 'boolean',
        'date_publication' => 'datetime',
        'date_expiration' => 'datetime',
        'is_deleted' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function auteur()
    {
        return $this->belongsTo(User::class, 'publie_par');
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function notifications()
    {
        return $this->morphMany(NotificationUtilisateur::class, 'source');
    }

    public function libelleType(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function libellePriorite(): string
    {
        return self::PRIORITES[$this->priorite] ?? ucfirst($this->priorite);
    }

    public function libelleCible(): string
    {
        if ($this->cible === 'classe' && $this->classe) {
            return 'Classe : '.$this->classe->nom;
        }

        return self::CIBLES[$this->cible] ?? ucfirst($this->cible);
    }

    public function estExpiree(): bool
    {
        return $this->date_expiration !== null && $this->date_expiration->isPast();
    }

    public function peutEtrePubliee(): bool
    {
        return ! $this->est_publiee && trim($this->titre) !== '' && trim($this->contenu) !== '';
    }
}
