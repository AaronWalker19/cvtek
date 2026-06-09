/**
 * client.ts - API Client pour l'API PHP REST
 * 
 * Architecture: Communique avec la nouvelle API PHP structurée avec:
 * - Point d'entrée unique: /api/index.php
 * - Controllers: Auth, Documents, Upload
 * - Repositories: Accès base de données
 * 
 * DÉPLOIEMENT:
 * - En production: utilise les chemins relatifs /cvtek/api/
 * - En développement: utilise http://localhost:8000/api/
 */

// ===============================================
// Configuration
// ===============================================

const API_CONFIG = {
    // Routes API (nouveau format)
    ROUTES: {
        // Auth
        AUTH_LOGIN: '/auth/login',
        AUTH_REGISTER: '/auth/register',
        AUTH_USER: '/auth/user',
        AUTH_LOGOUT: '/auth/logout',
        
        // Documents
        DOCUMENTS: '/documents',
        
        // Upload
        UPLOAD: '/upload',
        
        // Comments
        COMMENTS: '/comments',
    },
};

/**
 * Récupère l'URL de base de l'API
 */
function getApiBaseUrl(): string {
    if (process.env.NODE_ENV === 'production') {
        // En production: les APIs sont dans /cvtek/api/
        return '/cvtek/api';
    }

    // En développement: serveur PHP local
    return 'http://localhost:8000/api';
}

const API_BASE_URL = getApiBaseUrl();

// ===============================================
// Gestion du Token JWT
// ===============================================

/**
 * Stocke le token JWT dans localStorage
 */
export function storeToken(token: string): void {
    localStorage.setItem('auth_token', token);
}

/**
 * Récupère le token JWT depuis localStorage
 */
export function getToken(): string | null {
    const token = localStorage.getItem('auth_token');
    return token;
}

/**
 * Supprime le token JWT
 */
export function clearToken(): void {
    localStorage.removeItem('auth_token');
}

// ===============================================
// Client HTTP générique
// ===============================================

export interface ApiOptions extends RequestInit {
    throwOnError?: boolean;
}

export interface ApiResponse<T = any> {
    success: boolean;
    data?: T;
    error?: string;
    details?: any;
}

/**
 * Affiche les logs contenus dans une réponse API dans la console du navigateur
 */
function displayLogsFromResponse(data: any): void {
    if (!data) return;

    let totalLogsFound = 0;

    // Fonction récursive pour trouver tous les logs dans un objet
    const findAndDisplayLogs = (obj: any, path: string = '', depth: number = 0) => {
        if (!obj || typeof obj !== 'object' || depth > 5) return;

        // Vérifier si cet objet a une propriété 'logs'
        if (Array.isArray(obj.logs) && obj.logs.length > 0) {
            totalLogsFound += obj.logs.length;
            const label = path ? `📋 Logs (${path})` : '📋 Logs';
            console.group(label);
            obj.logs.forEach((log: string) => {
                console.log(log);
            });
            console.groupEnd();
        }

        // Parcourir les propriétés de l'objet
        for (const key in obj) {
            if (obj.hasOwnProperty(key) && typeof obj[key] === 'object' && obj[key] !== null && key !== 'logs') {
                const newPath = path ? `${path}.${key}` : key;
                findAndDisplayLogs(obj[key], newPath, depth + 1);
            }
        }
    };

    // Chercher les logs partout dans la réponse
    findAndDisplayLogs(data);
    
    // Debug: afficher un message si aucun log n'a été trouvé
    if (totalLogsFound === 0 && process.env.NODE_ENV === 'development') {
        console.debug('ℹ️ Aucun log trouvé dans la réponse API');
    }
}

/**
 * Fait un appel API
 */
