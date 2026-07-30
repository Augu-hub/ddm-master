<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class RiskIncident extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'risk_incidents';

    protected $fillable = [
        'tenant_id',
        'code_incident',
        'libelle',
        'description',
        'evaluation_monetaire',
        'devise',
        'date_incident',
        'source',
        'statut',
        'created_by',
        'moved_to_library_at',
        'converted_at',
        'risk_id',
    ];

    protected $casts = [
        'evaluation_monetaire' => 'decimal:2',
        'date_incident'        => 'date',
        'moved_to_library_at'  => 'datetime',
        'converted_at'         => 'datetime',
    ];

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActif(Builder $query): Builder
    {
        return $query->where('statut', 'actif');
    }

    public function scopeBibliotheque(Builder $query): Builder
    {
        return $query->where('statut', 'bibliotheque');
    }

    public function scopeConverti(Builder $query): Builder
    {
        return $query->where('statut', 'converti');
    }

    public function scopeTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    // ── Accessors ────────────────────────────────────────────────────────────

    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut) {
            'actif'       => 'Actif',
            'bibliotheque' => 'Bibliothèque',
            'converti'    => 'Converti en risque',
            default       => ucfirst($this->statut),
        };
    }

    public function getStatutBadgeAttribute(): string
    {
        return match ($this->statut) {
            'actif'       => 'warning',
            'bibliotheque' => 'info',
            'converti'    => 'success',
            default       => 'secondary',
        };
    }

    public function getEvaluationFormatteeAttribute(): ?string
    {
        if ($this->evaluation_monetaire === null) {
            return null;
        }
        return number_format((float) $this->evaluation_monetaire, 0, ',', ' ')
            . ' ' . $this->devise;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public static function generateCode(int $tenantId): string
    {
        $year = now()->format('Y');

        $count = static::on('tenant')
            ->where('tenant_id', $tenantId)
            ->whereYear('created_at', $year)
            ->withTrashed()
            ->count();

        return sprintf('INC-%s-%04d', $year, $count + 1);
    }
}
