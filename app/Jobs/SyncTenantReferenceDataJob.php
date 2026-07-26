<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\Tenant\TenantReferenceSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Dispatché automatiquement :
 *  - par les Observers de ddmparam (AuditType, MissionType, AuditTypeForm)
 *    à chaque création/modification (cf. AppServiceProvider::boot()) ;
 *  - par AuditTypeFormsController après chaque écriture DB::table() brute ;
 *  - à la création d'un nouveau tenant (TenantObserver).
 *
 * ⚠️ Job dédié à UN tenant : on dispatche une instance par tenant afin qu'un
 *    tenant en échec (DB injoignable, etc.) ne bloque pas les autres.
 *
 * ShouldBeUnique : si l'admin enchaîne 15 modifications de formulaires,
 * un seul job par tenant reste en file (dédoublonnage via cache_locks)
 * au lieu de 15 × N tenants. Le verrou est libéré dès l'exécution.
 */
class SyncTenantReferenceDataJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;
    public int $timeout = 300;

    /** Durée max (s) du verrou d'unicité si le job n'est jamais traité */
    public int $uniqueFor = 600;

    public function __construct(public int $tenantId)
    {
    }

    public function uniqueId(): string
    {
        return 'sync-tenant-reference-' . $this->tenantId;
    }

    public function handle(TenantReferenceSyncService $sync): void
    {
        $tenant = Tenant::find($this->tenantId);
        if (!$tenant) {
            Log::warning("SyncTenantReferenceDataJob: tenant #{$this->tenantId} introuvable (supprimé ?)");
            return;
        }

        $result = $sync->syncTenant($tenant);

        if (!empty($result['errors'])) {
            Log::error("⚠️ Synchro référentiel échouée pour le tenant {$tenant->code}", $result);
            // Laisse Laravel relancer (tries=3) si l'erreur est transitoire (connexion DB, etc.)
            $this->fail(new \RuntimeException(implode(' | ', $result['errors'])));
            return;
        }

        Log::info("✅ Synchro référentiel OK pour le tenant {$tenant->code}", $result);
    }

    public function failed(\Throwable $e): void
    {
        Log::error("❌ SyncTenantReferenceDataJob définitivement en échec pour le tenant #{$this->tenantId} : " . $e->getMessage());
    }
}
