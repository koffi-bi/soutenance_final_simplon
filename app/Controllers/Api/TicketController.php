<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use Dompdf\Dompdf;
use Dompdf\Options;

class TicketController extends BaseController
{
    /**
     * Retourne les tickets du voyageur connecté.
     */
    public function index()
    {
        helper('auth');

        $user = auth('tokens')->getAuthenticator()->getUser();

        if ($user === null) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Authentification requise.',
                ]);
        }

        if (! $user->can('reservations.view-own')) {
            return $this->response
                ->setStatusCode(403)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Vous ne disposez pas des droits pour consulter vos tickets.',
                ]);
        }

        $tickets = $this->fetchTickets((int) $user->id);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Tickets récupérés.',
            'data'    => $tickets,
        ]);
    }

    /**
     * Retourne le détail d'un ticket appartenant au voyageur connecté.
     */
    public function show(int $ticketId)
    {
        helper('auth');

        $user = auth('tokens')->getAuthenticator()->getUser();

        if ($user === null) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Authentification requise.',
                ]);
        }

        if (! $user->can('reservations.view-own')) {
            return $this->response
                ->setStatusCode(403)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Vous ne disposez pas des droits pour consulter vos tickets.',
                ]);
        }

        $tickets = $this->fetchTickets(
            (int) $user->id,
            $ticketId
        );

        if (empty($tickets)) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Ticket introuvable.',
                ]);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Ticket récupéré.',
            'data'    => $tickets[0],
        ]);
    }

    /**
 * Télécharge le ticket du voyageur connecté au format PDF.
 */
