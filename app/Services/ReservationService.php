<?php

namespace App\Services;

use App\Exceptions\ApiException;
use Config\Database;
use Throwable;
class ReservationService
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Crée une réservation temporaire de 15 minutes.
     */
    public function createTemporaryReservation(
        int $userId,
        int $tripId,
        array $seatIds
    ): array {
        // Éliminer les doublons et convertir les identifiants en entiers.
        $seatIds = array_values(array_unique(array_map('intval', $seatIds)));

        if ($userId <= 0 || $tripId <= 0 || empty($seatIds)) {
            return [
                'status' => 'error',
                 'http_status' => 400,
                'message' => 'Informations de réservation invalides.'
            ];
        }

        if (in_array(0, $seatIds, true) || min($seatIds) < 1) {
            return [
                'status' => 'error',
                'http_status' => 400,
                'message' => 'Identifiant de siège invalide.'
            ];
        }

        try {
            if ($this->db->transBegin() === false) {
                throw new \RuntimeException(
                    'Impossible de démarrer la transaction.'
                );
            }

            // Vérifier l'existence et le statut de l'utilisateur.
            $user = $this->db->query(
               "SELECT id, role, account_status
 FROM users
 WHERE id = ?
         FOR UPDATE",
                [$userId]
            )->getRowArray();

           if (!$user) {
    throw new ApiException('Utilisateur introuvable.', 404);
}

if ($user['account_status'] !== 'active' || $user['role'] !== 'traveler') {
    throw new ApiException('Seuls les voyageurs actifs peuvent réserver.', 403);
}

            /*
     * 1. Vérifier et verrouiller le voyage,
     *    son véhicule et sa compagnie.
     */

            /*
             * 1. Vérifier et verrouiller le voyage,
             *    son véhicule et sa compagnie.
             */
           
/*
 * 1. Vérifier et verrouiller le voyage,
 * son véhicule, sa compagnie, son itinéraire et ses villes.
 */
$trip = $this->db->query(
    "SELECT
        t.id,
        t.company_id,
        t.vehicle_id,
        t.departure_at,
        t.arrival_at,
        t.price,
        t.status AS trip_status,
        (t.departure_at > NOW()) AS is_future,
        (t.arrival_at > t.departure_at) AS valid_schedule,
        (t.price > 0) AS valid_price,
        v.status AS vehicle_status,
        c.status AS company_status,
        r.status AS route_status,
        origin.is_active AS origin_city_active,
        destination.is_active AS destination_city_active
     FROM trips t
     INNER JOIN vehicles v
        ON v.id = t.vehicle_id
        AND v.company_id = t.company_id
     INNER JOIN companies c
        ON c.id = t.company_id
     INNER JOIN routes r
        ON r.id = t.route_id
        AND r.company_id = t.company_id
     INNER JOIN cities origin
        ON origin.id = r.origin_city_id
     INNER JOIN cities destination
        ON destination.id = r.destination_city_id
     WHERE t.id = ?
     FOR UPDATE",
    [$tripId]
)->getRowArray();
if (!$trip) {
    throw new ApiException('Voyage introuvable ou indisponible.', 404);
}

if ($trip['trip_status'] !== 'scheduled') {
    throw new ApiException('Ce voyage n’est pas disponible.', 409);
}

if ((int) $trip['is_future'] !== 1) {
    throw new ApiException('La date de départ de ce voyage est dépassée.', 409);
}

if ((int) $trip['valid_schedule'] !== 1) {
    throw new ApiException('Les horaires de ce voyage sont invalides.', 409);
}

if ((int) $trip['valid_price'] !== 1) {
    throw new ApiException('Le prix de ce voyage est invalide.', 409);
}

if ($trip['vehicle_status'] !== 'active') {
    throw new ApiException('Le véhicule de ce voyage n’est pas disponible.', 409);
}

if ($trip['company_status'] !== 'active') {
    throw new ApiException('Cette compagnie n’est pas autorisée.', 409);
}

if ($trip['route_status'] !== 'active') {
    throw new ApiException('L’itinéraire de ce voyage n’est pas disponible.', 409);
}

if (
    (int) $trip['origin_city_active'] !== 1
    || (int) $trip['destination_city_active'] !== 1
) {
    throw new ApiException('Une des villes de cet itinéraire est inactive.', 409);
}

           

            /*
             * 2. Verrouiller les réservations temporaires
             *    déjà expirées pour ce voyage.
             */
            $expired = $this->db->query(
                "SELECT id
                 FROM reservations
                 WHERE trip_id = ?
                   AND status = 'pending'
                   AND expires_at IS NOT NULL
                   AND expires_at <= NOW()
                 FOR UPDATE",
                [$tripId]
            )->getResultArray();

            if (!empty($expired)) {
                $expiredIds = array_column($expired, 'id');
                $placeholders = implode(
                    ',',
                    array_fill(0, count($expiredIds), '?')
                );

                // Libérer les sièges des réservations expirées.
                $this->db->query(
                    "DELETE FROM reservation_seats
                     WHERE reservation_id IN ($placeholders)",
                    $expiredIds
                );

                // Conserver les réservations dans l'historique.
                $this->db->query(
                    "UPDATE reservations
                     SET status = 'expired', updated_at = NOW()
                     WHERE id IN ($placeholders)",
                    $expiredIds
                );
            }

            /*
             * 3. Vérifier les sièges et leur véhicule.
             */
            $seatPlaceholders = implode(
                ',',
                array_fill(0, count($seatIds), '?')
            );

            $seats = $this->db->query(
                "SELECT id
                 FROM seats
                 WHERE id IN ($seatPlaceholders)
                   AND vehicle_id = ?
                   AND is_active = 1
                 ORDER BY id
                 FOR UPDATE",
                array_merge($seatIds, [$trip['vehicle_id']])
            )->getResultArray();

            if (count($seats) !== count($seatIds)) {
    throw new ApiException(
        'Un ou plusieurs sièges sont invalides ou indisponibles.',
        422
    );
}

            /*
             * 4. Vérifier que les sièges ne sont pas déjà attribués.
             */
            $occupied = $this->db->query(
                "SELECT seat_id
                 FROM reservation_seats
                 WHERE trip_id = ?
                   AND seat_id IN ($seatPlaceholders)",
                array_merge([$tripId], $seatIds)
            )->getResultArray();

            if (!empty($occupied)) {
    throw new ApiException(
        'Un ou plusieurs sièges viennent d’être réservés.',
        409
    );
}

            /*
             * 5. Calculer le montant côté serveur.
             */
            $numberOfSeats = count($seatIds);
            $totalAmount = (float) $trip['price'] * $numberOfSeats;

            $reference = 'RES-' . date('YmdHis') . '-' .
                strtoupper(bin2hex(random_bytes(3)));

            /*
             * 6. Créer la réservation temporaire.
             */
            $this->db->query(
                "INSERT INTO reservations (
                    reference,
                    user_id,
                    trip_id,
                    number_of_seats,
                    total_amount,
                    status,
                    reserved_at,
                    expires_at,
                    created_at,
                    updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, 'pending',
                    NOW(),
                    DATE_ADD(NOW(), INTERVAL 15 MINUTE),
                    NOW(),
                    NOW()
                )",
                [
                    $reference,
                    $userId,
                    $tripId,
                    $numberOfSeats,
                    $totalAmount
                ]
            );

            $reservationId = $this->db->insertID();

            /*
             * 7. Bloquer les sièges pour ce voyage.
             */
            foreach ($seatIds as $seatId) {
                $this->db->query(
                    "INSERT INTO reservation_seats (
                        reservation_id,
                        trip_id,
                        seat_id,
                        created_at
                    ) VALUES (?, ?, ?, NOW())",
                    [$reservationId, $tripId, $seatId]
                );
            }

            /*
             * 8. Valider la transaction.
             */
            if ($this->db->transStatus() === false) {
                throw new \RuntimeException(
                    'Erreur lors de l’enregistrement de la réservation.'
                );
            }

            if ($this->db->transCommit() === false) {
                throw new \RuntimeException(
                    'Impossible de valider la réservation.'
                );
            }

            return [
                'status' => 'success',
                'message' => 'Réservation temporaire créée.',
                'data' => [
                    'reservation_id' => $reservationId,
                    'reference' => $reference,
                    'number_of_seats' => $numberOfSeats,
                    'total_amount' => $totalAmount,
                    'currency' => 'XOF',
                    'expires_in_minutes' => 15
                ]
            ];
        } catch (ApiException $e) {
    $this->db->transRollback();

    return [
        'status' => 'error',
        'http_status' => $e->getHttpStatus(),
        'message' => $e->getMessage()
    ];

} catch (Throwable $e) {
    $this->db->transRollback();

    log_message('error', 'Erreur réservation : ' . $e->getMessage());

    return [
        'status' => 'error',
        'http_status' => 500,
        'message' => 'Une erreur interne est survenue lors de la réservation.'
    ];
}
    }


    /**
     * Récupérer l'historique des réservations d'un utilisateur.
     */
    public function getUserReservations(int $userId): array
    {
        try {
            $reservations = $this->db->query(
                "SELECT
                    r.id AS reservation_id,
                    r.reference,
                    r.number_of_seats,
                    r.total_amount,
                    r.status AS reservation_status,
                    r.reserved_at,
                    r.expires_at,
                    r.cancelled_at,
                    t.departure_at,
                    t.arrival_at,
                    t.price AS trip_price,
                    co.name AS company_name,
                    origin.name AS origin_city,
                    destination.name AS destination_city,

                    (
                        SELECT GROUP_CONCAT(
                            s.seat_number
                            ORDER BY s.seat_number
                            SEPARATOR ', '
                        )
                        FROM reservation_seats rs
                        INNER JOIN seats s ON s.id = rs.seat_id
                        WHERE rs.reservation_id = r.id
                    ) AS seat_numbers,

                    (
                        SELECT p.status
                        FROM payments p
                        WHERE p.reservation_id = r.id
                        ORDER BY p.id DESC
                        LIMIT 1
                    ) AS latest_payment_status,

                    (
                        SELECT p.payment_method
                        FROM payments p
                        WHERE p.reservation_id = r.id
                        ORDER BY p.id DESC
                        LIMIT 1
                    ) AS latest_payment_method

                FROM reservations r
                INNER JOIN trips t ON t.id = r.trip_id
                INNER JOIN routes rt ON rt.id = t.route_id
                INNER JOIN cities origin ON origin.id = rt.origin_city_id
                INNER JOIN cities destination ON destination.id = rt.destination_city_id
                INNER JOIN companies co ON co.id = t.company_id

                WHERE r.user_id = ?

                ORDER BY r.created_at DESC, r.id DESC
                LIMIT 50",
                [$userId]
            )->getResultArray();

            return [
                'status' => 'success',
                'message' => 'Historique des réservations récupéré.',
                'data' => [
                    'reservations' => $reservations,
                    'total' => count($reservations)
                ]
            ];

        } catch (Throwable $e) {
            log_message(
                'error',
                'Erreur historique réservations : ' . $e->getMessage()
            );

            return [
                'status' => 'error',
                'http_status' => 500,
                'message' => 'Impossible de récupérer l’historique des réservations.'
            ];
        }
    }

    /**
 * Récupérer le détail d'une réservation appartenant à un utilisateur.
 */
