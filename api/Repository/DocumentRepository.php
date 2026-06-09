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
                    d.description, d.created_at, u.username, u.email,
                    COUNT(c.id) as comment_count
             FROM documents d
             LEFT JOIN users u ON d.user_id = u.id
             LEFT JOIN doc_version dv ON dv.id_doc = d.id
             LEFT JOIN commentaire c ON c.id_docversion = dv.id
             GROUP BY d.id
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
                    d.description, d.created_at,
                    COUNT(c.id) as comment_count
             FROM documents d
             LEFT JOIN doc_version dv ON dv.id_doc = d.id
             LEFT JOIN commentaire c ON c.id_docversion = dv.id
             WHERE d.user_id = ?
             GROUP BY d.id
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
                    d.description, d.created_at,
                    COUNT(c.id) as comment_count
             FROM documents d
             LEFT JOIN doc_version dv ON dv.id_doc = d.id
             LEFT JOIN commentaire c ON c.id_docversion = dv.id
             WHERE d.id = ?
             GROUP BY d.id",
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
        error_log("[DEBUG] findByIdWithVersions: Récupération du document ID = $id");
        
        // Récupérer le document
        $doc = $this->findById($id);
        if (!$doc) {
            error_log("[DEBUG] findByIdWithVersions: Document introuvable");
            return null;
        }

        error_log("[DEBUG] findByIdWithVersions: Document trouvé = " . json_encode($doc));

        // Récupérer toutes les versions
        $versions = $this->findVersions($id);
        
        error_log("[DEBUG] findByIdWithVersions: Versions trouvées = " . count($versions) . " versions");
        if (!empty($versions)) {
            error_log("[DEBUG] findByIdWithVersions: Première version = " . json_encode($versions[0]));
        }

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

        error_log("[DEBUG] findByIdWithVersions: Document final = " . json_encode(array_keys($doc)));

        return $doc;
    }

    /**
     * Récupère le document associé à un ID de version
     */
    public function findByVersionId(int $versionId): ?array
    {
        error_log("[DEBUG] findByVersionId: Cherche la version ID = $versionId");
        
        // Trouver le document associé à cette version
        $version = $this->executeOne(
            "SELECT id_doc FROM doc_version WHERE id = ?",
            [$versionId]
        );

        error_log("[DEBUG] findByVersionId: Résultat query = " . json_encode($version));

        if (!$version) {
            error_log("[DEBUG] findByVersionId: Version non trouvée");
            return null;
        }

        error_log("[DEBUG] findByVersionId: Document ID trouvé = " . $version['id_doc']);

        // Récupérer le document avec toutes ses versions
        $doc = $this->findByIdWithVersions($version['id_doc']);
        
        error_log("[DEBUG] findByVersionId: Document récupéré = " . json_encode(isset($doc) ? 'OK' : 'NULL'));
        
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
     * Récupère la dernière version d'un document
     */
    public function getLatestVersion(int $docId): ?array
    {
        return $this->executeOne(
            "SELECT id, id_doc, version, url_fichier, created_at FROM doc_version 
             WHERE id_doc = ? 
             ORDER BY version DESC 
             LIMIT 1",
            [$docId]
        );
    }

    /**
     * Récupère un document avec les infos du propriétaire
     */
    public function findByIdWithOwner(int $id): ?array
    {
        $result = $this->executeOne(
            "SELECT d.id, d.user_id, d.nom_fichier, d.titre, d.type_fichier, 
                    d.description, d.created_at, u.username, u.email
             FROM documents d
             LEFT JOIN users u ON d.user_id = u.id
             WHERE d.id = ?",
            [$id]
        );

        if ($result) {
            return $this->formatDocument($result);
        }

        return null;
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
            'comment_count' => (int)($doc['comment_count'] ?? 0),
        ];
    }

    /**
     * Formate plusieurs documents
     */
    private function formatDocuments(array $docs): array
    {
        return array_map([$this, 'formatDocument'], $docs);
    }

    /**
     * Récupère le document associé à une version
     */
    public function findDocByVersionId(int $versionId): ?array
    {
        $result = $this->executeOne(
            "SELECT d.id, d.user_id, d.nom_fichier, d.titre, d.type_fichier, 
                    d.description, d.created_at,
                    COUNT(c.id) as comment_count
             FROM documents d
             INNER JOIN doc_version dv ON dv.id_doc = d.id
             LEFT JOIN commentaire c ON c.id_docversion = dv.id
             WHERE dv.id = ?
             GROUP BY d.id",
            [$versionId]
        );

        if ($result) {
            return $this->formatDocument($result);
        }

        return null;
    }
}
