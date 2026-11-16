<?php

namespace App\Controllers;

use App\Models\CityModel;
use CodeIgniter\RESTful\ResourceController;

class CityController extends ResourceController
{
    protected $format = 'json';

    public function index()
    {
        $cityModel = new CityModel();

        $cities = $cityModel
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->findAll();

        return $this->respond([
            'status' => 'success',
            'message' => 'Liste des villes récupérée.',
            'data' => $cities,
        ], 200);
    }
}