public function pdf(int $ticketId)
{
    helper('auth');

    $user = auth('tokens')->getAuthenticator()->getUser();

    if ($user === null) {
        return $this->response
            ->setStatusCode(401)
            ->setJSON([
                'status'  => 'error',
                'message' => 'Authentification requise.',
            ]);
    }

    if (! $user->can('reservations.view-own')) {
        return $this->response
            ->setStatusCode(403)
            ->setJSON([
                'status'  => 'error',
                'message' => 'Vous ne disposez pas des droits nécessaires.',
            ]);
    }

    // Vérifier que le ticket appartient au voyageur.
    $tickets = $this->fetchTickets(
        (int) $user->id,
        $ticketId
    );

    if (empty($tickets)) {
        return $this->response
            ->setStatusCode(404)
            ->setJSON([
                'status'  => 'error',
                'message' => 'Ticket introuvable.',
            ]);
    }

    $ticket = $tickets[0];

    // Préparer les sièges.
    $seatLabels = [];

    foreach ($ticket['seats'] as $seat) {
        $seatLabels[] = esc((string) $seat['seat_number'])
            . ' (' . esc((string) $seat['seat_type']) . ')';
    }

    $seats = implode(', ', $seatLabels);

    // Formater les dates et le montant.
    $departure = date(
        'd/m/Y à H:i',
        strtotime($ticket['departure_at'])
    );

    $arrival = date(
        'd/m/Y à H:i',
        strtotime($ticket['arrival_at'])
    );

    $amount = number_format(
        (float) $ticket['total_amount'],
        0,
        ',',
        ' '
    );

    // Construire le document HTML.
    $html = '
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: DejaVu Sans, sans-serif;
                color: #222;
                font-size: 12px;
                margin: 30px;
            }

            .header {
                text-align: center;
                border-bottom: 3px solid #1d4ed8;
                padding-bottom: 15px;
                margin-bottom: 25px;
            }

            h1 {
                color: #1d4ed8;
                font-size: 24px;
            }

            .ticket-number {
                font-size: 16px;
                font-weight: bold;
            }

            .section {
                margin-bottom: 20px;
                padding: 12px;
                border: 1px solid #ddd;
            }

            .section h2 {
                color: #1d4ed8;
                font-size: 15px;
                margin-top: 0;
            }

            .row {
                margin: 9px 0;
            }

            .label {
                font-weight: bold;
            }

            .status {
                color: #15803d;
                font-weight: bold;
            }

            .amount {
                font-size: 18px;
                font-weight: bold;
                color: #15803d;
            }

            .footer {
                margin-top: 30px;
                text-align: center;
                font-size: 10px;
                color: #666;
            }
        </style>
    </head>
    <body>

        <div class="header">
            <h1>CAR RESERVATION</h1>
            <p>VOTRE TICKET DE VOYAGE</p>
            <p class="ticket-number">'
                . esc($ticket['ticket_number']) .
            '</p>
            <p class="status">TICKET VALIDE</p>
        </div>

        <div class="section">
            <h2>Informations du voyageur</h2>

            <div class="row">
                <span class="label">Nom :</span>
                ' . esc($ticket['first_name'] . ' ' . $ticket['last_name']) . '
            </div>

            <div class="row">
                <span class="label">Téléphone :</span>
                ' . esc($ticket['phone'] ?? '') . '
            </div>
        </div>

        <div class="section">
            <h2>Informations du trajet</h2>

            <div class="row">
                <span class="label">Compagnie :</span>
                ' . esc($ticket['company_name']) . '
            </div>

            <div class="row">
                <span class="label">Départ :</span>
                ' . esc($ticket['origin_city']) . '
            </div>

            <div class="row">
                <span class="label">Destination :</span>
                ' . esc($ticket['destination_city']) . '
            </div>

            <div class="row">
                <span class="label">Date et heure de départ :</span>
                ' . esc($departure) . '
            </div>

            <div class="row">
                <span class="label">Date et heure d’arrivée :</span>
                ' . esc($arrival) . '
            </div>

            <div class="row">
                <span class="label">Siège(s) :</span>
                ' . $seats . '
            </div>
        </div>

        <div class="section">
            <h2>Informations de réservation et paiement</h2>

            <div class="row">
                <span class="label">Référence :</span>
                ' . esc($ticket['reservation_reference']) . '
            </div>

            <div class="row">
                <span class="label">Nombre de places :</span>
                ' . esc((string) $ticket['number_of_seats']) . '
            </div>

            <div class="row">
                <span class="label">Mode de paiement :</span>
                ' . esc($ticket['payment_method']) . '
            </div>

            <div class="row">
                <span class="label">Statut du paiement :</span>
                <span class="status">PAYÉ</span>
            </div>

            <div class="row amount">
                Montant payé : ' . esc($amount) . ' FCFA
            </div>
        </div>

        <div class="footer">
            Ticket généré le '
                . esc(date('d/m/Y à H:i', strtotime($ticket['issued_at']))) .
            '<br><br>
            Merci de voyager avec notre compagnie.
            Conservez ce ticket pendant votre voyage.
        </div>

    </body>
    </html>';

    // Générer le PDF.
    $options = new Options();
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('isRemoteEnabled', false);

    $dompdf = new Dompdf($options);

    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return $this->response
        ->setHeader('Content-Type', 'application/pdf')
        ->setHeader(
            'Content-Disposition',
            'attachment; filename="ticket-' . $ticket['ticket_number'] . '.pdf"'
        )
        ->setHeader('Cache-Control', 'no-store')
        ->setBody($dompdf->output());
}

    /**
     * Recherche les tickets et leurs informations associées.
     */
    private function fetchTickets(
        int $userId,
        ?int $ticketId = null
    ): array {
        $builder = db_connect()->table('tickets t');

        $builder->select([
            't.id AS ticket_id',
            't.ticket_number',
            't.status AS ticket_status',
            't.issued_at',
            'r.id AS reservation_id',
            'r.reference AS reservation_reference',
            'r.status AS reservation_status',
            'r.total_amount',
            'r.number_of_seats',
            'u.first_name',
            'u.last_name',
            'u.phone',
            'c.name AS company_name',
            'origin.name AS origin_city',
            'destination.name AS destination_city',
            'tr.departure_at',
            'tr.arrival_at',
            'tr.price AS trip_price',
            'p.id AS payment_id',
            'p.status AS payment_status',
            'p.payment_method',
        ]);

        $builder->join(
            'reservations r',
            'r.id = t.reservation_id'
        );

        $builder->join(
            'payments p',
            'p.id = t.payment_id'
        );

        $builder->join(
            'users u',
            'u.id = r.user_id'
        );

        $builder->join(
            'trips tr',
            'tr.id = r.trip_id'
        );

        $builder->join(
            'companies c',
            'c.id = tr.company_id'
        );

        $builder->join(
            'routes ro',
            'ro.id = tr.route_id'
        );

        $builder->join(
            'cities origin',
            'origin.id = ro.origin_city_id'
        );

        $builder->join(
            'cities destination',
            'destination.id = ro.destination_city_id'
        );

        // Sécurité : uniquement les tickets du voyageur connecté.
        $builder->where('r.user_id', $userId);

        // Un ticket doit être lié à un paiement réussi.
        $builder->where('p.status', 'paid');

        if ($ticketId !== null) {
            $builder->where('t.id', $ticketId);
        }

        $builder->orderBy('t.issued_at', 'DESC');

        $tickets = $builder->get()->getResultArray();

        if (empty($tickets)) {
            return [];
        }

        // Récupérer les sièges de toutes les réservations.
        $reservationIds = array_column(
            $tickets,
            'reservation_id'
        );

        $seatRows = db_connect()
            ->table('reservation_seats rs')
            ->select([
                'rs.reservation_id',
                's.id AS seat_id',
                's.seat_number',
                's.seat_type',
            ])
            ->join('seats s', 's.id = rs.seat_id')
            ->whereIn('rs.reservation_id', $reservationIds)
            ->orderBy('s.seat_number', 'ASC')
            ->get()
            ->getResultArray();

        $seatsByReservation = [];

        foreach ($seatRows as $seat) {
            $reservationId = $seat['reservation_id'];

            $seatsByReservation[$reservationId][] = [
                'seat_id'     => $seat['seat_id'],
                'seat_number' => $seat['seat_number'],
                'seat_type'   => $seat['seat_type'],
            ];
        }

        foreach ($tickets as &$ticket) {
            $ticket['seats'] =
                $seatsByReservation[$ticket['reservation_id']] ?? [];
        }

        unset($ticket);

        return $tickets;
    }
}
