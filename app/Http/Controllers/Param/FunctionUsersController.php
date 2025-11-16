<?php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class FunctionUsersController extends Controller
{
    private static ?string $centralConn = null;

    private function userConn(): string
    {
        if (self::$centralConn) return self::$centralConn;

        $cands = array_values(array_filter([
            config('tenancy.central_connection'),
            config('database.central_connection'),
            'param','central','mysql', config('database.default'),
        ]));

        foreach ($cands as $name) {
            try {
                if ($name && Schema::connection($name)->hasTable('users')) {
                    return self::$centralConn = $name;
                }
            } catch (\Throwable $e) {}
        }
        abort(500, "No central connection with 'users' table found.");
    }

    public function index(Request $r)
    {
        $entities = DB::connection('tenant')->table('entities')->orderBy('name')->get(['id','name']);
        return Inertia::render('dashboards/Param/Functions/Users/index', [
            'entities' => $entities,
            'routes'   => [
                'search' => route('param.projects.functions.users.search'),
                'map'    => route('param.projects.functions.users.map'),
                'set'    => route('param.projects.functions.users.set'),
                'clear'  => route('param.projects.functions.users.clear'),
                'funcs'  => [
                    'current' => route('param.projects.functions.current'),
                ],
            ],
        ]);
    }

    public function search(Request $r)
    {
        $q = trim((string)$r->query('q',''));
        $conn = $this->userConn();

        $rows = DB::connection($conn)->table('users')
            ->when($q !== '', function($w) use ($q){
                $like = "%{$q}%";
                $w->where(function($qq) use ($like){
                    $qq->where('name','like',$like)->orWhere('email','like',$like);
                });
            })
            ->orderBy('name')->limit(50)->get(['id','name','email']);

        return response()->json([
            'options' => $rows->map(fn($u)=>[
                'value'=>(string)$u->id,
                'text' => $u->name.' — '.$u->email,
            ])->values(),
        ]);
    }

    public function map(Request $r)
    {
        $r->validate(['entity_id' => ['required','integer', Rule::exists('tenant.entities','id')]]);
        $tenantRows = DB::connection('tenant')->table('function_assignments')
            ->where('entity_id',(int)$r->integer('entity_id'))
            ->get(['function_id','user_id']);

        $userIds = $tenantRows->pluck('user_id')->filter()->unique();
        $users = collect();
        if ($userIds->isNotEmpty()) {
            $conn = $this->userConn();
            $users = DB::connection($conn)->table('users')
                ->whereIn('id', $userIds)->get(['id','name','email'])->keyBy('id');
        }

        return response()->json([
            'items' => $tenantRows->map(function($r) use ($users){
                $u = $r->user_id ? ($users[$r->user_id] ?? null) : null;
                return [
                    'function_id'=>(int)$r->function_id,
                    'user_id'    => $r->user_id ? (int)$r->user_id : null,
                    'name'       => $u->name  ?? null,
                    'email'      => $u->email ?? null,
                ];
            })->values()
        ]);
    }

    public function set(Request $r)
    {
        $conn = $this->userConn();
        $data = $r->validate([
            'entity_id'   => ['required','integer', Rule::exists('tenant.entities','id')],
            'function_id' => ['required','integer', Rule::exists('tenant.functions','id')],
            'user_id'     => ['nullable','integer', Rule::exists($conn.'.users','id')],
        ]);

        $now = now();

        $rowId = DB::connection('tenant')->table('function_assignments')
            ->where('entity_id',(int)$data['entity_id'])
            ->where('function_id',(int)$data['function_id'])
            ->value('id');

        if (!$rowId) {
            $rowId = DB::connection('tenant')->table('function_assignments')->insertGetId([
                'entity_id'   => (int)$data['entity_id'],
                'function_id' => (int)$data['function_id'],
                'user_id'     => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }

        DB::connection('tenant')->table('function_assignments')->where('id',$rowId)->update([
            'user_id'    => $data['user_id'] ?? null,
            'updated_at' => $now,
        ]);

        return response()->json(['ok'=>true]);
    }

    public function clear(Request $r)
    {
        $r->validate([
            'entity_id'   => ['required','integer', Rule::exists('tenant.entities','id')],
            'function_id' => ['required','integer', Rule::exists('tenant.functions','id')],
        ]);

        DB::connection('tenant')->table('function_assignments')
            ->where('entity_id',(int)$r->integer('entity_id'))
            ->where('function_id',(int)$r->integer('function_id'))
            ->update(['user_id'=>null, 'updated_at'=>now()]);

        return response()->json(['ok'=>true]);
    }
}
