<?php

/**
 * Class Controller (abstraite)
 * 
 * Classe de base pour tous les contrôleurs
 * Définit l'interface pour traiter les différentes méthodes HTTP
 */
abstract class Controller
{
    /**
     * Traite la requête HTTP et retourne une réponse JSON
     */
    public function jsonResponse(HttpRequest $request): ?string
    {
        $data = null;
        $method = $request->getMethod();

        switch ($method) {
            case "GET":
                $data = $this->processGetRequest($request);
                break;
            case "POST":
                $data = $this->processPostRequest($request);
                break;
            case "PUT":
                $data = $this->processPutRequest($request);
                break;
            case "PATCH":
                $data = $this->processPatchRequest($request);
                break;
            case "DELETE":
                $data = $this->processDeleteRequest($request);
                break;
            default:
                $data = ["error" => "Méthode HTTP non supportée"];
                break;
        }

        // Wrapper la réponse avec {success: boolean, data: ..., error: ...}
        if ($data === null) {
            return null;
        }

        // Si déjà une erreur, retourner comme-is
        if (isset($data['error'])) {
            return json_encode([
                'success' => false,
                'error' => $data['error'],
                'details' => $data['details'] ?? null,
            ]);
        }

        // Sinon, c'est un succès
        return json_encode([
            'success' => true,
            'data' => $data,
        ]);
    }

    protected function processGetRequest(HttpRequest $request)
    {
        return ["error" => "GET non implémenté"];
    }

    protected function processPostRequest(HttpRequest $request)
    {
        return ["error" => "POST non implémenté"];
    }

    protected function processPutRequest(HttpRequest $request)
    {
        return ["error" => "PUT non implémenté"];
    }

    protected function processPatchRequest(HttpRequest $request)
    {
        return ["error" => "PATCH non implémenté"];
    }

    protected function processDeleteRequest(HttpRequest $request)
    {
        return ["error" => "DELETE non implémenté"];
    }
}
