<?php

namespace App\Controllers\Api;

use App\Models\TripModel;
use CodeIgniter\RESTful\ResourceController;

class TripController extends ResourceController
{
    protected $format = 'json';
    /**
     * GET /api/trips
     *
     * Liste les voyages accessibles avec filtres facultatifs.
     *
     * Paramètres :
     * departure_city_id
     * arrival_city_id
     * departure_date (format YYYY-MM-DD)
     */
    public function index()
    {
        $filters = [];

        $departureCityId = $this->request->getGet('departure_city_id');
        $arrivalCityId = $this->request->getGet('arrival_city_id');
        $departureDate = $this->request->getGet('departure_date');

        // Validation de la ville de départ.
        if ($departureCityId !== null && $departureCityId !== '') {
            $id = filter_var($departureCityId, FILTER_VALIDATE_INT);

            if ($id === false || $id < 1) {
                return $this->failValidationErrors([
                    'departure_city_id' => 'Identifiant de ville de départ invalide.',
                ]);
            }

            $filters['departure_city_id'] = $id;
        }

        // Validation de la ville d'arrivée.
        if ($arrivalCityId !== null && $arrivalCityId !== '') {
            $id = filter_var($arrivalCityId, FILTER_VALIDATE_INT);

            if ($id === false || $id < 1) {
                return $this->failValidationErrors([
                    'arrival_city_id' => 'Identifiant de ville d’arrivée invalide.',
                ]);
            }

            $filters['arrival_city_id'] = $id;
        }

        // Validation de la date de départ.
        if ($departureDate !== null && $departureDate !== '') {
            $dateIsValid = false;

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $departureDate)) {
                $dateIsValid = checkdate(
                    (int) substr($departureDate, 5, 2),
                    (int) substr($departureDate, 8, 2),
                    (int) substr($departureDate, 0, 4)
                );
            }

            if (!$dateIsValid) {
                return $this->failValidationErrors([
                    'departure_date' => 'Date invalide. Format attendu : YYYY-MM-DD.',
                ]);
            }

            $filters['departure_date'] = $departureDate;
        }

        $tripModel = new TripModel();
        $trips = $tripModel->getAvailableTrips($filters);

        return $this->respond([
            'status' => 'success',
            'message' => 'Liste des voyages récupérée.',
            'data' => $trips,
            'total' => count($trips),
        ], 200);
    }

    /**
     * GET /api/trips/{id}
     *
     * Affiche un voyage accessible par son identifiant.
     */
    public function show($id = null)
    {
        $tripId = filter_var($id, FILTER_VALIDATE_INT);

        if ($tripId === false || $tripId < 1) {
            return $this->failValidationErrors([
                'id' => 'Identifiant de voyage invalide.',
            ]);
        }

        $tripModel = new TripModel();
        $trip = $tripModel->getAvailableTripById($tripId);

        if (!$trip) {
            return $this->failNotFound(
                'Voyage introuvable ou indisponible.'
            );
        }

               return $this->respond([
            'status' => 'success',
            'message' => 'Voyage récupéré.',
            'data' => $trip,
        ], 200);
    }

    /**
     * GET /api/trips/{id}/seats
     *
     * Retourne les sièges actifs d'un voyage
     * avec leur disponibilité.
     */
    public function seats($id = null)
    {
        $tripId = filter_var($id, FILTER_VALIDATE_INT);

        if ($tripId === false || $tripId < 1) {
            return $this->failValidationErrors([
                'id' => 'Identifiant de voyage invalide.',
            ]);
        }

        $tripModel = new TripModel();

        // Vérifier que le voyage est toujours accessible.
        $trip = $tripModel->getAvailableTripById($tripId);

        if (!$trip) {
            return $this->failNotFound(
                'Voyage introuvable ou indisponible.'
            );
        }

        $seats = $tripModel->getTripSeats($tripId);

        return $this->respond([
            'status' => 'success',
            'message' => 'Liste des sièges récupérée.',
            'data' => $seats,
            'total' => count($seats),
        ], 200);
    }
}

