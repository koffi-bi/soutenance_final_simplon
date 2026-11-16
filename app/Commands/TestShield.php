<?php

namespace App\Commands;

use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TestShield extends BaseCommand
{
    protected $group       = 'Tests';
    protected $name        = 'test:shield';
    protected $description = 'Vérifie la lecture des utilisateurs avec CodeIgniter Shield.';

    public function run(array $params)
    {
        // Par défaut, on teste l'utilisateur ayant l'identifiant 1.
        $userId = isset($params[0]) ? (int) $params[0] : 1;

        if ($userId <= 0) {
            CLI::error('Identifiant utilisateur invalide.');
            return;
        }

        $userModel = model(UserModel::class);

        // Charger l'utilisateur avec ses identités et ses groupes.
        $user = $userModel
            ->withIdentities()
            ->withGroups()
            ->findById($userId);

        if ($user === null) {
            CLI::error("Aucun utilisateur trouvé avec l'identifiant {$userId}.");
            return;
        }

        CLI::write('====================================', 'yellow');
        CLI::write(' TEST D’INTÉGRATION CODEIGNITER SHIELD', 'yellow');
        CLI::write('====================================', 'yellow');

        CLI::write('ID utilisateur : ' . $user->id);
        CLI::write('Identité e-mail chargée : ' . ($user->email !== null ? 'OUI' : 'NON'));
        CLI::write('Rôle métier : ' . ($user->role ?? 'NON DÉFINI'));
        CLI::write('Statut du compte : ' . ($user->account_status ?? 'NON DÉFINI'));
        CLI::write('Compte actif (Shield) : ' . ($user->active ? 'OUI' : 'NON'));

        $groups = $user->getGroups() ?? [];

        CLI::write('Groupes Shield : ' . implode(', ', $groups));

        CLI::write('Appartient au groupe admin : ' . ($user->inGroup('admin') ? 'OUI' : 'NON'));
        CLI::write('Appartient au groupe company_admin : ' . ($user->inGroup('company_admin') ? 'OUI' : 'NON'));
        CLI::write('Appartient au groupe traveler : ' . ($user->inGroup('traveler') ? 'OUI' : 'NON'));

        CLI::write('Compagnie associée : ' . ($user->company_id ?? 'AUCUNE'));

CLI::write('====================================', 'yellow');
CLI::write('VÉRIFICATION DES PERMISSIONS', 'yellow');

$permissions = [
    'platform.manage',
    'companies.manage',
    'company.dashboard',
    'company.trips.manage',
    'company.reservations.manage',
    'reservations.create',
    'reservations.view-own',
    'reservations.cancel-own',
];

foreach ($permissions as $permission) {
    $result = $user->can($permission) ? 'OUI' : 'NON';
    CLI::write($permission . ' : ' . $result);
}

        CLI::write('====================================', 'yellow');
        CLI::write('TEST TERMINÉ', 'green');
    }
}