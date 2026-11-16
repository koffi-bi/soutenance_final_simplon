<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

class AuthGroups extends ShieldAuthGroups
{
    /**
     * Groupe attribué par défaut aux nouveaux utilisateurs.
     */
    public string $defaultGroup = 'traveler';

    /**
     * Groupes disponibles dans l'application.
     */
    public array $groups = [
        'admin' => [
            'title'       => 'Administrateur',
            'description' => 'Administration globale de la plateforme.',
        ],
        'company_admin' => [
            'title'       => 'Administrateur de compagnie',
            'description' => 'Gestion des activités de sa propre compagnie.',
        ],
        'traveler' => [
            'title'       => 'Voyageur',
            'description' => 'Consultation et gestion de ses réservations.',
        ],
    ];

    /**
     * Permissions disponibles.
     */
    public array $permissions = [
        'platform.manage' => 'Gérer toute la plateforme',

        'companies.manage' => 'Gérer les compagnies',
        'company.dashboard' => 'Accéder au tableau de bord de sa compagnie',
        'company.trips.manage' => 'Gérer les trajets de sa compagnie',
        'company.reservations.manage' => 'Gérer les réservations de sa compagnie',

        'reservations.create' => 'Créer une réservation',
        'reservations.view-own' => 'Consulter ses propres réservations',
        'reservations.cancel-own' => 'Annuler ses propres réservations',
    ];

    /**
     * Permissions attribuées aux groupes.
     */
    public array $matrix = [
        'admin' => [
            'platform.manage',
            'companies.manage',
        ],

        'company_admin' => [
            'company.dashboard',
            'company.trips.manage',
            'company.reservations.manage',
        ],

        'traveler' => [
            'reservations.create',
            'reservations.view-own',
            'reservations.cancel-own',
        ],
    ];
}