export async function apiCall<T = any>(
    endpoint: string,
    options: ApiOptions = {}
): Promise<ApiResponse<T>> {
    const url = API_BASE_URL + endpoint;
    const throwOnError = options.throwOnError ?? true;
    
    try {
        // Log pour les requêtes DELETE
        if (options.method === 'DELETE') {
            console.log('[DEBUG] apiCall - DELETE request to:', url);
        }

        const headers: Record<string, string> = {
            'Content-Type': 'application/json',
            ...options.headers,
        };

        // Ajouter le token JWT si disponible
        const token = getToken();
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        const response = await fetch(url, {
            ...options,
            headers,
            credentials: 'include',
        });

        // Parser la réponse
        const contentType = response.headers.get('content-type');
        let data: ApiResponse<T>;

        if (contentType?.includes('application/json')) {
            data = await response.json();
        } else {
            const text = await response.text();
            console.error('❌ Non-JSON response:', text.substring(0, 200));
            data = {
                success: false,
                error: 'Invalid server response (not JSON)',
            };
        }

        // Log pour les réponses DELETE
        if (options.method === 'DELETE') {
            console.log('[DEBUG] apiCall - DELETE response status:', response.status);
            console.log('[DEBUG] apiCall - DELETE response data:', data);
        }

        // Afficher les logs s'ils existent dans la réponse
        displayLogsFromResponse(data);

        // Vérifier le statut HTTP
        if (!response.ok) {
            console.error(`❌ API Error (${response.status}):`, data.error);
            if (throwOnError) {
                throw new Error(data.error || `HTTP ${response.status}`);
            }
            return data;
        }

        return data;

    } catch (error) {
        const errorMsg = error instanceof Error ? error.message : String(error);
        console.error(`❌ API Exception:`, errorMsg);
        
        if (throwOnError) {
            throw error;
        }
        
        return {
            success: false,
            error: errorMsg,
        };
    }
}

// ===============================================
// Authentification
// ===============================================

export interface LoginCredentials {
    email: string;      // CHANGÉ: username → email
    password: string;
}

export interface RegisterCredentials {
    username: string;
    email: string;
    password: string;
    role?: 'student' | 'professor' | 'admin';  // CHANGÉ: added 'admin'
}

export interface User {
    id: number;         // CHANGÉ: userId → id
    username: string;
    email: string;
    role: 'student' | 'professor' | 'admin';
    parcour?: string;
    created_at?: string;
}

/**
 * Connexion utilisateur
 * Endpoint: POST /api/auth/login
 */
export async function login(credentials: LoginCredentials): Promise<User> {
    const response = await apiCall<{ user: User; token: string }>(API_CONFIG.ROUTES.AUTH_LOGIN, {
        method: 'POST',
        body: JSON.stringify(credentials),
    });

    if (!response.success || !response.data?.user) {
        throw new Error(response.error || 'Connexion échouée');
    }

    // Stocker le token JWT
    if (response.data.token) {
        storeToken(response.data.token);
    }

    return response.data.user;
}

/**
 * Inscription utilisateur
 * Endpoint: POST /api/auth/register
 */
export async function register(credentials: RegisterCredentials): Promise<User> {
    const response = await apiCall<{ user: User; token: string }>(API_CONFIG.ROUTES.AUTH_REGISTER, {
        method: 'POST',
        body: JSON.stringify(credentials),
    });

    if (!response.success || !response.data?.user) {
        throw new Error(response.error || 'Inscription échouée');
    }

    // Stocker le token JWT
    if (response.data.token) {
        storeToken(response.data.token);
    }

    return response.data.user;
}

/**
 * Récupère le profil de l'utilisateur connecté
 * Endpoint: GET /api/auth/user
 */
export async function getCurrentUser(): Promise<User | null> {
    const response = await apiCall<{ user: User }>(API_CONFIG.ROUTES.AUTH_USER, {
        method: 'GET',
        throwOnError: false,
    });

    if (!response.success) {
        return null;
    }

    return response.data?.user ?? null;
}

/**
 * Déconnexion
 * Endpoint: POST /api/auth/logout
 */
export async function logout(): Promise<void> {
    await apiCall(API_CONFIG.ROUTES.AUTH_LOGOUT, {
        method: 'POST',
        throwOnError: false,
    });
    
    // Supprimer le token
    clearToken();
}

/**
 * Récupère les infos d'un utilisateur par son ID
 * Endpoint: GET /api/auth/{userId}
 */
