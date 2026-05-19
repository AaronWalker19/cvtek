<?php

/**
 * Class HttpRequest
 * 
 * Encapsule toutes les informations utiles sur une requête HTTP
 * Toutes les propriétés sont privées et accessibles via des getters
 */
class HttpRequest
{
    private string $method;           // GET, POST, DELETE, PATCH, PUT
    private string $resource = "none"; // ressource ciblée (auth, documents, upload)
    private string $id = "";          // identifiant de la ressource
    private string $action = "";      // action supplémentaire (ex: "versions")
    private ?array $params = null;    // paramètres de requête
    private ?array $json = null;      // données JSON du body
    private bool $isAuth = false;     // authentification

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->parseUri();
        $this->parseQuery();
        $this->parseJson();
    }

    /**
     * Parse l'URI pour extraire la ressource, l'id et l'action
     * Convention: /api/[resource]/[id]/[action]?params
     */
    private function parseUri(): void
    {
        $uri = $_SERVER['REQUEST_URI'];
        $tmp = explode("?", $uri);
        $tmp = explode("/", $tmp[0]);
        $tmp = array_filter($tmp);

        // Trouver la position de "api"
        $apiIndex = array_search("api", $tmp);
        if ($apiIndex !== false && isset($tmp[$apiIndex + 1])) {
            $this->resource = $tmp[$apiIndex + 1];
            
            if (isset($tmp[$apiIndex + 2])) {
                $this->id = $tmp[$apiIndex + 2];
            }

            if (isset($tmp[$apiIndex + 3])) {
                $this->action = $tmp[$apiIndex + 3];
            }
        }
    }

    /**
     * Parse les paramètres GET et POST
     */
    private function parseQuery(): void
    {
        $this->params = array_merge($_GET, $_POST);
    }

    /**
     * Parse le body JSON
     */
    private function parseJson(): void
    {
        $input = file_get_contents('php://input');
        if ($input) {
            $this->json = json_decode($input, true);
        }
    }

    // ===== Getters =====

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getResource(): string
    {
        return $this->resource;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getParams(): ?array
    {
        return $this->params;
    }

    public function getParam(string $key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }

    public function getJson(): ?array
    {
        return $this->json;
    }

    public function getJsonField(string $key, $default = null)
    {
        return $this->json[$key] ?? $default;
    }

    public function isAuth(): bool
    {
        return $this->isAuth;
    }

    public function setAuth(bool $isAuth): self
    {
        $this->isAuth = $isAuth;
        return $this;
    }
}
