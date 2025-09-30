<?php

namespace App\Models\Param;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MacroProcessus extends Model
{
     protected $connection = 'tenant';     // <— IMPORTANT

    protected $table = 'macro_processes';

    protected $fillable = ['project_id','code','name','character','designation','kind'];

   public function project(): BelongsTo { return $this->belongsTo(Projet::class); }
    public function processes(): HasMany  { return $this->hasMany(Processus::class, 'macro_process_id'); }

     public static function nextCodeForProjectKind(int $projectId, string $kind, ?string $character = null): string
    {
        return strtoupper(substr($character ?: ($kind[0] ?? 'X'), 0, 1));
    }
}
