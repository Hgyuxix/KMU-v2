<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelurahan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::with('kelurahan')
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $kelurahans = Kelurahan::orderBy('nama')->get();

        return view('admin.users.create', compact('kelurahans'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],

            'role' => [
                'required',
                Rule::in(['kelurahan', 'kecamatan']),
            ],

            'kelurahan_id' => [
                'nullable',
                'required_if:role,kelurahan',
                'exists:kelurahans,id',
            ],
        ]);

        if ($data['role'] === 'kecamatan') {
            $data['kelurahan_id'] = null;
        }

        $data['is_active'] = true;

        User::create($data);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Akun berhasil dibuat.');
    }

    public function edit(User $user)
    {
        $kelurahans = Kelurahan::orderBy('nama')->get();

        return view(
            'admin.users.edit',
            compact('user', 'kelurahans')
        );
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],

            'role' => [
                'required',
                Rule::in(['kelurahan', 'kecamatan']),
            ],

            'kelurahan_id' => [
                'nullable',
                'required_if:role,kelurahan',
                'exists:kelurahans,id',
            ],

            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        if ($data['role'] === 'kecamatan') {
            $data['kelurahan_id'] = null;
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Akun berhasil diperbarui.');
    }

    public function toggleStatus(User $user)
    {
        abort_if($user->isAdmin(), 403);

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        return back()->with(
            'success',
            $user->is_active
                ? 'Akun diaktifkan.'
                : 'Akun dinonaktifkan.'
        );
    }
}
