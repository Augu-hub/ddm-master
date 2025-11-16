<?php

namespace App\Models\Param;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = 'menus';

    protected $fillable = [
        'key','label','type','icon','url','route_name','target',
        'parent_id','sort',
        'service_id','module_id',
        'visible','badge_json','tooltip_json','meta_json',
    ];

    protected $casts = [
        'visible'     => 'boolean',
        'badge_json'  => 'array',
        'tooltip_json'=> 'array',
        'meta_json'   => 'array',
    ];

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('sort');
    }

    /** menus d’un module (optionnellement + globaux) */
    public function scopeForModule($query, $moduleId = null, $includeGlobals = true)
    {
        if ($moduleId) {
            if ($includeGlobals) {
                $query->where(function($q) use ($moduleId) {
                    $q->where('module_id', $moduleId)
                      ->orWhereNull('module_id');
                });
            } else {
                $query->where('module_id', $moduleId);
            }
        }
        return $query;
    }
}
