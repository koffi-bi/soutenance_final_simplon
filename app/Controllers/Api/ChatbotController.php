<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\AI\GroqService;
use App\Services\AI\ChatbotDataService;
use App\Services\ReservationService;
use App\Services\TicketService;
use Config\Database;

class ChatbotController extends BaseController
{
    public function message()
    {
        helper('auth');

        // 1. Vérifier l'utilisateur connecté.
        $user = auth('tokens')->getAuthenticator()->getUser();

        if ($user === null) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'error' => 'Authentification requise.',
                ]);
        }

        // 2. Vérifier le message reçu.
        $data = $this->request->getJSON(true);

        if (!is_array($data)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'success' => false,
                    'error' => 'Format de requête invalide.',
                ]);
        }

        $message = trim($data['message'] ?? '');

        if ($message === '') {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'success' => false,
                    'error' => 'Veuillez saisir un message.',
                ]);
        }

        if (mb_strlen($message) > 2000) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'success' => false,
                    'error' => 'Votre message ne doit pas dépasser 2000 caractères.',
                ]);
        }

        // 3. Déterminer si l'utilisateur parle de ses réservations.
        $isTicketRequest = preg_match(
    '/\b(billet|billets|ticket|tickets)\b/ui',
    $message
);
        $isReservationRequest = preg_match(
            '/\b(r[eé]servation|r[eé]servations)\b/ui',
            $message
        );


        if ($isTicketRequest) {

            // Vérifier la permission de consulter ses propres billets.
            if (!$user->can('reservations.view-own')) {
                return $this->response
                    ->setStatusCode(403)
                    ->setJSON([
                        'success' => false,
                        'error' => 'Vous ne disposez pas des droits nécessaires pour consulter vos billets.',
                    ]);
            }

            // Récupérer uniquement les billets de l'utilisateur connecté.
            $ticketService = new TicketService(Database::connect());

            $tickets = $ticketService->getUserTickets((int) $user->id);

            $context = [
                'type' => 'personal_tickets',
                'total' => count($tickets),
                'tickets' => $tickets,
            ];

        } elseif ($isReservationRequest) {

            // Vérifier la permission de consulter ses réservations.
            if (!$user->can('reservations.view-own')) {
                return $this->response
                    ->setStatusCode(403)
                    ->setJSON([
                        'success' => false,
                        'error' => 'Vous ne disposez pas des droits nécessaires pour consulter vos réservations.',
                    ]);
            }

            // Récupérer uniquement les réservations de l'utilisateur connecté.
            $reservationService = new ReservationService();

            $reservationResult = $reservationService->getUserReservations(
                (int) $user->id
            );

            if (($reservationResult['status'] ?? 'error') !== 'success') {
                $httpStatus = (int) ($reservationResult['http_status'] ?? 500);

                return $this->response
                    ->setStatusCode($httpStatus)
                    ->setJSON([
                        'success' => false,
                        'error' => $reservationResult['message']
                            ?? 'Impossible de récupérer vos réservations.',
                    ]);
            }

            $reservationData = $reservationResult['data'] ?? [];

            $context = [
                'type' => 'personal_reservations',
                'total' => $reservationData['total'] ?? 0,
                'reservations' => $reservationData['reservations'] ?? [],
            ];

        } else {

            // Rechercher les voyages disponibles.
            $dataService = new ChatbotDataService();

            $filters = $dataService->extractCityFilters($message);

            $context = $dataService->searchTrips($filters);
        }


        // 5. Envoyer le message et le contexte à Groq.
        $groqService = new GroqService();

        $result = $groqService->sendMessage($message, $context);

        if (!$result['success']) {
            return $this->response
                ->setStatusCode(502)
                ->setJSON($result);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => $result['message'],
        ]);
    }
}
