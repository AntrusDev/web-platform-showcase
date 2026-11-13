<?php

/*
 * Policy de las solicitudes de derechos sobre datos personales (ARCOP).
 * Decisión: el panel solo puede ver y gestionar; crear y borrar se niegan
 * explícitamente, porque las solicitudes nacen del formulario público y se
 * conservan como registro de lo atendido. Filament la aplica automáticamente.
 */

namespace App\Policies;

use App\Models\PrivacyRequest;
use App\Models\User;
use Filament\Facades\Filament;

class PrivacyRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isPanelUser($user);
    }

    public function view(User $user, PrivacyRequest $privacyRequest): bool
    {
        return $this->isPanelUser($user);
    }

    /**
     * Las solicitudes solo nacen desde el formulario público.
     */
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, PrivacyRequest $privacyRequest): bool
    {
        return $this->isPanelUser($user);
    }

    /**
     * Se conservan como registro de las solicitudes atendidas.
     */
    public function delete(User $user, PrivacyRequest $privacyRequest): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    private function isPanelUser(User $user): bool
    {
        return $user->canAccessPanel(Filament::getPanel('admin'));
    }
}
