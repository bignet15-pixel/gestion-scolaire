<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'inscription_id',
        'evaluation_id',
        'valeur',
        'appreciation',
        'is_deleted',
    ];

    protected $casts = [
        'valeur' => 'decimal:2',
        'is_deleted' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function inscription()
    {
        return $this->belongsTo(Inscription::class);
    }

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function estValide(): bool
    {
        if (! $this->evaluation) {
            return false;
        }

        return $this->valeur >= 0 && $this->valeur <= $this->evaluation->bareme;
    }
}