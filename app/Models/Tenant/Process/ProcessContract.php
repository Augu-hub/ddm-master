<?php

namespace App\Models\Tenant\Process;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessContract extends Model
{
    // Note: SoftDeletes retiré (pas de colonne deleted_at dans la migration)

    protected $connection = 'tenant';
    protected $table = 'process_contracts';

    protected $fillable = [
        'process_id',
        'entity_id',
        'function_id',
        'user_id',
        'owner',
        'purpose',
        'status',
        'inputs',
        'outputs',
        'resources',
        'activity_indicators',
        'performance_indicators',
        'attachments'
    ];

    protected $casts = [
        'inputs' => 'array',
        'outputs' => 'array',
        'resources' => 'array',
        'activity_indicators' => 'array',
        'performance_indicators' => 'array',
        'attachments' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        // 'archived_at' retiré (colonne n'existe pas)
    ];

    /**
     * 🔗 Relations
     */
    public function process(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Param\Processus::class, 'process_id');
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Param\Entity::class, 'entity_id');
    }

    public function function(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Param\Function::class, 'function_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ProcessContractHistory::class, 'contract_id')
            ->orderBy('created_at', 'desc');
    }

    /**
     * 📊 Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByProcess($query, $processId)
    {
        return $query->where('process_id', $processId);
    }

    public function scopeByEntity($query, $entityId)
    {
        return $query->where('entity_id', $entityId);
    }

    public function scopeByFunction($query, $functionId)
    {
        return $query->where('function_id', $functionId);
    }

    /**
     * 📝 Accessors & Mutators
     */
    public function getOutputsCountAttribute(): int
    {
        return count($this->outputs ?? []);
    }

    public function getInputsCountAttribute(): int
    {
        return count($this->inputs ?? []);
    }

    public function getResourcesCountAttribute(): int
    {
        return count($this->resources ?? []);
    }

    /**
     * 🔄 Méthodes utiles
     */
    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function archive()
    {
        $this->update(['status' => 'archived']);
        $this->logHistory('archived', 'Contrat archivé');
    }

    public function restore()
    {
        $this->update(['status' => 'active']);
        $this->logHistory('restored', 'Contrat restauré');
    }

    /**
     * 📜 Log une modification dans l'historique
     */
    public function logHistory(
        string $action,
        string $description = null,
        array $oldValues = null,
        array $newValues = null,
        string $fileName = null,
        string $fileType = null
    ) {
        $this->histories()->create([
            'user_id' => auth()?->id(),
            'user_name' => auth()?->user()?->name,
            'action' => $action,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'file_name' => $fileName,
            'file_type' => $fileType,
        ]);
    }
}