export async function getUserById(userId: number): Promise<User> {
    const response = await apiCall<{ user: User }>(
        `/auth/${userId}`,
        { method: 'GET' }
    );

    if (!response.success || !response.data?.user) {
        throw new Error(response.error || 'Utilisateur non trouvé');
    }

    return response.data.user;
}

/**
 * Obtient un token de démo pour un utilisateur
 * Endpoint: GET /api/auth/demo-token?user_id=X
 */
export async function getDemoToken(userId: number): Promise<{ token: string; user: User }> {
    const response = await apiCall<{ token: string; user: User }>(
        `/auth/demo-token?user_id=${userId}`,
        { method: 'GET', throwOnError: false }
    );

    if (!response.success || !response.data?.token) {
        throw new Error(response.error || 'Erreur génération token démo');
    }

    // Stocker le token
    storeToken(response.data.token);

    return response.data;
}

/**
 * Initialise tous les utilisateurs démo (mael, professor, admin)
 * Endpoint: GET /api/auth/init-demo
 */
export async function initializeDemoUsers(): Promise<any> {
    try {
        const response = await apiCall<any>(
            `/auth/init-demo`,
            { method: 'GET', throwOnError: false }
        );

        return response.data;
    } catch (error) {
        console.error(`❌ Erreur lors de l'initialisation des utilisateurs démo:`, error);
        return null;
    }
}

/**
 * Initialise l'utilisateur admin (crée l'admin en base si n'existe pas)
 * Endpoint: POST /api/admin/init
 */
export async function initializeAdmin(): Promise<any> {
    try {
        const response = await apiCall<any>(
            `/admin/init`,
            { method: 'POST', throwOnError: false }
        );

        console.log('✅ Admin initialisé:', response);
        return response.data;
    } catch (error) {
        console.error(`⚠️ Erreur lors de l'initialisation admin (non bloquant):`, error);
        return null;
    }
}

// ===============================================
// Documents
// ===============================================

export interface Document {
    id: number;
    user_id: number;
    nom_fichier: string;
    titre?: string;
    type_fichier: string;
    url_fichier: string;
    description?: string;
    version: number;
    created_at: string;
}

export interface DocumentVersion {
    id: number;
    id_doc: number;
    version: number;
    url_fichier: string;
    created_at: string;
}

/**
 * Récupère les documents
 * Endpoint: GET /api/documents ou GET /api/documents?user_id=123
 */
export async function getDocuments(userId?: number): Promise<Document[]> {
    let url = API_CONFIG.ROUTES.DOCUMENTS;
    if (userId) {
        url += `?user_id=${userId}`;
    }

    const response = await apiCall<{
        count: number;
        documents: Document[];
    }>(url, {
        method: 'GET',
    });

    if (!response.success || !response.data?.documents) {
        throw new Error(response.error || 'Erreur lors de la récupération des documents');
    }

    return response.data.documents;
}

/**
 * Récupère un document spécifique
 * Endpoint: GET /api/documents/{id}
 */
export async function getDocument(id: number): Promise<Document> {
    const response = await apiCall<{ document: Document }>(
        `${API_CONFIG.ROUTES.DOCUMENTS}/${id}`,
        { method: 'GET' }
    );

    if (!response.success || !response.data?.document) {
        throw new Error(response.error || 'Erreur lors de la récupération du document');
    }

    return response.data.document;
}

/**
 * Récupère le document en fonction de l'ID de version
 * Endpoint: GET /api/documents/version/{versionId}
 */
export async function getDocumentByVersionId(versionId: number): Promise<Document> {
    const response = await apiCall<{ document: Document }>(
        `${API_CONFIG.ROUTES.DOCUMENTS}/version/${versionId}`,
        { method: 'GET' }
    );

    if (!response.success || !response.data?.document) {
        throw new Error(response.error || 'Erreur lors de la récupération du document');
    }

    return response.data.document;
}

/**
 * Fonction de secours: trouve le document en cherchant par l'ID de version
 * Cherche d'abord dans tous les documents (pour admins/profs)
 * Puis dans les documents spécifiques de l'utilisateur
 */
