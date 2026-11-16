<?php

namespace App\Controllers;

use App\Models\CompanyModel;
use CodeIgniter\RESTful\ResourceController;

class CompanyController extends ResourceController
{
    protected $format = 'json';

    // GET : afficher toutes les compagnies
    public function index()
    {
        $companyModel = new CompanyModel();

        $companies = $companyModel
            ->orderBy('name', 'ASC')
            ->findAll();

        return $this->respond([
            'status' => 'success',
            'message' => 'Liste des compagnies récupérée.',
            'data' => $companies,
        ], 200);
    }

    // GET : afficher une compagnie par son ID
    public function show($id = null)
    {
        $companyModel = new CompanyModel();

        $company = $companyModel->find($id);

        if (!$company) {
            return $this->failNotFound(
                'Compagnie introuvable.'
            );
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'Compagnie récupérée.',
            'data' => $company,
        ], 200);
    }
}

