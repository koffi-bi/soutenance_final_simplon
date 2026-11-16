<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\DashboardService;
use Config\Database;
use Throwable;

class DashboardController extends BaseController
{
    /**
     * Tableau de bord global de l'administrateur.
     */
    public function admin()
    {
        helper('auth');

        $user = auth('tokens')->getAuthenticator()->getUser();

        if ($user === null) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'error' => 'Authentification requise.',
                ]);
        }

        if (!$user->inGroup('admin')) {
            return $this->response
                ->setStatusCode(403)
                ->setJSON([
                    'success' => false,
                    'error' => 'Accès réservé à l administrateur.',
                ]);
        }

        try {
            $service = new DashboardService();

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Statistiques globales récupérées.',
                'data' => $service->getAdminStats(),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Erreur dashboard admin : ' . $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'success' => false,
                    'error' => 'Impossible de récupérer les statistiques.',
                ]);
        }
    }

    /**
     * Tableau de bord du responsable de compagnie.
     */
    public function company()
    {
        helper('auth');

        $user = auth('tokens')->getAuthenticator()->getUser();

        if ($user === null) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'error' => 'Authentification requise.',
                ]);
        }

        if (!$user->inGroup('company_admin')) {
            return $this->response
                ->setStatusCode(403)
                ->setJSON([
                    'success' => false,
                    'error' => 'Accès réservé aux responsables de compagnie.',
                ]);
        }

        $companyId = (int) ($user->company_id ?? 0);

        if ($companyId <= 0) {
            return $this->response
                ->setStatusCode(403)
                ->setJSON([
                    'success' => false,
                    'error' => 'Aucune compagnie associée à ce compte.',
                ]);
        }

        try {
            $service = new DashboardService();

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Statistiques de la compagnie récupérées.',
                'data' => $service->getCompanyStats($companyId),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Erreur dashboard compagnie : ' . $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'success' => false,
                    'error' => 'Impossible de récupérer les statistiques.',
                ]);
        }
    }
}