export async function findDocumentByVersionIdFallback(versionId: number, userId?: number): Promise<Document> {
    try {
        // Essayer d'abord avec TOUS les documents (pour admins/profs)
        let documents: Document[] = [];
        try {
            const response = await apiCall<{ documents: Document[] }>(
                API_CONFIG.ROUTES.DOCUMENTS,
                { method: 'GET' }
            );
            if (response.success && response.data?.documents) {
                documents = response.data.documents;
            }
        } catch (err) {
            // Si on n'a pas accès à tous les documents, chercher juste nos documents
            if (userId) {
                documents = await getDocuments(userId);
            }
        }
        
        if (documents.length === 0) {
            throw new Error(`Aucun document trouvé pour chercher la version ${versionId}`);
        }
        
        // Pour chaque document, chercher les versions
        for (const doc of documents) {
            try {
                const versions = await getVersions(doc.id);
                const foundVersion = versions.find(v => v.id === versionId);
                if (foundVersion) {
                    return doc;
                }
            } catch (err) {
                // Continuer avec le document suivant
                continue;
            }
        }
        
        throw new Error(`Version ${versionId} non trouvée`);
    } catch (err) {
        throw new Error(`Erreur lors de la recherche du document: ${err instanceof Error ? err.message : 'Erreur inconnue'}`);
    }
}

/**
 * Crée un nouveau document
 * Endpoint: POST /api/documents
 */
export async function createDocument(data: {
    user_id?: number;
    nom_fichier: string;
    titre?: string;
    type_fichier: string;
    url_fichier: string;
    description?: string;
    version?: number;
    parent_document_id?: number;
}): Promise<{ 
    message: string; 
    id: number;
    email_sent?: boolean;
    recipients_count?: number;
    recipient_emails?: Array<{ email: string; name: string }>;
    email_error?: string;
}> {
    const response = await apiCall<{ 
        message: string; 
        id: number;
        email_sent?: boolean;
        recipients_count?: number;
        recipient_emails?: Array<{ email: string; name: string }>;
        email_error?: string;
    }>(
        API_CONFIG.ROUTES.DOCUMENTS,
        {
            method: 'POST',
            body: JSON.stringify(data),
        }
    );

    if (!response.success || !response.data) {
        throw new Error(response.error || 'Erreur lors de la création du document');
    }

    // 📧 Afficher les informations d'email de notification
    if (response.data?.email_sent && response.data?.recipient_emails?.length) {
        console.log(`📧 Emails de notification envoyés!`);
        response.data.recipient_emails.forEach((recipient, index) => {
            console.log(`   📮 ${index + 1}. ${recipient.name} <${recipient.email}>`);
        });
    } else if (response.data?.email_sent === false && response.data?.recipients_count === 0) {
        console.log(`ℹ️ Aucun professeur abonné - pas d'email à envoyer`);
    } else if (response.data?.email_error) {
        console.warn(`⚠️ Erreur lors de l'envoi des emails: ${response.data.email_error}`);
    }

    return response.data;
}

/**
 * Met à jour un document
 * Endpoint: PUT /api/documents/{id}
 */
export async function updateDocument(
    id: number,
    data: { titre?: string; description?: string; version?: number; url_fichier?: string }
): Promise<{ message: string }> {
    const response = await apiCall<{ message: string }>(
        `${API_CONFIG.ROUTES.DOCUMENTS}/${id}`,
        {
            method: 'PUT',
            body: JSON.stringify(data),
        }
    );

    if (!response.success || !response.data) {
        throw new Error(response.error || 'Erreur lors de la mise à jour');
    }

    return response.data;
}

/**
 * Supprime un document
 * Endpoint: DELETE /api/documents/{id}
 */
export async function deleteDocument(id: number): Promise<void> {
    const response = await apiCall(
        `${API_CONFIG.ROUTES.DOCUMENTS}/${id}`,
        { method: 'DELETE' }
    );

    if (!response.success) {
        throw new Error(response.error || 'Erreur lors de la suppression');
    }
}

