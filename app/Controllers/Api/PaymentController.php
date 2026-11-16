<?php


namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Payments\PaymentService;

class PaymentController extends BaseController
{
    /**
     * Créer une tentative de paiement pour l'utilisateur connecté.
     */
    public function testCreate()
    {
        helper('auth');

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

        $data = $this->request->getJSON(true);

        if (!is_array($data)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Données JSON invalides.',
                ]);
        }

        $reservationId = filter_var(
            $data['reservation_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $paymentMode = $data['payment_mode'] ?? '';
        $paymentMethod = $data['payment_method'] ?? '';

        if (
            $reservationId === false
            || $reservationId === null
            || $reservationId <= 0
            || !is_string($paymentMode)
            || !is_string($paymentMethod)
        ) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Paramètres de paiement invalides.',
                ]);
        }

        $paymentService = new PaymentService();

        $result = $paymentService->createPayment(
            (int) $user->id,
            $reservationId,
            $paymentMode,
            $paymentMethod
        );

       $httpStatus = $result['http_status'] ?? 200;
unset($result['http_status']);

return $this->response
    ->setStatusCode($httpStatus)
    ->setJSON($result);
    }

    /**
     * Simuler le résultat d'un paiement.
     */
    public function testProcess()
    {
        helper('auth');

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

        $data = $this->request->getJSON(true);

        if (
            !is_array($data)
            || !isset($data['payment_id'])
            || !array_key_exists('success', $data)
            || !is_bool($data['success'])
        ) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Identifiant du paiement ou résultat invalide.',
                ]);
        }

        $paymentId = filter_var(
            $data['payment_id'],
            FILTER_VALIDATE_INT
        );

        if (
            $paymentId === false
            || $paymentId === null
            || $paymentId <= 0
        ) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Identifiant du paiement invalide.',
                ]);
        }

        $paymentService = new PaymentService();

        $result = $paymentService->processSimulation(
            $paymentId,
            (int) $user->id,
            $data['success']
        );

       $httpStatus = $result['http_status'] ?? 200;
unset($result['http_status']);

return $this->response
    ->setStatusCode($httpStatus)
    ->setJSON($result);
    }
}
