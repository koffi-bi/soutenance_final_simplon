<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\I18n\Time;
use App\Models\UserModel;
use CodeIgniter\Shield\Authentication\Authenticators\Session;
use Throwable;

class AuthController extends BaseController
{
    /**
     * Connexion API : vérification des identifiants
     * et génération d'un Bearer Token.
     */
    public function login()
    {
        $data = $this->request->getJSON(true);

        if (! is_array($data)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Données JSON invalides.',
                ]);
        }

        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($email === '' || $password === '') {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'L’e-mail et le mot de passe sont obligatoires.',
                ]);
        }

        // Vérification du mot de passe sans ouvrir de session PHP.
       helper('auth');

$authenticator = auth('session')->getAuthenticator();

        $result = $authenticator->check([
            'email'    => $email,
            'password' => $password,
        ]);

        if (! $result->isOK()) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Identifiants incorrects.',
                ]);
        }

        $user = $result->extraInfo();

        // Vérification du statut métier de l'utilisateur.
        if ($user->account_status !== 'active' || ! $user->active) {
            return $this->response
                ->setStatusCode(403)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Ce compte est désactivé ou suspendu.',
                ]);
        }

        // Création d'un jeton valable 30 jours.
        $token = $user->generateAccessToken(
            'reservation-cars-api',
            ['*'],
            Time::now()->addDays(30)
        );

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'status'  => 'success',
                'message' => 'Connexion réussie.',
                'data'    => [
                    'token'      => $token->raw_token,
                    'token_type' => 'Bearer',
                    'expires_at' => $token->expires,
                    'user'       => [
                        'id'         => $user->id,
                        'first_name' => $user->first_name,
                        'last_name'  => $user->last_name,
                        'email'      => $user->email,
                        'phone'      => $user->phone,
                        'role'       => $user->role,
                        'company_id' => $user->company_id,
                    ],
                ],
            ]);
    }

    /**
     * Retourne les informations de l'utilisateur connecté.
     */
    public function me()
    {
        helper('auth');

    $authenticator = auth('tokens')->getAuthenticator();
        $user = $authenticator->getUser();

        if ($user === null) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Utilisateur non authentifié.',
                ]);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Profil récupéré.',
            'data'    => [
                'id'         => $user->id,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'email'      => $user->email,
                'phone'      => $user->phone,
                'role'       => $user->role,
                'company_id' => $user->company_id,
            ],
        ]);
    }

    /**
     * Déconnexion : révocation du jeton utilisé.
     */
    public function logout()
    {
         helper('auth');

    $authenticator = auth('tokens')->getAuthenticator();
        $user = $authenticator->getUser();
        $rawToken = $authenticator->getBearerToken();

        if ($user === null || $rawToken === null) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Utilisateur non authentifié.',
                ]);
        }

        // Suppression du jeton dans auth_identities.
        $user->revokeAccessToken($rawToken);

        // Nettoyage de l'authentificateur courant.
        $authenticator->logout();

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Déconnexion réussie.',
        ]);
    }


