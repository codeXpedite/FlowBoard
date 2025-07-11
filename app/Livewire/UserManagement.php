<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class UserManagement extends Component
{
    public $users;
    public $roles;

    public function mount()
    {
        // Check permissions
        if (!auth()->user()->can('manage users')) {
            abort(403, 'Bu sayfayı görüntüleme yetkiniz bulunmamaktadır.');
        }

        $this->users = User::with('roles')->get();
        $this->roles = Role::all();
    }

    public function assignRole($userId, $roleName)
    {
        // Check permissions
        if (!auth()->user()->can('assign roles')) {
            session()->flash('error', 'Rol atama yetkiniz bulunmamaktadır.');
            return;
        }

        $user = User::find($userId);
        if ($user) {
            $user->syncRoles([$roleName]);
            $this->users = User::with('roles')->get();
            session()->flash('success', "{$user->name} kullanıcısına {$roleName} rolü atandı.");
        }
    }

    public function removeRole($userId)
    {
        // Check permissions
        if (!auth()->user()->can('assign roles')) {
            session()->flash('error', 'Rol kaldırma yetkiniz bulunmamaktadır.');
            return;
        }

        $user = User::find($userId);
        if ($user) {
            $user->syncRoles([]);
            $this->users = User::with('roles')->get();
            session()->flash('success', "{$user->name} kullanıcısının rolleri kaldırıldı.");
        }
    }

    public function render()
    {
        return view('livewire.user-management')
            ->layout('layouts.app');
    }
}
