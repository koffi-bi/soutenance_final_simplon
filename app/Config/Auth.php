<?php

namespace Config;

use CodeIgniter\Shield\Config\Auth as ShieldAuth;

class Auth extends ShieldAuth
{
    /**
     * Utiliser notre modèle personnalisé.
     */
    public string $userProvider = \App\Models\UserModel::class;

    /**
     * Désactiver l'inscription native pour le moment.
     */
    public bool $allowRegistration = false;
}
