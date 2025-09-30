<?php

namespace App\Models\Param;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Fonction extends Model
{
     protected $connection = 'tenant';
    protected $table = 'functions';

    protected $fillable = ['project_id','name','character','avatar_path','parent_id'];

    protected $appends = ['avatar_url'];

    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id'); }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path ? asset('storage/'.$this->avatar_path) : null;
    }
   
     // ✅ CORRECTION : Relation inverse avec la table pivot entity_function
   // Correct relationship
public function entities()
{
    return $this->belongsToMany(Entite::class, 'function_assignments', 'function_id', 'entity_id');
}
    public function scopeInProject($q, int|string $projectId)
    {
        return $q->where('project_id', $projectId);
    }
}
