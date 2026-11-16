<?php


namespace App\Services;

use Config\Database;

class DashboardService
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Statistiques globales de la plateforme.
     */
    public function getAdminStats(): array
    {
        $users = $this->db->query("
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(role = 'admin'), 0) AS admins,
                COALESCE(SUM(role = 'company_admin'), 0) AS company_admins,
                COALESCE(SUM(role = 'traveler'), 0) AS travelers
            FROM users
            WHERE deleted_at IS NULL
        ")->getRowArray();

        $companies = $this->db->query("
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(status = 'active'), 0) AS active,
                COALESCE(SUM(status = 'pending'), 0) AS pending
            FROM companies
        ")->getRowArray();

        $trips = $this->db->query("
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(status = 'scheduled'), 0) AS scheduled
            FROM trips
        ")->getRowArray();

        $reservations = $this->db->query("
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(status = 'confirmed'), 0) AS confirmed,
                COALESCE(SUM(status = 'pending'), 0) AS pending,
                COALESCE(SUM(status = 'cancelled'), 0) AS cancelled,
                COALESCE(SUM(status = 'expired'), 0) AS expired
            FROM reservations
        ")->getRowArray();

        $payments = $this->db->query("
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(status = 'paid'), 0) AS paid,
                COALESCE(SUM(status = 'pending'), 0) AS pending,
                COALESCE(SUM(status = 'failed'), 0) AS failed,
                COALESCE(SUM(status = 'refunded'), 0) AS refunded,
                COALESCE(
                    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END),
                    0
                ) AS revenue
            FROM payments
        ")->getRowArray();

        $tickets = $this->db->query("
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(status = 'valid'), 0) AS valid,
                COALESCE(SUM(status = 'used'), 0) AS used,
                COALESCE(SUM(status = 'cancelled'), 0) AS cancelled
            FROM tickets
        ")->getRowArray();

        return [
            'users' => $users,
            'companies' => $companies,
            'trips' => $trips,
            'reservations' => $reservations,
            'payments' => $payments,
            'tickets' => $tickets,
        ];
    }

    /**
     * Statistiques limitées à une compagnie.
     */
    public function getCompanyStats(int $companyId): array
    {
        $trips = $this->db->query("
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(status = 'scheduled'), 0) AS scheduled
            FROM trips
            WHERE company_id = ?
        ", [$companyId])->getRowArray();

        $reservations = $this->db->query("
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(r.status = 'confirmed'), 0) AS confirmed,
                COALESCE(SUM(r.status = 'pending'), 0) AS pending,
                COALESCE(SUM(r.status = 'cancelled'), 0) AS cancelled,
                COALESCE(SUM(r.status = 'expired'), 0) AS expired
            FROM reservations r
            INNER JOIN trips t ON t.id = r.trip_id
            WHERE t.company_id = ?
        ", [$companyId])->getRowArray();

        $travelers = $this->db->query("
            SELECT COUNT(DISTINCT r.user_id) AS total
            FROM reservations r
            INNER JOIN trips t ON t.id = r.trip_id
            INNER JOIN users u ON u.id = r.user_id
            WHERE t.company_id = ?
              AND u.role = 'traveler'
              AND u.deleted_at IS NULL
        ", [$companyId])->getRowArray();

        $payments = $this->db->query("
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(p.status = 'paid'), 0) AS paid,
                COALESCE(SUM(p.status = 'pending'), 0) AS pending,
                COALESCE(SUM(p.status = 'failed'), 0) AS failed,
                COALESCE(SUM(p.status = 'refunded'), 0) AS refunded,
                COALESCE(
                    SUM(CASE WHEN p.status = 'paid' THEN p.amount ELSE 0 END),
                    0
                ) AS revenue
            FROM payments p
            INNER JOIN reservations r ON r.id = p.reservation_id
            INNER JOIN trips t ON t.id = r.trip_id
            WHERE t.company_id = ?
        ", [$companyId])->getRowArray();

        $tickets = $this->db->query("
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(tk.status = 'valid'), 0) AS valid,
                COALESCE(SUM(tk.status = 'used'), 0) AS used,
                COALESCE(SUM(tk.status = 'cancelled'), 0) AS cancelled
            FROM tickets tk
            INNER JOIN reservations r ON r.id = tk.reservation_id
            INNER JOIN trips t ON t.id = r.trip_id
            WHERE t.company_id = ?
        ", [$companyId])->getRowArray();

        return [
            'company_id' => $companyId,
            'trips' => $trips,
            'reservations' => $reservations,
            'travelers' => $travelers,
            'payments' => $payments,
            'tickets' => $tickets,
        ];
    }
}
