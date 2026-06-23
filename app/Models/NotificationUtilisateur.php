<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationUtilisateur extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPES = [
        'annonce' => 'Annonce',
        'note' => 'Note',
        'absence' => 'Absence',
        'retard' => 'Retard',
        'sanction' => 'Sanction',
        'paiement' => 'Paiement',
        'justification' => 'Justification',
        'reinscription' => 'Réinscription',
        'resultats' => 'Résultats / bulletin',
        'compte' => 'Compte',
        'information' => 'Information',
    ];

    protected $table = 'notifications_utilisateurs';

    protected $fillable = [
        'user_id',
        'titre',
        'message',
        'type',
        'lien',
        'source_type',
        'source_id',
        'lue',
        'lue_le',
        'email_mode',
        'email_resume',
        'email_raison_connexion',
        'email_statut',
        'email_envoye_le',
        'email_erreur',
        'metadata',
        'is_deleted',
    ];

    protected $casts = [
        'lue' => 'boolean',
        'lue_le' => 'datetime',
        'email_envoye_le' => 'datetime',
        'metadata' => 'array',
        'is_deleted' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function source()
    {
        return $this->morphTo();
    }

    public function scopeNonLues($query)
    {
        return $query->where('lue', false);
    }

    public function scopeLues($query)
    {
        return $query->where('lue', true);
    }

    public function marquerCommeLue(): void
    {
        if ($this->lue) {
            return;
        }

        $this->update([
            'lue' => true,
            'lue_le' => now(),
        ]);
    }

    public function libelleType(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function emailEnvoye(): bool
    {
        return $this->email_statut === 'sent';
    }
}