/**
 * Récupère toutes les versions d'un document
 * Endpoint: GET /api/documents/{id}/versions
 */
export async function getVersions(docId: number): Promise<DocumentVersion[]> {
    const response = await apiCall<{
        count: number;
        versions: DocumentVersion[];
    }>(
        `${API_CONFIG.ROUTES.DOCUMENTS}/${docId}/versions`,
        { method: 'GET' }
    );

    if (!response.success || !response.data?.versions) {
        return [];
    }

    return response.data.versions;
}

/**
 * Ajoute une nouvelle version à un document
 * Endpoint: PUT /api/documents/{id}/version
 */
export async function addVersion(
    docId: number,
    urlFichier: string
): Promise<{ message: string }> {
    const response = await apiCall<{ 
        message: string;
        email_sent?: boolean;
        recipients_count?: number;
        email_error?: string;
    }>(
        `${API_CONFIG.ROUTES.DOCUMENTS}/${docId}/version`,
        {
            method: 'PUT',
            body: JSON.stringify({ url_fichier: urlFichier }),
        }
    );

    if (!response.success || !response.data) {
        throw new Error(response.error || 'Erreur lors de la création de la version');
    }

    return response.data;
}

// ===============================================
// Upload
// ===============================================

export interface UploadResponse {
    message: string;
    file: string;           // Nom unique du fichier
    original_name: string;
    size: number;
    url: string;
    type: string;
}

/**
 * Upload un fichier
 * Endpoint: POST /api/upload (multipart/form-data)
 */
export async function uploadFile(file: File, userId?: number): Promise<UploadResponse> {
    const formData = new FormData();
    formData.append('file', file);
    if (userId) {
        formData.append('user_id', userId.toString());
    }

    const url = API_BASE_URL + API_CONFIG.ROUTES.UPLOAD;
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'include',
        });

        const data = await response.json() as ApiResponse<UploadResponse>;

        if (!response.ok || !data.success) {
            console.error('❌ Upload failed:', data.error);
            throw new Error(data.error || `Upload failed with status ${response.status}`);
        }

        return data.data!;

    } catch (error) {
        const errorMsg = error instanceof Error ? error.message : String(error);
        console.error(`❌ Upload exception:`, errorMsg);
        throw error;
    }
}

// ===============================================
// Commentaires
// ===============================================

export interface Comment {
    id: number;
    id_user: number;
    id_docversion: number;
    text: string;
    date: string;
    username: string;
    email?: string;
}

/**
 * Récupère les commentaires d'une version de document
 * Endpoint: GET /api/comments?doc_version_id={id}
 */
export async function getCommentsByDocVersion(docVersionId: number): Promise<Comment[]> {
    const response = await apiCall<{
        count: number;
        comments: Comment[];
    }>(
        `${API_CONFIG.ROUTES.COMMENTS}?doc_version_id=${docVersionId}`,
        { method: 'GET' }
    );

    if (!response.success || !response.data?.comments) {
        return [];
    }

    return response.data.comments;
}

/**
 * Récupère tous les commentaires d'un utilisateur
 * Endpoint: GET /api/comments?user_id={id}
 */
export async function getCommentsByUserId(userId: number): Promise<Comment[]> {
    const response = await apiCall<{
        count: number;
        comments: Comment[];
    }>(
        `${API_CONFIG.ROUTES.COMMENTS}?user_id=${userId}`,
        { method: 'GET' }
    );

    if (!response.success || !response.data?.comments) {
        return [];
    }

    return response.data.comments;
}

/**
 * Ajoute un commentaire à une version de document
 * Endpoint: POST /api/comments
 */
