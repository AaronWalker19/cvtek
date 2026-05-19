<?php

/**
 * DocumentRepository
 * Gère les opérations documents en base de données
 */
class DocumentRepository extends Repository
{
    /**
     * Récupère tous les documents (métadonnées uniquement, sans versions)
     */
    public function findAll(): array
    {
        $results = $this->execute(
            "SELECT d.id, d.user_id, d.nom_fichier, d.titre, d.type_fichier, 
                    d.description, d.created_at, u.username, u.email
             FROM documents d
             LEFT JOIN users u ON d.user_id = u.id
             ORDER BY d.created_at DESC"
        );

        return $this->formatDocuments($results ?? []);
    }

    /**
     * Récupère les documents d'un utilisateur (métadonnées uniquement, sans versions)
     */
    public function findByUserId(int $userId): array
    {
        $results = $this->execute(
            "SELECT d.id, d.user_id, d.nom_fichier, d.titre, d.type_fichier, 
                    d.description, d.created_at
             FROM documents d
             WHERE d.user_id = ?
             ORDER BY d.created_at DESC",
            [$userId]
        );

        return $this->formatDocuments($results ?? []);
    }

    /**
     * Récupère un document (métadonnées uniquement, sans versions)
     */
    public function findById(int $id): ?array
    {
        $result = $this->executeOne(
            "SELECT d.id, d.user_id, d.nom_fichier, d.titre, d.type_fichier, 
                    d.description, d.created_at
             FROM documents d
             WHERE d.id = ?",
            [$id]
        );

        if ($result) {
            return $this->formatDocument($result);
        }

        return null;
    }

    /**
     * Récupère un document avec toutes ses versions
     */
    public function findByIdWithVersions(int $id): ?array
    {
        // Récupérer le document
        $doc = $this->findById($id);
        if (!$doc) {
            return null;
        }

        // Récupérer toutes les versions
        $versions = $this->findVersions($id);

        // Ajouter les versions au document
        $doc['availableVersions'] = $versions;

        // Si des versions existent, ajouter l'URL de la dernière version
        // Sinon, laisser url_fichier vide
        if (!empty($versions)) {
            $latestVersion = $versions[0]; // Triée DESC
            $doc['url_fichier'] = $latestVersion['url_fichier'];
            $doc['version'] = $latestVersion['version'];
        } else {
            $doc['url_fichier'] = '';
            $doc['version'] = 0;
        }

        return $doc;
    }

    /**
     * Crée un nouveau document (métadonnées uniquement)
     * Les versions se créent via addVersion()
     */
    public function create(
        int $userId,
        string $nomFichier,
        string $titre,
        string $typeFichier,
        string $description = ''
    ): int {
        $this->executeUpdate(
            "INSERT INTO documents (user_id, nom_fichier, titre, type_fichier, description, created_at) 
             VALUES (?, ?, ?, ?, ?, NOW())",
            [$userId, $nomFichier, $titre, $typeFichier, $description]
        );

        return (int)$this->getLastInsertId();
    }

    /**
     * Ajoute une nouvelle version à un document
     * Toutes les versions (1.0, 2.0, 3.0, etc) se créent via cette méthode
     */
    public function addVersion(int $docId, string $urlFichier): bool
    {
        // Récupérer le numéro de version suivant
        $result = $this->executeOne(
            "SELECT MAX(version) as max_version FROM doc_version WHERE id_doc = ?",
            [$docId]
        );

        $nextVersion = $result && $result['max_version'] ? ((float)$result['max_version'] + 1.0) : 1.0;

        return $this->executeUpdate(
            "INSERT INTO doc_version (id_doc, version, url_fichier, created_at) 
             VALUES (?, ?, ?, NOW())",
            [$docId, $nextVersion, $urlFichier]
        );
    }

    /**
     * Met à jour un document
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = ['titre', 'description'];
        $setClause = [];
        $params = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $setClause[] = "$key = ?";
                $params[] = $value;
            }
        }

        if (empty($setClause)) {
            return false;
        }

        $params[] = $id;

        return $this->executeUpdate(
            "UPDATE documents SET " . implode(', ', $setClause) . " WHERE id = ?",
            $params
        );
    }

    /**
     * Supprime un document (cascade sur les versions)
     */
    public function delete(int $id): bool
    {
        return $this->executeUpdate(
            "DELETE FROM documents WHERE id = ?",
            [$id]
        );
    }

    /**
     * Récupère toutes les versions d'un document
     */
    public function findVersions(int $docId): array
    {
        $versions = $this->execute(
            "SELECT id, id_doc, version, url_fichier, created_at FROM doc_version 
             WHERE id_doc = ? 
             ORDER BY version DESC",
            [$docId]
        );

        return $versions ?? [];
    }

    /**
     * Récupère une version spécifique d'un document
     */
    public function findVersion(int $docId, float $version): ?array
    {
        return $this->executeOne(
            "SELECT id, id_doc, version, url_fichier, created_at FROM doc_version 
             WHERE id_doc = ? AND version = ?",
            [$docId, $version]
        );
    }

    /**
     * Formate un document (conversion types)
     */
    private function formatDocument(array $doc): array
    {
        return [
            'id' => (int)$doc['id'],
            'user_id' => (int)$doc['user_id'],
            'nom_fichier' => $doc['nom_fichier'],
            'titre' => $doc['titre'] ?? '',
            'type_fichier' => $doc['type_fichier'],
            'description' => $doc['description'] ?? '',
            'created_at' => $doc['created_at'],
        ];
    }

    /**
     * Formate plusieurs documents
     */
    private function formatDocuments(array $docs): array
    {
        return array_map([$this, 'formatDocument'], $docs);
    }
}
