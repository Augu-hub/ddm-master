<?php

namespace App\Models\Param;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Processus extends Model
{
 protected $connection = 'tenant';     // <— IMPORTANT


    protected $table = 'processes';


    protected $fillable = ['macro_process_id','code','name'];

    public function macro(): BelongsTo
    {
        return $this->belongsTo(MacroProcessus::class, 'macro_process_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activite::class);
    }

    public static function macroPrefix(MacroProcessus $macro): string
    {
        if (preg_match('/^[A-Z]\d{2}/', (string)$macro->code, $m)) return $m[0];
        $M = strtoupper(substr($macro->character ?: ($macro->kind[0] ?? 'X'), 0, 1));
        return $M . '01';
    }

    /** Code Processus = Mmm + P + pp (pp=2 chiffres par macro) */
    public static function nextCodeForMacro(MacroProcessus $macro): string
    {
        // Lettre du macro: character prioritaire, sinon 1ère lettre du kind, sinon code macro
        $macroLetter = strtoupper(
            substr($macro->character ?: ($macro->kind[0] ?? $macro->code ?? 'X'), 0, 1)
        );

        // nombre de processus existants sous ce macro
        $count = static::where('macro_process_id', $macro->id)->lockForUpdate()->count();
        $pp = str_pad((string)($count + 1), 2, '0', STR_PAD_LEFT);

        return 'P' . $pp . $macroLetter; // ex: P01D
    }
}
