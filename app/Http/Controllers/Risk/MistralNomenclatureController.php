<?php

namespace App\Http\Controllers\Risk;

use App\Enums\NomenclatureType;
use App\Http\Controllers\Controller;
use App\Models\RiskNomenclature;
use App\Services\MistralNomenclatureAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MistralNomenclatureController extends Controller
{
    public function __construct(
        private readonly MistralNomenclatureAssistant $assistant
    ) {}

    /**
     * POST /m/risk.core/nomenclature/mistral/suggest
     *
     * Body JSON :
     *   type_code : string RC|RF|RS|RO (requis)
     *   sector    : string (requis)
     *   context   : string (optionnel)
     */
    public function suggest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type_code' => ['required', 'string', 'in:RC,RF,RS,RO'],
            'sector'    => ['required', 'string', 'max:150'],
            'context'   => ['nullable', 'string', 'max:500'],
        ]);

        $tenantId  = (int) (session('tenant_id') ?? 1);
        $typeCode  = $validated['type_code'];
        $typeEnum  = NomenclatureType::from($typeCode);

        // Récupérer les nomenclatures L2 déjà existantes pour ce type
        // afin que Mistral ne les reduplique pas
        $existing = RiskNomenclature::where('tenant_id', $tenantId)
            ->where('type_code', $typeCode)
            ->where('level', 2)
            ->pluck('label')
            ->toArray();

        $params = array_merge($validated, [
            'type_label' => $typeEnum->label(),
            'existing'   => $existing,
        ]);

        try {
            $suggestions = $this->assistant->suggest($params);

            return response()->json([
                'suggestions' => $suggestions,
                'type_code'   => $typeCode,
                'type_label'  => $typeEnum->label(),
                'type_color'  => $typeEnum->color(),
                'sector'      => $validated['sector'],
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'hints'   => self::reformulationHints($validated['sector']),
            ], 422);

        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => "Une erreur inattendue s'est produite. Veuillez reessayer.",
                'hints'   => [],
            ], 500);
        }
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private static function reformulationHints(string $sector): array
    {
        $hints = [
            'Precisez le sous-secteur (ex : "agroalimentaire certifie" plutot que "agro")',
            'Ajoutez du contexte : taille de l\'entreprise, region, reglementation',
            'Verifiez que le secteur est redige en francais',
        ];

        if (strlen(trim($sector)) < 8) {
            array_unshift(
                $hints,
                'Le secteur indique est trop court — decrivez-le en 2 a 5 mots'
            );
        }

        return $hints;
    }
}
