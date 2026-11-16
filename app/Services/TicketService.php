<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use RuntimeException;

class TicketService
{
    protected BaseConnection $db;

    public function __construct(BaseConnection $db)
    {
        $this->db = $db;
    }

    /**
     * Génère un ticket pour une réservation payée.
     * Cette méthode doit être appelée dans une transaction déjà ouverte.
     */
    public function issueForPaidReservation(
        int $reservationId,
        int $paymentId
    ): array {
        // Vérifier la réservation
        $reservation = $this->db->table('reservations')
            ->where('id', $reservationId)
            ->get()
            ->getRowArray();

        if (!$reservation || $reservation['status'] !== 'confirmed') {
            throw new RuntimeException(
                'La réservation doit être confirmée avant la création du ticket.'
            );
        }

        // Vérifier le paiement
        $payment = $this->db->table('payments')
            ->where('id', $paymentId)
            ->where('reservation_id', $reservationId)
            ->get()
            ->getRowArray();

        if (!$payment || $payment['status'] !== 'paid') {
            throw new RuntimeException(
                'Le paiement doit être réussi avant la création du ticket.'
            );
        }

        // Vérifier si un ticket existe déjà pour cette réservation
        $existingTicket = $this->db->table('tickets')
            ->where('reservation_id', $reservationId)
            ->get()
            ->getRowArray();

        if ($existingTicket) {
            return $existingTicket;
        }

        // Générer un numéro de ticket unique
        $ticketNumber = 'TKT-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(5)));

        $now = date('Y-m-d H:i:s');

        $data = [
            'reservation_id' => $reservationId,
            'payment_id'     => $paymentId,
            'ticket_number'  => $ticketNumber,
            'status'         => 'valid',
            'issued_at'      => $now,
            'created_at'     => $now,
            'updated_at'     => $now,
        ];

        $this->db->table('tickets')->insert($data);

        if ($this->db->affectedRows() !== 1) {
            throw new RuntimeException(
                'Impossible de créer le ticket.'
            );
        }

        return $this->db->table('tickets')
            ->where('reservation_id', $reservationId)
            ->get()
            ->getRowArray();
    }


    /**
     * Récupère les billets appartenant à un voyageur.
     */
    public function getUserTickets(int $userId): array
    {
        $builder = $this->db->table('tickets t');

        $builder->select([
            't.id AS ticket_id',
            't.ticket_number',
            't.status AS ticket_status',
            't.issued_at',
            'r.id AS reservation_id',
            'r.reference AS reservation_reference',
            'r.total_amount',
            'r.number_of_seats',
            'c.name AS company_name',
            'origin.name AS origin_city',
            'destination.name AS destination_city',
            'tr.departure_at',
            'tr.arrival_at',
            'p.status AS payment_status',
            'p.payment_method',
        ]);

        $builder->join('reservations r', 'r.id = t.reservation_id');
        $builder->join('payments p', 'p.id = t.payment_id');
        $builder->join('trips tr', 'tr.id = r.trip_id');
        $builder->join('companies c', 'c.id = tr.company_id');
        $builder->join('routes ro', 'ro.id = tr.route_id');
        $builder->join('cities origin', 'origin.id = ro.origin_city_id');
        $builder->join('cities destination', 'destination.id = ro.destination_city_id');

        // Sécurité : uniquement les billets de cet utilisateur.
        $builder->where('r.user_id', $userId);

        // Seuls les billets associés à un paiement réussi.
        $builder->where('p.status', 'paid');

        $builder->orderBy('t.issued_at', 'DESC');

        return $builder->get()->getResultArray();
    }

}