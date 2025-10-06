<?php

namespace App\Models\Param;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Illuminate\Support\Facades\Schema;

class Entite extends Model
{
    protected $connection = 'tenant';
    protected $table = 'entities';

    protected $fillable = [
        'name','description','level','parent_id','code_base',
        'logo','phone','email','leader','address',
    ];

    // Hiérarchie
    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id'); }

    // Pivot "officiel"
    public function functions(): BelongsToMany
    {
        return $this->belongsToMany(Fonction::class, 'entity_function', 'entity_id', 'function_id')
            ->withTimestamps();
    }

    // Table historique (présente dans ta base actuelle)
    public function functionsLegacy(): BelongsToMany
    {
        return $this->belongsToMany(Fonction::class, 'function_assignments', 'entity_id', 'function_id')
            ->withTimestamps();
    }

    /** Renvoie la bonne relation selon les tables existantes */
    public function functionsSmart(): BelongsToMany
    {
        if (Schema::connection($this->getConnectionName())->hasTable('entity_function')) {
            return $this->functions();
        }
        // Dans ton dump, la table function_assignments existe et a des données.
        return $this->functionsLegacy();
    }
    public function project(): BelongsTo
    {
        return $this->belongsTo(Projet::class, 'project_id');
    }

   

    /**
     * Génère un code base :
     * - prefix = parent.code_base si parent, sinon 3 lettres du nom (MAJ, sans accents, alphanum)
     * - suffix = compteur des frères/soeurs au sein (project_id, parent_id) => -01, -02, ...
     */
  public static function generateCodeBase($projectId, $parentId, string $name): string
{
    // 1) Préfixe : parent.code_base (nettoyé) OU 3 lettres du nom (nettoyées)
    $prefix = null;

    if (!empty($parentId)) {
        $parent = self::find($parentId);
        if ($parent && !empty($parent->code_base)) {
            // Supprimer tout ce qui n'est pas alphanumérique (dont les tirets), et upper
            $prefix = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $parent->code_base));
        }
    }

    if (!$prefix) {
        // 3 premières lettres du nom, sans accents/espaces/ponctuation
        $raw = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::ascii($name));
        $raw = preg_replace('/[^A-Z0-9]/', '', $raw);
        $prefix = substr($raw, 0, 3);
        if (strlen($prefix) < 3) {
            $prefix = str_pad($prefix, 3, 'X'); // sûreté
        }
    }

    // 2) Compteur des frères/soeurs au sein (project_id, parent_id)
    $siblingsCount = self::where('project_id', $projectId)
        ->where(function ($q) use ($parentId) {
            if ($parentId) $q->where('parent_id', $parentId);
            else $q->whereNull('parent_id');
        })
        ->count();

    $seq = str_pad((string)($siblingsCount + 1), 2, '0', STR_PAD_LEFT);

    // 3) Concaténation SANS séparateur
    return $prefix . $seq; // ex: KEK01 (si parent KEK → enfant KEK01 ; petit-enfant KEK0101, etc.)
}
 // Correct - using the actual table name


}
