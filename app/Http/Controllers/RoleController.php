<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest\CreateRoleRequest;
use App\Http\Requests\RoleRequest\UpdateRoleRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('permission:roles index', only: ['index']),
            new Middleware('permission:roles create', only: ['create', 'store']),
            new Middleware('permission:roles edit', only: ['edit', 'update']),
            new Middleware('permission:roles delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $roles = Role::with('permissions')->when($request->search, function ($query, $search) {
            $query->where('name', 'like', "%{$search}%");
        })
            ->paginate(8)
            ->withQueryString()
            ->through(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->map(fn (Permission $permission): array => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                ])->all(),
            ]);

        return inertia('roles/index', [
            'roles' => $roles,
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('roles/create', [
            'permissions' => $this->daftarIzin(),
        ]);
    }

    /**
     * Hanya id dan nama yang dikirim ke layar.
     *
     * Model Permission utuh membawa created_at/updated_at berupa Carbon, yang
     * terserialisasi menjadi "2026-03-31T17:00:00.000000Z" dan tidak pernah
     * dipakai halaman mana pun.
     *
     * @return array<int, array{id: int, name: string}>
     */
    private function daftarIzin(): array
    {
        return Permission::query()
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn (Permission $permission): array => [
                'id' => $permission->id,
                'name' => $permission->name,
            ])
            ->all();
    }

    public function store(CreateRoleRequest $request)
    {
        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        // Peran tanpa izin sah; tanpa nilai bawaan ini syncPermissions menerima
        // null saat tidak ada satu pun kotak centang yang dicentang.
        $role->syncPermissions($request->validated('permissions') ?? []);

        return redirect()->route('roles.index')->with('success', 'Role berhasil ditambahkan.');
    }

    public function edit(string $id)
    {
        $role = Role::with('permissions')->findOrFail($id);

        return Inertia::render('roles/edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->all(),
            ],
            'permissions' => $this->daftarIzin(),
        ]);
    }

    public function update(UpdateRoleRequest $request, string $id)
    {
        $role = Role::findOrFail($id);
        $role->update(['name' => $request->validated('name')]);

        $role->syncPermissions($request->validated('permissions') ?? []);

        return redirect()->route('roles.index')->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role berhasil dihapus.');
    }
}