public function register()
{
    $rawBody = (string) $this->request->getBody();

    $data = json_decode($rawBody, true);
    $jsonError = json_last_error();
    $jsonErrorMessage = json_last_error_msg();

    log_message('error', 'AUTH_REGISTER_DIAG ' . json_encode([
        'content_type'   => $this->request->getHeaderLine('Content-Type'),
        'content_length' => $this->request->getHeaderLine('Content-Length'),
        'body_length'    => strlen($rawBody),
        'prefix_hex'     => bin2hex(substr($rawBody, 0, 8)),
       'json_error' => $jsonErrorMessage,
    ]));

  if ($jsonError !== JSON_ERROR_NONE) {
        return $this->response->setStatusCode(400)->setJSON([
            'status'  => 'error',
            'message' => 'Format JSON invalide.',
        ]);
    }

    if (! is_array($data)) {
        return $this->response->setStatusCode(400)->setJSON([
            'status'  => 'error',
            'message' => 'Données JSON invalides.',
        ]);
    }

    $firstName = trim((string) ($data['first_name'] ?? ''));
    $lastName  = trim((string) ($data['last_name'] ?? ''));
    $email     = strtolower(trim((string) ($data['email'] ?? '')));
    $phone     = trim((string) ($data['phone'] ?? ''));
    $password  = (string) ($data['password'] ?? '');
    $confirm   = (string) ($data['password_confirm'] ?? '');

    // Vérification des champs obligatoires
    if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
        return $this->response->setStatusCode(422)->setJSON([
            'status'  => 'error',
            'message' => 'Les champs prénom, nom, e-mail et mot de passe sont obligatoires.',
        ]);
    }

    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $this->response->setStatusCode(422)->setJSON([
            'status'  => 'error',
            'message' => 'Adresse e-mail invalide.',
        ]);
    }

    if (strlen($password) < 8) {
        return $this->response->setStatusCode(422)->setJSON([
            'status'  => 'error',
            'message' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ]);
    }

    if ($password !== $confirm) {
        return $this->response->setStatusCode(422)->setJSON([
            'status'  => 'error',
            'message' => 'Les mots de passe ne correspondent pas.',
        ]);
    }

    $db = \Config\Database::connect();
    $userModel = new UserModel();

    // Vérifier si l'adresse e-mail existe déjà dans les identités Shield.
    $existingEmail = $db->table('auth_identities')
        ->where('type', Session::ID_TYPE_EMAIL_PASSWORD)
        ->where('secret', $email)
        ->countAllResults();

    if ($existingEmail > 0) {
        return $this->response->setStatusCode(409)->setJSON([
            'status'  => 'error',
            'message' => 'Cette adresse e-mail est déjà utilisée.',
        ]);
    }

    $db->transBegin();

    try {
        // Création d'un utilisateur Shield.
        $user = $userModel->createNewUser([
            'first_name'    => $firstName,
            'last_name'     => $lastName,
            'phone'         => $phone !== '' ? $phone : null,
            'role'          => 'traveler',
            'company_id'    => null,
            'account_status'=> 'active',
            'active'        => true,
        ]);

        $user->setEmail($email);
        $user->setPassword($password);

        // Insertion de l'utilisateur et création de son identité e-mail.
        $userId = $userModel->insert($user);

        if (! $userId) {
            throw new \RuntimeException('Échec de création du compte.');
        }

        // Récupération de l'entité utilisateur créée.
        $user = $userModel->find($userId);

        if ($user === null) {
            throw new \RuntimeException('Utilisateur introuvable après création.');
        }

        // Activation du compte et attribution du groupe par défaut.
        $user->activate();
        $userModel->addToDefaultGroup($user);

        // Création du token d'accès.
        $token = $user->generateAccessToken(
            'reservation-cars-api',
            ['*'],
            \CodeIgniter\I18n\Time::now()->addDays(30)
        );

        if ($db->transStatus() === false) {
            throw new \RuntimeException('Échec de la transaction.');
        }

        $db->transCommit();

        return $this->response->setStatusCode(201)->setJSON([
            'status'  => 'success',
            'message' => 'Inscription réussie.',
            'data'    => [
                'token'      => $token->raw_token,
                'token_type' => 'Bearer',
                'expires_at' => $token->expires,
                'user'       => [
                    'id'         => $user->id,
                    'first_name' => $user->first_name,
                    'last_name'  => $user->last_name,
                    'email'      => $user->email,
                    'phone'      => $user->phone,
                    'role'       => $user->role,
                    'company_id' => $user->company_id,
                ],
            ],
        ]);
    } catch (Throwable $e) {
        $db->transRollback();

        log_message('error', 'Erreur inscription : ' . $e->getMessage());

        return $this->response->setStatusCode(500)->setJSON([
            'status'  => 'error',
            'message' => 'Une erreur est survenue pendant la création du compte.',
        ]);
    }
}

}


