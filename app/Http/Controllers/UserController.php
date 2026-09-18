<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest\CreateUserRequest;
use App\Http\Requests\UserRequest\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Spatie\Permission\Models\Role as ModelsRole;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('permission:users index', only: ['index']),
            new Middleware('permission:users create', only: ['create', 'store']),
            new Middleware('permission:users edit', only: ['edit', 'update']),
            new Middleware('permission:users delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $users = User::with('roles')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->paginate(8)
            ->withQueryString()
            // Model User utuh membawa created_at, updated_at, email_verified_at
            // dan two_factor_confirmed_at berupa Carbon — empat stempel waktu
            // mentah per baris yang tidak satu pun dipakai layar ini.
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->map(fn ($role): array => [
                    'id' => $role->id,
                    'name' => $role->name,
                ])->all(),
            ]);

        return inertia('users/index', [
            'users' => $users,
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('users/create', [
            'roles' => $this->daftarRole(),
        ]);
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function daftarRole(): array
    {
        return ModelsRole::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ModelsRole $role): array => ['id' => $role->id, 'name' => $role->name])
            ->all();
    }

    public function store(CreateUserRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'email_verified_at' => now(),
        ]);

        $user->assignRole($request->role);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(string $id)
    {
        $user = User::with('roles')->findOrFail($id);

        return Inertia::render('users/edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->map(fn ($role): array => [
                    'id' => $role->id,
                    'name' => $role->name,
                ])->all(),
            ],
            'roles' => $this->daftarRole(),
        ]);
    }

    public function update(UpdateUserRequest $request, string $id)
    {
        $user = User::findOrFail($id);
        $validated = $request->validated();

        // Kata sandi kosong berarti "biarkan seperti semula"; dibiarkan lewat,
        // string kosong itu akan di-hash dan menjadi kata sandi baru.
        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $user->update(collect($validated)->only(['name', 'email', 'password'])->all());

        $user->syncRoles($validated['role']);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