export async function addComment(docVersionId: number, text: string): Promise<Comment> {
    const requestBody = {
        id_docversion: docVersionId,
        text: text,
    };
    
    const response = await apiCall<{
        comment: Comment;
        email_sent?: boolean;
        email_recipient?: string;
        email_recipient_name?: string;
        email_sender_name?: string;
    }>(
        API_CONFIG.ROUTES.COMMENTS,
        {
            method: 'POST',
            body: JSON.stringify(requestBody),
        }
    );

    if (!response.success || !response.data?.comment) {
        console.error(`❌ [addComment] Erreur - success: ${response.success}, data: ${response.data}`);
        throw new Error(response.error || 'Erreur lors de la création du commentaire');
    }

    // 📧 Afficher les informations d'email de notification
    if (response.data?.email_sent) {
        console.log(`📧 Email de notification envoyé avec succès`);
        console.log(`   📮 Destinataire: ${response.data.email_recipient_name} <${response.data.email_recipient}>`);
        console.log(`   👨‍🏫 Professeur: ${response.data.email_sender_name}`);
    } else if (response.data?.email_sent === false) {
        console.warn(`⚠️ Email de notification non envoyé`);
        console.log(`   📮 Destinataire prévu: ${response.data.email_recipient_name} <${response.data.email_recipient}>`);
    }

    return response.data.comment;
}

/**
 * Met à jour un commentaire
 * Endpoint: PUT /api/comments/{id}
 */
export async function updateComment(commentId: number, text: string): Promise<Comment> {
    const response = await apiCall<{
        comment: Comment;
    }>(
        `${API_CONFIG.ROUTES.COMMENTS}/${commentId}`,
        {
            method: 'PUT',
            body: JSON.stringify({ text }),
        }
    );

    if (!response.success || !response.data?.comment) {
        throw new Error(response.error || 'Erreur lors de la mise à jour du commentaire');
    }

    return response.data.comment;
}

/**
 * Supprime un commentaire
 * Endpoint: DELETE /api/comments/{id}
 */
export async function deleteComment(commentId: number): Promise<void> {
    const response = await apiCall(
        `${API_CONFIG.ROUTES.COMMENTS}/${commentId}`,
        { method: 'DELETE' }
    );

    if (!response.success) {
        throw new Error(response.error || 'Erreur lors de la suppression du commentaire');
    }
}

// ===============================================
// Abonnements (Professeur suivant un étudiant)
// ===============================================

export interface Subscription {
    id: number;
    id_prof: number;
    id_user: number;
    created_at: string;
    username?: string;
    email?: string;
    parcour?: string;
}

/**
 * Vérifie si un professeur suit un étudiant
 * Endpoint: GET /api/abonnement/check?prof_id={profId}&user_id={userId}
 */
export async function checkSubscription(profId: number, userId: number): Promise<boolean> {
    const response = await apiCall<{ subscribed: boolean }>(
        `/abonnement/check?prof_id=${profId}&user_id=${userId}`,
        { method: 'GET', throwOnError: false }
    );

    return response.success && response.data?.subscribed ? true : false;
}

/**
 * Récupère tous les abonnements d'un professeur
 * Endpoint: GET /api/abonnement?prof_id={profId}
 */
export async function getSubscriptionsByProf(profId: number): Promise<Subscription[]> {
    const response = await apiCall<{ subscriptions: Subscription[] }>(
        `/abonnement?prof_id=${profId}`,
        { method: 'GET', throwOnError: false }
    );

    return response.success && response.data?.subscriptions ? response.data.subscriptions : [];
}

/**
 * Récupère tous les abonnements d'un étudiant
 * Endpoint: GET /api/abonnement?user_id={userId}
 */
export async function getSubscriptionsByUser(userId: number): Promise<Subscription[]> {
    const response = await apiCall<{ subscriptions: Subscription[] }>(
        `/abonnement?user_id=${userId}`,
        { method: 'GET', throwOnError: false }
    );

    return response.success && response.data?.subscriptions ? response.data.subscriptions : [];
}

/**
 * Crée un abonnement (professeur suit un étudiant)
 * Endpoint: POST /api/abonnement
 */
export async function createSubscription(profId: number, userId: number): Promise<Subscription> {
    const response = await apiCall<Subscription>(
        '/abonnement',
        {
            method: 'POST',
            body: JSON.stringify({ prof_id: profId, user_id: userId }),
        }
    );

    if (!response.success) {
        throw new Error(response.error || 'Erreur lors de la création de l\'abonnement');
    }

    return response.data as Subscription;
}

