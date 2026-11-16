<?php

namespace App\Services\AI;

use App\Models\TripModel;
use App\Models\CityModel;

class ChatbotDataService
{
    /**
     * Normalise un texte pour faciliter la comparaison des villes.
     * Ignore les majuscules, les accents et les tirets.
     */
    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');

        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

        if ($converted !== false) {
            $text = $converted;
        }

        $text = preg_replace('/[^a-z0-9]+/', ' ', $text);

        return trim($text);
    }

    /**
     * Détecte les filtres de villes à partir du message.
     */
    public function extractCityFilters(string $message): array
    {
        $normalizedMessage = $this->normalize($message);

        $cityModel = new CityModel();

        $cities = $cityModel
            ->where('is_active', 1)
            ->findAll();

        $filters = [];

        foreach ($cities as $departureCity) {
            $departureName = $this->normalize($departureCity['name']);

            foreach ($cities as $arrivalCity) {
                if ($departureCity['id'] === $arrivalCity['id']) {
                    continue;
                }

                $arrivalName = $this->normalize($arrivalCity['name']);

                // Exemple : de Abidjan à Bouaké.
                // Exemple : depuis Abidjan vers Bouaké.
                $pattern = '/\b(?:de|depuis)\s+'
                    . preg_quote($departureName, '/')
                    . '\s+(?:a|vers)\s+'
                    . preg_quote($arrivalName, '/')
                    . '\b/';

                if (preg_match($pattern, $normalizedMessage)) {
                    return [
                        'departure_city_id' => (int) $departureCity['id'],
                        'arrival_city_id' => (int) $arrivalCity['id'],
                    ];
                }
            }
        }

        // Recherche d'une ville de départ seule.
        foreach ($cities as $city) {
            $cityName = $this->normalize($city['name']);

            $departurePattern = '/\b(?:depart(?:ure)?(?:\s+de)?|'
                . 'au\s+depart\s+de|depuis|partir\s+de)\s+'
                . preg_quote($cityName, '/')
                . '\b/';

            if (preg_match($departurePattern, $normalizedMessage)) {
                $filters['departure_city_id'] = (int) $city['id'];
                break;
            }
        }

        // Recherche d'une ville d'arrivée seule.
        foreach ($cities as $city) {
            $cityName = $this->normalize($city['name']);

            $arrivalPattern = '/\b(?:arrivee(?:\s+a)?|'
                . 'destination|vers)\s+'
                . preg_quote($cityName, '/')
                . '\b/';

            if (preg_match($arrivalPattern, $normalizedMessage)) {
                $filters['arrival_city_id'] = (int) $city['id'];
                break;
            }
        }

        return $filters;
    }

    /**
     * Recherche les voyages réellement disponibles.
     */
    public function searchTrips(array $filters = []): array
    {
        $tripModel = new TripModel();

        // Réutilisation du calcul réel des disponibilités.
        $trips = $tripModel->getAvailableTrips($filters);

        $total = count($trips);

        // Limiter les données envoyées à Groq.
        $trips = array_slice($trips, 0, 10);

        return [
            'total' => $total,
            'trips' => array_map(
                static function (array $trip): array {
                    return [
                        'company' => $trip['company_name'],
                        'departure_city' => $trip['departure_city'],
                        'arrival_city' => $trip['arrival_city'],
                        'departure_at' => $trip['departure_at'],
                        'arrival_at' => $trip['arrival_at'],
                        'price_fcfa' => (float) $trip['price'],
                        'available_seats' => (int) $trip['available_seats'],
                    ];
                },
                $trips
            ),
        ];
    }
}
