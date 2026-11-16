<?php

namespace App\Services\Payments;

use App\Exceptions\ApiException;
use App\Models\PaymentModel;
use App\Services\TicketService;
use Throwable;

class PaymentService
{
    protected PaymentModel $paymentModel;
    protected $db;

    public function __construct()
    {
        $this->paymentModel = new PaymentModel();
        $this->db = \Config\Database::connect();
    }

/**
 * Créer une nouvelle tentative de paiement.
 */
public function createPayment(
    int $userId,
    int $reservationId,
    string $paymentMode,
    string $paymentMethod,
    ?string $provider = null
): array {
    // Pour cette première version, seule la simulation est autorisée.
    if ($paymentMode !== 'simulation') {
        return [
            'status' => 'error',
             'http_status' => 400,
            'message' => 'Les paiements réels ne sont pas encore disponibles.'
        ];
    }

    // Vérifier le moyen de paiement.
    $allowedMethods = ['mobile_money', 'card', 'cash'];

    if (!in_array($paymentMethod, $allowedMethods, true)) {
        return [
            'status' => 'error',
            'http_status' => 400,
            'message' => 'Moyen de paiement non autorisé.'
        ];
    }

    // La simulation ne nécessite pas de fournisseur réel.
    $provider = null;

    try {
        $this->db->transBegin();

        if (!$this->db->transStatus()) {
            throw new \RuntimeException(
                'Impossible de démarrer la transaction.'
            );
        }

        // 1. Verrouiller la réservation.
        $reservation = $this->db->query(
            "SELECT
                id,
                user_id,
                total_amount,
                status,
                expires_at,
                (expires_at <= NOW()) AS is_expired
             FROM reservations
             WHERE id = ?
             FOR UPDATE",
            [$reservationId]
        )->getRowArray();

        if (!$reservation) {
           throw new ApiException('Réservation introuvable.', 404);
        }

        // 2. Vérifier le propriétaire.
        if ((int) $reservation['user_id'] !== $userId) {
            throw new ApiException(
    'Vous ne pouvez pas payer cette réservation.',
    403
);
        }

        // 3. Vérifier d'abord si un paiement réussi existe déjà.
        // Une réservation payée ne doit jamais être considérée comme expirée.
        $paidPayment = $this->db->table('payments')
            ->where('reservation_id', $reservationId)
            ->where('status', 'paid')
            ->countAllResults();

        if ($paidPayment > 0) {
           throw new ApiException(
    'Cette réservation possède déjà un paiement réussi.',
    409
);
        }

        // 4. Une réservation confirmée ne peut plus recevoir de paiement.
        if ($reservation['status'] !== 'pending'
            && $reservation['status'] !== 'expired') {
            throw new ApiException(
    'Cette réservation ne peut plus être payée. Statut : '
    . $reservation['status'],
    409
);
        }

        // 5. Vérifier l'expiration uniquement si la réservation est pending.
        if (
            $reservation['status'] === 'pending'
            && (int) $reservation['is_expired'] === 1
        ) {
            // Libérer les places.
            $this->db->table('reservation_seats')
                ->where('reservation_id', $reservationId)
                ->delete();

            // Expirer la réservation.
            $this->db->query(
                "UPDATE reservations
                 SET status = 'expired', updated_at = NOW()
                 WHERE id = ? AND status = 'pending'",
                [$reservationId]
            );

            // Expirer les tentatives encore en attente.
            $this->db->query(
                "UPDATE payments
                 SET status = 'expired', updated_at = NOW()
                 WHERE reservation_id = ? AND status = 'pending'",
                [$reservationId]
            );

            if (!$this->db->transStatus()) {
                throw new \RuntimeException(
                    'Erreur pendant le traitement de l’expiration.'
                );
            }

            if (!$this->db->transCommit()) {
                throw new \RuntimeException(
                    'Impossible de valider l’expiration.'
                );
            }

            return [
                'status' => 'error',
                'http_status' => 409,
                'message' => 'Le délai de réservation est dépassé.'
            ];
        }

        // 6. Seules les réservations encore en attente sont payables.
        if ($reservation['status'] !== 'pending') {
           throw new ApiException(
    'Cette réservation est expirée et ne peut plus être payée.',
    409
);
        }

        // 7. Empêcher plusieurs tentatives simultanément en attente.
        $pendingPayment = $this->db->table('payments')
            ->where('reservation_id', $reservationId)
            ->where('status', 'pending')
            ->countAllResults();

        if ($pendingPayment > 0) {
            throw new ApiException(
    'Un paiement est déjà en attente pour cette réservation.',
    409
);
        }

        // 8. Générer une référence unique.
        $transactionReference = 'PAY-SIM-'
            . date('YmdHis')
            . '-'
            . strtoupper(bin2hex(random_bytes(4)));

        // 9. Créer la tentative de paiement.
        $this->db->table('payments')->insert([
            'reservation_id' => $reservationId,
            'transaction_reference' => $transactionReference,
            'payment_mode' => 'simulation',
            'amount' => $reservation['total_amount'],
            'payment_method' => $paymentMethod,
            'provider' => $provider,
            'provider_transaction_id' => null,
            'status' => 'pending',
            'paid_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$this->db->transStatus()) {
            throw new \RuntimeException(
                'Impossible de créer le paiement.'
            );
        }

        $paymentId = $this->db->insertID();

        if (!$paymentId) {
            throw new \RuntimeException(
                'Impossible de récupérer l’identifiant du paiement.'
            );
        }

        // 10. Valider la transaction.
        if (!$this->db->transCommit()) {
            throw new \RuntimeException(
                'Impossible de valider le paiement.'
            );
        }

        return [
            'status' => 'success',
            'message' => 'Tentative de paiement créée.',
            'data' => [
                'payment_id' => $paymentId,
                'transaction_reference' => $transactionReference,
                'reservation_id' => $reservationId,
                'amount' => $reservation['total_amount'],
                'payment_mode' => 'simulation',
                'payment_method' => $paymentMethod,
                'status' => 'pending',
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

    log_message('error', 'Erreur paiement : ' . $e->getMessage());

    return [
        'status' => 'error',
        'http_status' => 500,
        'message' => 'Une erreur interne est survenue lors du paiement.'
    ];
}
}


    /**
     * Traiter le résultat d'un paiement en mode simulation.
     *
     * Attention : le résultat choisi par le client est uniquement destiné
     * à la démonstration. Ce mécanisme ne remplace pas un webhook bancaire.
     */
    public function processSimulation(
        int $paymentId,
        int $userId,
        bool $success
    ): array {
        try {
            $this->db->transBegin();

            if (!$this->db->transStatus()) {
                throw new \RuntimeException(
                    'Impossible de démarrer la transaction.'
                );
            }

            // 1. Lire l'identifiant de réservation associé au paiement.
            $initialPayment = $this->db->table('payments')
                ->select('id, reservation_id')
                ->where('id', $paymentId)
                ->get()
                ->getRowArray();

            if (!$initialPayment) {
    throw new ApiException('Paiement introuvable.', 404);
}

            $reservationId = (int) $initialPayment['reservation_id'];

            // 2. Verrouiller d'abord la réservation.
            // Ce même ordre de verrouillage est utilisé dans createPayment().
            $reservation = $this->db->query(
                "SELECT
                    id,
                    user_id,
                    status,
                    expires_at,
                    (expires_at <= NOW()) AS is_expired
                 FROM reservations
                 WHERE id = ?
                 FOR UPDATE",
                [$reservationId]
            )->getRowArray();

            if (!$reservation) {
    throw new ApiException('Réservation introuvable.', 404);
}

            // 3. Verrouiller ensuite le paiement et vérifier son association.
            $payment = $this->db->query(
                "SELECT
                    id,
                    reservation_id,
                    payment_mode,
                    status AS payment_status
                 FROM payments
                 WHERE id = ? AND reservation_id = ?
                 FOR UPDATE",
                [$paymentId, $reservationId]
            )->getRowArray();

            if (!$payment) {
    throw new ApiException(
        'Paiement introuvable ou associé à une autre réservation.',
        404
    );
}

            // 4. Vérifier le propriétaire.
            if ((int) $reservation['user_id'] !== $userId) {
    throw new ApiException(
        'Vous ne pouvez pas traiter ce paiement.',
        403
    );
}

            // 5. Autoriser uniquement la simulation.
            if ($payment['payment_mode'] !== 'simulation') {
    throw new ApiException(
        'Ce paiement ne peut pas être simulé.',
        409
    );
}
            // 6. Vérifier le statut du paiement.
            if ($payment['payment_status'] !== 'pending') {
    throw new ApiException(
        'Cette tentative de paiement a déjà été traitée.',
        409
    );
}

            // 7. Si la réservation en attente a expiré, libérer les places.
            if (
                $reservation['status'] === 'pending'
                && (int) $reservation['is_expired'] === 1
            ) {
                $this->db->table('reservation_seats')
                    ->where('reservation_id', $reservationId)
                    ->delete();

                $this->db->query(
                    "UPDATE reservations
                     SET status = 'expired', updated_at = NOW()
                     WHERE id = ? AND status = 'pending'",
                    [$reservationId]
                );

                // Expirer toutes les tentatives encore en attente.
                $this->db->query(
                    "UPDATE payments
                     SET status = 'expired', updated_at = NOW()
                     WHERE reservation_id = ? AND status = 'pending'",
                    [$reservationId]
                );

                if (!$this->db->transStatus()) {
                    throw new \RuntimeException(
                        'Erreur pendant le traitement de l’expiration.'
                    );
                }

                if (!$this->db->transCommit()) {
                    throw new \RuntimeException(
                        'Impossible de valider l’expiration.'
                    );
                }

                return [
                    'status' => 'error',
                     'http_status' => 409,
                    'message' => 'Le délai de réservation est dépassé.'
                ];
            }

            // 8. Vérifier que la réservation est toujours en attente.
            if ($reservation['status'] !== 'pending') {
    throw new ApiException(
        'Cette réservation ne peut plus être payée. Statut : '
        . $reservation['status'],
        409
    );
}

            // 9. Simuler un échec.
            if (!$success) {
                $this->db->query(
                    "UPDATE payments
                     SET status = 'failed', updated_at = NOW()
                     WHERE id = ? AND status = 'pending'",
                    [$paymentId]
                );

                if (!$this->db->transStatus()) {
                    throw new \RuntimeException(
                        'Erreur pendant le traitement du paiement.'
                    );
                }

                if (!$this->db->transCommit()) {
                    throw new \RuntimeException(
                        'Impossible de valider le résultat.'
                    );
                }

                return [
                    'status' => 'success',
                    'message' => 'Paiement simulé comme échoué.',
                    'data' => [
                        'payment_id' => $paymentId,
                        'payment_status' => 'failed',
                        'reservation_status' => 'pending'
                    ]
                ];
            }

            // 10. Vérifier qu'aucun autre paiement n'est déjà réussi.
            $existingPaid = $this->db->table('payments')
                ->where('reservation_id', $reservationId)
                ->where('status', 'paid')
                ->countAllResults();

            if ($existingPaid > 0) {
    throw new ApiException(
        'Un paiement réussi existe déjà pour cette réservation.',
        409
    );
}
            // 11. Marquer le paiement comme réussi.
            $this->db->query(
                "UPDATE payments
                 SET status = 'paid',
                     paid_at = NOW(),
                     updated_at = NOW()
                 WHERE id = ? AND status = 'pending'",
                [$paymentId]
            );

            if ($this->db->affectedRows() !== 1) {
                throw new \RuntimeException(
                    'Le paiement n’a pas pu être marqué comme réussi.'
                );
            }

            // 12. Confirmer la réservation.
            $this->db->query(
                "UPDATE reservations
                 SET status = 'confirmed', updated_at = NOW()
                 WHERE id = ? AND status = 'pending'",
                [$reservationId]
            );

            if ($this->db->affectedRows() !== 1) {
                throw new \RuntimeException(
                    'La réservation n’a pas pu être confirmée.'
                );
            }

            // 13. Générer automatiquement le ticket.
$ticketService = new TicketService($this->db);

$ticket = $ticketService->issueForPaidReservation(
    $reservationId,
    $paymentId
);

            // 14. Vérifier puis valider les deux changements ensemble.
            if (!$this->db->transStatus()) {
                throw new \RuntimeException(
                    'Erreur pendant la confirmation.'
                );
            }

            if (!$this->db->transCommit()) {
                throw new \RuntimeException(
                    'Impossible de valider la transaction.'
                );
            }

            return [
    'status' => 'success',
    'message' => 'Paiement réussi, réservation confirmée et ticket généré.',
    'data' => [
        'payment_id' => $paymentId,
        'payment_status' => 'paid',
        'reservation_id' => $reservationId,
        'reservation_status' => 'confirmed',
        'ticket' => [
            'id' => $ticket['id'],
            'ticket_number' => $ticket['ticket_number'],
            'status' => $ticket['status'],
            'issued_at' => $ticket['issued_at'],
        ],
    ],
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

    log_message('error', 'Erreur traitement paiement : ' . $e->getMessage());

    return [
        'status' => 'error',
        'http_status' => 500,
        'message' => 'Une erreur interne est survenue lors du traitement du paiement.'
    ];
}
    }
}