/**
 * Supprime un abonnement
 * Endpoint: DELETE /api/abonnement?prof_id={profId}&user_id={userId}
 */
export async function deleteSubscription(profId: number, userId: number): Promise<void> {
    const response = await apiCall(
        `/abonnement?prof_id=${profId}&user_id=${userId}`,
        { method: 'DELETE' }
    );

    if (!response.success) {
        throw new Error(response.error || 'Erreur lors de la suppression de l\'abonnement');
    }
}

// ===============================================
// Administration
// ===============================================

export interface Professor extends User {
    comment_count?: number;
}

/**
 * Récupère la liste de tous les professeurs avec le nombre de commentaires
 * Endpoint: GET /api/admin/professors
 */
export async function getProfessors(): Promise<Professor[]> {
    const response = await apiCall<{ professors: Professor[] }>(
        '/admin/professors',
        { method: 'GET' }
    );

    if (!response.success) {
        throw new Error(response.error || 'Erreur lors de la récupération des professeurs');
    }

    return response.data?.professors ?? [];
}

/**
 * Récupère les détails d'un professeur avec ses commentaires
 * Endpoint: GET /api/admin/professors/{professorId}
 */
export async function getProfessor(professorId: number): Promise<{
    professor: Professor;
    comments: Comment[];
    comment_count: number;
}> {
    const response = await apiCall<{
        professor: Professor;
        comments: Comment[];
        comment_count: number;
    }>(
        `/admin/professors/${professorId}`,
        { method: 'GET' }
    );

    if (!response.success) {
        throw new Error(response.error || 'Erreur lors de la récupération du professeur');
    }

    return response.data as any;
}

/**
 * Crée un nouveau professeur avec email uniquement
 * Endpoint: POST /api/admin/professors
 */
export async function createProfessor(email: string): Promise<Professor> {
    const response = await apiCall<{ professor: Professor }>(
        '/admin/professors',
        {
            method: 'POST',
            body: JSON.stringify({ email }),
        }
    );

    if (!response.success) {
        const errorMsg = response.error || 'Erreur lors de la création du professeur';
        console.error('❌ createProfessor failed:', errorMsg);
        throw new Error(errorMsg);
    }

    return response.data?.professor as Professor;
}

/**
 * Supprime un professeur
 * Endpoint: DELETE /api/admin/professors/{professorId}
 */
export async function deleteProfessor(professorId: number): Promise<void> {
    console.log('[DEBUG] deleteProfessor called with ID:', professorId, 'type:', typeof professorId);
    
    const url = `/admin/professors/${professorId}`;
    console.log('[DEBUG] Sending DELETE request to:', url);
    
    const response = await apiCall(
        url,
        { method: 'DELETE' }
    );

    console.log('[DEBUG] Response from server:', response);
    console.log('[DEBUG] Response.success:', response.success, 'Response.error:', response.error);

    if (!response.success) {
        const errorMsg = response.error || 'Erreur lors de la suppression du professeur';
        console.error('[ERROR] deleteProfessor failed with error:', errorMsg);
        console.error('[ERROR] Full response:', response);
        throw new Error(errorMsg);
    }
}

// ===============================================
// Authentification SSO Unilim
// ===============================================

/**
 * Obtient l'URL de redirection vers Unilim
 * Endpoint: GET /api/auth/unilim-authorize
 */
export async function getUnilimAuthorizeUrl(): Promise<{ authorize_url: string; state: string }> {
    const response = await apiCall<{ authorize_url: string; state: string }>(
        `/auth/unilim-authorize`,
        { method: 'GET', throwOnError: true }
    );

    if (!response.success || !response.data?.authorize_url) {
        throw new Error(response.error || 'Erreur obtention URL Unilim');
    }

    return response.data;
}

/**
 * Traite le callback Unilim et connecte l'utilisateur
 * Endpoint: POST /api/auth/callback
 */
// Fonction à implémenter plus tard
// export async function handleCallback(code: string, state: string): Promise<User> {

