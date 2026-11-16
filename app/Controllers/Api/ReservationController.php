<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\ReservationService;

class ReservationController extends BaseController
{
    /**
     * Créer une réservation temporaire pour l'utilisateur connecté.
     */
    public function test()
    {
        helper('auth');

        // Récupérer l'utilisateur authentifié par le token Shield.
        $authenticator = auth('tokens')->getAuthenticator();
        $user = $authenticator->getUser();

        if ($user === null) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Authentification requise.',
                ]);
        }

        if (! $user->can('reservations.create')) {
    return $this->response
        ->setStatusCode(403)
        ->setJSON([
            'status'  => 'error',
            'message' => 'Vous ne disposez pas des droits pour créer une réservation.',
        ]);
}

        // Lire les données envoyées en JSON.
        $data = $this->request->getJSON(true);

        if (!is_array($data)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Données JSON invalides.',
                ]);
        }

        // Valider l'identifiant du trajet.
        $tripId = filter_var(
            $data['trip_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        // Valider la liste des sièges.
        $seatIds = $data['seat_ids'] ?? null;

        if (
            $tripId === false
            || $tripId === null
            || $tripId <= 0
            || !is_array($seatIds)
            || empty($seatIds)
        ) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Identifiant du trajet ou liste des sièges invalide.',
                ]);
        }

        $validatedSeatIds = [];

        foreach ($seatIds as $seatId) {
            $validatedId = filter_var(
                $seatId,
                FILTER_VALIDATE_INT
            );

            if (
                $validatedId === false
                || $validatedId === null
                || $validatedId <= 0
            ) {
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON([
                        'status'  => 'error',
                        'message' => 'Un ou plusieurs identifiants de sièges sont invalides.',
                    ]);
            }

            $validatedSeatIds[] = $validatedId;
        }

        // Éviter de transmettre plusieurs fois le même siège.
        $validatedSeatIds = array_values(
            array_unique($validatedSeatIds)
        );

        $reservationService = new ReservationService();

        $result = $reservationService->createTemporaryReservation(
            (int) $user->id,
            $tripId,
            $validatedSeatIds
        );

        $httpStatus = $result['http_status'] ?? 200;

unset($result['http_status']);

return $this->response
    ->setStatusCode($httpStatus)
    ->setJSON($result);
    }


    /**
     * Afficher l'historique des réservations de l'utilisateur connecté.
     */
    public function history()
    {
        helper('auth');

        $authenticator = auth('tokens')->getAuthenticator();
        $user = $authenticator->getUser();

        if ($user === null) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'Authentification requise.'
                ]);
        }

        if (! $user->can('reservations.view-own')) {
    return $this->response
        ->setStatusCode(403)
        ->setJSON([
            'status'  => 'error',
            'message' => 'Vous ne disposez pas des droits pour consulter vos réservations.',
        ]);
}

        $reservationService = new ReservationService();

        $result = $reservationService->getUserReservations(
            (int) $user->id
        );

        $httpStatus = $result['http_status'] ?? 200;
        unset($result['http_status']);

        return $this->response
            ->setStatusCode($httpStatus)
            ->setJSON($result);
    }

    /**
 * Afficher le détail d'une réservation.
 */
public function show(int $reservationId)
{
    helper('auth');

    $authenticator = auth('tokens')->getAuthenticator();
    $user = $authenticator->getUser();

    if ($user === null) {
        return $this->response
            ->setStatusCode(401)
            ->setJSON([
                'status' => 'error',
                'message' => 'Authentification requise.'
            ]);
    }

    if (! $user->can('reservations.view-own')) {
    return $this->response
        ->setStatusCode(403)
        ->setJSON([
            'status'  => 'error',
            'message' => 'Vous ne disposez pas des droits pour consulter vos réservations.',
        ]);
}

    $reservationService = new ReservationService();

    $result = $reservationService->getReservationDetails(
        $reservationId,
        (int) $user->id
    );

    $httpStatus = $result['http_status'] ?? 200;
    unset($result['http_status']);

    return $this->response
        ->setStatusCode($httpStatus)
        ->setJSON($result);
}

/**
 * Annuler une réservation de l'utilisateur connecté.
 */
public function cancel(int $reservationId)
{
    helper('auth');

    $authenticator = auth('tokens')->getAuthenticator();
    $user = $authenticator->getUser();

    if ($user === null) {
        return $this->response
            ->setStatusCode(401)
            ->setJSON([
                'status' => 'error',
                'message' => 'Authentification requise.'
            ]);
    }

    if (! $user->can('reservations.cancel-own')) {
    return $this->response
        ->setStatusCode(403)
        ->setJSON([
            'status'  => 'error',
            'message' => 'Vous ne disposez pas des droits pour annuler une réservation.',
        ]);
}

    $reservationService = new ReservationService();

    $result = $reservationService->cancelReservation(
        $reservationId,
        (int) $user->id
    );

    $httpStatus = $result['http_status'] ?? 200;
    unset($result['http_status']);

    return $this->response
        ->setStatusCode($httpStatus)
        ->setJSON($result);
}

}
