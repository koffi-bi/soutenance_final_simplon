<?php

namespace App\Models;

use CodeIgniter\Model;

class TripModel extends Model
{
    protected $table            = 'trips';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;

    /**
     * Retourne les voyages accessibles aux voyageurs.
     *
     * Les places occupées sont celles liées :
     * - à une réservation confirmée ;
     * - à une réservation pending dont le délai n'est pas expiré.
     */
    public function getAvailableTrips(array $filters = []): array
    {
        $sql = "
            SELECT
                t.id,
                t.company_id,
                c.name AS company_name,
                c.logo AS company_logo,
                t.route_id,
                origin.id AS departure_city_id,
                origin.name AS departure_city,
                destination.id AS arrival_city_id,
                destination.name AS arrival_city,
                r.estimated_duration_minutes,
                t.vehicle_id,
                v.label AS vehicle_label,
                v.vehicle_type,
                t.departure_at,
                t.arrival_at,
                t.price,

                (
                    SELECT COUNT(*)
                    FROM seats s
                    WHERE s.vehicle_id = t.vehicle_id
                      AND s.is_active = 1
                      AND NOT EXISTS (
                          SELECT 1
                          FROM reservation_seats rs
                          INNER JOIN reservations res
                              ON res.id = rs.reservation_id
                          WHERE rs.trip_id = t.id
                            AND rs.seat_id = s.id
                            AND (
                                res.status = 'confirmed'
                                OR (
                                    res.status = 'pending'
                                    AND res.expires_at > NOW()
                                )
                            )
                      )
                ) AS available_seats

            FROM trips t

            INNER JOIN companies c
                ON c.id = t.company_id

            INNER JOIN routes r
                ON r.id = t.route_id

            INNER JOIN cities origin
                ON origin.id = r.origin_city_id

            INNER JOIN cities destination
                ON destination.id = r.destination_city_id

            INNER JOIN vehicles v
                ON v.id = t.vehicle_id

            WHERE
                t.status = 'scheduled'
                AND t.departure_at > NOW()
                AND t.arrival_at > t.departure_at
                AND t.price > 0
                
                AND c.status = 'active'
                AND r.status = 'active'
                AND origin.is_active = 1
                AND destination.is_active = 1
                AND v.status = 'active'
                AND t.company_id = r.company_id
                AND t.company_id = v.company_id
        ";

        $bindings = [];

        if (!empty($filters['trip_id'])) {
    $sql .= " AND t.id = ?";
    $bindings[] = (int) $filters['trip_id'];
}

        // Filtre facultatif : ville de départ.
        if (!empty($filters['departure_city_id'])) {
            $sql .= " AND origin.id = ?";
            $bindings[] = (int) $filters['departure_city_id'];
        }

        // Filtre facultatif : ville d'arrivée.
        if (!empty($filters['arrival_city_id'])) {
            $sql .= " AND destination.id = ?";
            $bindings[] = (int) $filters['arrival_city_id'];
        }

        // Filtre facultatif : date de départ.
        if (!empty($filters['departure_date'])) {
            $sql .= " AND DATE(t.departure_at) = ?";
            $bindings[] = $filters['departure_date'];
        }

        $sql .= " ORDER BY t.departure_at ASC";

        return $this->db->query($sql, $bindings)->getResultArray();
    }

    /**
     * Retourne un voyage disponible par son identifiant.
     */
        public function getAvailableTripById(int $id): ?array
    {
        $trips = $this->getAvailableTrips([
            'trip_id' => $id,
        ]);

        return $trips[0] ?? null;
    }

    /**
     * Retourne les sièges actifs d'un voyage avec leur disponibilité.
     */
    public function getTripSeats(int $tripId): array
    {
        $sql = "
            SELECT
                s.id,
                s.seat_number,
                s.seat_type,

                CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM reservation_seats rs
                        INNER JOIN reservations res
                            ON res.id = rs.reservation_id
                        WHERE
                            rs.trip_id = ?
                            AND rs.seat_id = s.id
                            AND (
                                res.status = 'confirmed'
                                OR (
                                    res.status = 'pending'
                                    AND res.expires_at > NOW()
                                )
                            )
                    )
                    THEN 0
                    ELSE 1
                END AS is_available

            FROM seats s
            INNER JOIN trips t
                ON t.vehicle_id = s.vehicle_id

            WHERE
                t.id = ?
                AND s.is_active = 1

            ORDER BY s.seat_number ASC
        ";

        $seats = $this->db->query(
            $sql,
            [$tripId, $tripId]
        )->getResultArray();

        foreach ($seats as &$seat) {
            $seat['id'] = (int) $seat['id'];
            $seat['is_available'] = (bool) $seat['is_available'];
        }

        unset($seat);

        return $seats;
    }
}

