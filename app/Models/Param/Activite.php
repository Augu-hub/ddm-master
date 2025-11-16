<?php
// app/Models/Param/Activity.php
namespace App\Models\Param;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activite extends Model
{
    protected $connection = 'tenant';
    protected $table = 'activities';
    protected $fillable = ['process_id','code','name','description'];

    public function process(): BelongsTo { return $this->belongsTo(Processus::class); }
    public static function nextCodeForProcess(Processus $process): string
    {
        $procCode = strtoupper((string)$process->code);
        $pp = '01'; $macroLetter = 'X';

        if (preg_match('/^P(\d{2})([A-Z])$/', $procCode, $m)) {
            $pp = $m[1];
            $macroLetter = $m[2];
        } else {
            // fallback si le code du process ne respecte pas le pattern
            $macroLetter = strtoupper(substr($procCode, -1)) ?: 'X';
        }

        $count = static::where('process_id', $process->id)->lockForUpdate()->count();
        $aa = str_pad((string)($count + 1), 2, '0', STR_PAD_LEFT);

        return 'A' . $aa . 'P' . $pp . $macroLetter; // ex: A01P01D
    }
}
