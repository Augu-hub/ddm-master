<?php
// app/Models/Master/Menu.php
namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    /** 👇 très important */
    protected $connection = 'mysql';   // <— base maître
    protected $table = 'menus';

    protected $fillable = [
        'key','label','type','icon','url','route_name','target',
        'parent_id','sort','service_id','module_id',
        'visible','badge_json','tooltip_json','meta_json',
    ];
    // app/Models/Master/Menu.php
protected $casts = [
    'badge_json'   => 'array',
    'tooltip_json' => 'array',
    'meta_json'    => 'array',
    'visible'      => 'boolean',
    'sort'         => 'integer',
];



    public function parent()   { return $this->belongsTo(Menu::class, 'parent_id'); }
    public function children() { return $this->hasMany(Menu::class, 'parent_id')->orderBy('sort'); }

    public function scopeForModule($query, $moduleId = null, $includeGlobals = true)
    {
        if ($moduleId) {
            return $includeGlobals
                ? $query->where(fn($q) => $q->where('module_id', $moduleId)->orWhereNull('module_id'))
                : $query->where('module_id', $moduleId);
        }
        return $query;
    }
}
