/**
 * client.ts - API Client pour l'API PHP REST
 * 
 * Architecture: Communique avec la nouvelle API PHP structurée avec:
 * - Point d'entrée unique: /api/index.php
 * - Controllers: Auth, Documents, Upload
 * - Repositories: Accès base de données
 * 
 * DÉPLOIEMENT:
 * - En production: utilise les chemins relatifs /~valin6/cvtek/api/
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
    },
};

/**
 * Récupère l'URL de base de l'API
 */
function getApiBaseUrl(): string {
    if (process.env.NODE_ENV === 'production') {
        // En production: les APIs sont dans /~valin6/cvtek/api/
        return '/~valin6/cvtek/api';
    }

    // En développement: serveur PHP local
    return 'http://localhost:8000/api';
}

const API_BASE_URL = getApiBaseUrl();

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
 * Fait un appel API
 */
export async function apiCall<T = any>(
    endpoint: string,
    options: ApiOptions = {}
): Promise<ApiResponse<T>> {
    const url = API_BASE_URL + endpoint;
    const throwOnError = options.throwOnError ?? true;
    
    console.log(`🔗 API Call: ${options.method || 'GET'} ${url}`);
    
    try {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers,
            },
            credentials: 'include', // Important pour les cookies de session
        });

        console.log(`📊 Response Status: ${response.status} ${response.statusText}`);

        // Parser la réponse
        const contentType = response.headers.get('content-type');
        let data: ApiResponse<T>;

        if (contentType?.includes('application/json')) {
            data = await response.json();
        } else {
            // Si ce n'est pas du JSON, créer une réponse d'erreur
            const text = await response.text();
            console.error('❌ Réponse non-JSON reçue:', text.substring(0, 500));
            data = {
                success: false,
                error: 'Réponse serveur invalide (pas du JSON)',
            };
        }

        // Vérifier le statut HTTP
        if (!response.ok) {
            console.error(`❌ API Error (${response.status}):`, data.error);
            if (throwOnError) {
                throw new Error(data.error || `HTTP ${response.status}`);
            }
            return data;
        }

        console.log(`✅ Data reçue du backend:`, data);
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
    created_at?: string;
}

/**
 * Connexion utilisateur
 * Endpoint: POST /api/auth/login
 */
export async function login(credentials: LoginCredentials): Promise<User> {
    const response = await apiCall<{ user: User }>(API_CONFIG.ROUTES.AUTH_LOGIN, {
        method: 'POST',
        body: JSON.stringify(credentials),
    });

    if (!response.success || !response.data?.user) {
        throw new Error(response.error || 'Connexion échouée');
    }

    return response.data.user;
}

/**
 * Inscription utilisateur
 * Endpoint: POST /api/auth/register
 */
export async function register(credentials: RegisterCredentials): Promise<User> {
    const response = await apiCall<{ user: User }>(API_CONFIG.ROUTES.AUTH_REGISTER, {
        method: 'POST',
        body: JSON.stringify(credentials),
    });

    if (!response.success || !response.data?.user) {
        throw new Error(response.error || 'Inscription échouée');
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
/**
 * Déconnexion
 * Endpoint: POST /api/auth/logout
 */
export async function logout(): Promise<void> {
    await apiCall(API_CONFIG.ROUTES.AUTH_LOGOUT, {
        method: 'POST',
        throwOnError: false,
    });
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
}): Promise<{ message: string; id: number }> {
    const response = await apiCall<{ message: string; id: number }>(
        API_CONFIG.ROUTES.DOCUMENTS,
        {
            method: 'POST',
            body: JSON.stringify(data),
        }
    );

    if (!response.success || !response.data) {
        throw new Error(response.error || 'Erreur lors de la création du document');
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
        console.warn(`⚠️ Erreur lors de la récupération des versions du document ${docId}`);
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
    const response = await apiCall<{ message: string }>(
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

    console.log(`📤 Uploading: ${file.name} (${file.size} bytes)`);

    const url = API_BASE_URL + API_CONFIG.ROUTES.UPLOAD;
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'include',
        });

        console.log(`📊 Upload Response Status: ${response.status}`);

        const data = await response.json() as ApiResponse<UploadResponse>;

        if (!response.ok || !data.success) {
            console.error('❌ Upload failed:', data.error);
            throw new Error(data.error || `Upload failed with status ${response.status}`);
        }

        console.log(`✅ Upload successful:`, data.data);
        return data.data!;

    } catch (error) {
        const errorMsg = error instanceof Error ? error.message : String(error);
        console.error(`❌ Upload exception:`, errorMsg);
        throw error;
    }
}
