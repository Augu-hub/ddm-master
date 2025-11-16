<?php
// app/Http/Controllers/Admin/ServiceController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Domain\Catalog\Actions\RegisterService;
use App\Domain\Catalog\DTOs\ServiceDTO;
// Fallback model si besoin:
use App\Models\Master\Service;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::query()->orderBy('id','desc')->get(['id','code','label','icon']);
        return inertia('admin/services/Index', compact('services')); // ou retourne JSON si tu n'as pas encore la vue
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'code'  => ['required','string','max:60', Rule::unique('services','code')],
            'label' => ['required','string','max:120'],
            'icon'  => ['nullable','string','max:80'],
        ]);

        DB::transaction(function () use ($data) {
            // Si ton action existe
            if (class_exists(RegisterService::class)) {
                app(RegisterService::class)->execute(new ServiceDTO(
                    $data['code'],
                    $data['label'],
                    $data['icon'] ?? 'ti ti-package'
                ));
            } else {
                Service::create([
                    'code'  => $data['code'],
                    'label' => $data['label'],
                    'icon'  => $data['icon'] ?? 'ti ti-package',
                ]);
            }
        });

        return back()->with('success','Service créé.');
    }
}
