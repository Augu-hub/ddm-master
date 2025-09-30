<?php


namespace App\Models\Param;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Permission;

class Menu extends Model
{
    protected $fillable = [
        'key','label','icon','url','parent_id','sort',
        'is_title','is_divider','visible','badge_json','tooltip_json','meta_json'
    ];

    protected $casts = [
        'is_title' => 'bool',
        'is_divider' => 'bool',
        'visible' => 'bool',
        'badge_json' => 'array',
        'tooltip_json' => 'array',
        'meta_json' => 'array',
    ];

    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id')->orderBy('sort'); }
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'menu_permission', 'menu_id', 'permission_id');
    }
}