public function getReservationDetails(int $reservationId, int $userId): array
{
    try {
        $reservation = $this->db->query(
            "SELECT
                r.id AS reservation_id,
                r.reference,
                r.number_of_seats,
                r.total_amount,
                r.status AS reservation_status,
                r.reserved_at,
                r.expires_at,
                r.cancelled_at,
                t.departure_at,
                t.arrival_at,
                t.price AS trip_price,
                co.name AS company_name,
                origin.name AS origin_city,
                destination.name AS destination_city,
                (
                    SELECT GROUP_CONCAT(
                        s.seat_number
                        ORDER BY s.seat_number
                        SEPARATOR ', '
                    )
                    FROM reservation_seats rs
                    INNER JOIN seats s ON s.id = rs.seat_id
                    WHERE rs.reservation_id = r.id
                ) AS seat_numbers,
                (
                    SELECT p.status
                    FROM payments p
                    WHERE p.reservation_id = r.id
                    ORDER BY p.id DESC
                    LIMIT 1
                ) AS latest_payment_status,
                (
                    SELECT p.payment_method
                    FROM payments p
                    WHERE p.reservation_id = r.id
                    ORDER BY p.id DESC
                    LIMIT 1
                ) AS latest_payment_method
            FROM reservations r
            INNER JOIN trips t ON t.id = r.trip_id
            INNER JOIN routes rt ON rt.id = t.route_id
            INNER JOIN cities origin ON origin.id = rt.origin_city_id
            INNER JOIN cities destination ON destination.id = rt.destination_city_id
            INNER JOIN companies co ON co.id = t.company_id
            WHERE r.id = ? AND r.user_id = ?
            LIMIT 1",
            [$reservationId, $userId]
        )->getRowArray();

        if (!$reservation) {
            return [
                'status' => 'error',
                'http_status' => 404,
                'message' => 'Réservation introuvable.'
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Détail de la réservation récupéré.',
            'data' => $reservation
        ];

    } catch (Throwable $e) {
        log_message('error', 'Erreur détail réservation : ' . $e->getMessage());

        return [
            'status' => 'error',
            'http_status' => 500,
            'message' => 'Impossible de récupérer cette réservation.'
        ];
    }
}

/**
 * Annuler une réservation en attente et libérer ses sièges.
 */
public function cancelReservation(int $reservationId, int $userId): array
{
    try {
        $this->db->transBegin();

        $reservation = $this->db->query(
            "SELECT id, status, expires_at
             FROM reservations
             WHERE id = ? AND user_id = ?
             FOR UPDATE",
            [$reservationId, $userId]
        )->getRowArray();

        if (!$reservation) {
            $this->db->transRollback();

            return [
                'status' => 'error',
                'http_status' => 404,
                'message' => 'Réservation introuvable.'
            ];
        }

        if ($reservation['status'] !== 'pending') {
            $this->db->transRollback();

            return [
                'status' => 'error',
                'http_status' => 409,
                'message' => 'Cette réservation ne peut pas être annulée.'
            ];
        }

        if (
            !empty($reservation['expires_at'])
            && strtotime($reservation['expires_at']) <= time()
        ) {
            $this->db->query(
                "UPDATE reservations
                 SET status = 'expired', updated_at = NOW()
                 WHERE id = ?",
                [$reservationId]
            );

            $this->db->query(
                "DELETE FROM reservation_seats
                 WHERE reservation_id = ?",
                [$reservationId]
            );

            $this->db->transCommit();

            return [
                'status' => 'error',
                'http_status' => 409,
                'message' => 'Cette réservation a expiré.'
            ];
        }

        $paidPayment = $this->db->query(
            "SELECT id
             FROM payments
             WHERE reservation_id = ?
               AND status = 'paid'
             LIMIT 1",
            [$reservationId]
        )->getRowArray();

        if ($paidPayment) {
            $this->db->transRollback();

            return [
                'status' => 'error',
                'http_status' => 409,
                'message' => 'Une réservation payée ne peut pas être annulée automatiquement.'
            ];
        }

        $this->db->query(
            "UPDATE reservations
             SET status = 'cancelled',
                 cancelled_at = NOW(),
                 updated_at = NOW()
             WHERE id = ?",
            [$reservationId]
        );

        $this->db->query(
            "DELETE FROM reservation_seats
             WHERE reservation_id = ?",
            [$reservationId]
        );

        if ($this->db->transStatus() === false) {
            $this->db->transRollback();

            return [
                'status' => 'error',
                'http_status' => 500,
                'message' => 'Échec de l’annulation.'
            ];
        }

        $this->db->transCommit();

        return [
            'status' => 'success',
            'message' => 'Réservation annulée avec succès.'
        ];

    } catch (Throwable $e) {
        $this->db->transRollback();

        log_message('error', 'Erreur annulation réservation : ' . $e->getMessage());

        return [
            'status' => 'error',
            'http_status' => 500,
            'message' => 'Impossible d’annuler cette réservation.'
        ];
    }
}

    
}