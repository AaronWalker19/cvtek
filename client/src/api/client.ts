/**
 * client.ts - API Client pour l'API PHP
 * 
 * Remplace le client Express par une version compatible PHP
 * 
 * DÉPLOIEMENT:
 * - En production: utilise les chemins relatifs /~valin6/cvtek/api/
 * - En développement: utilise http://localhost:8000/
 */

// ===============================================
// Configuration
// ===============================================

const API_CONFIG = {
    // Routes API
    ROUTES: {
        LOGIN: '/api/auth/login',
        REGISTER: '/api/auth/register',
        USER: '/api/auth/user',
        LOGOUT: '/api/auth/logout',
        
        DOCUMENTS: '/api/documents',
        DOCUMENTS_ALL: '/api/documents/all',
        UPLOAD: '/api/upload',
    },
};

/**
 * Récupère l'URL de base de l'API
 */
function getApiBaseUrl(): string {
    if (process.env.NODE_ENV === 'production') {
        // En production: les APIs sont dans le même dossier /~valin6/cvtek/api/
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
    username: string;
    password: string;
}

export interface RegisterCredentials {
    username: string;
    email: string;
    password: string;
    role?: 'student' | 'professor';
}

export interface User {
    userId: number;
    username: string;
    email: string;
    role: 'student' | 'professor' | 'admin';
    login_at?: string;
}

/**
 * Connexion utilisateur
 */
export async function login(credentials: LoginCredentials): Promise<User> {
    const response = await apiCall<User>(API_CONFIG.ROUTES.LOGIN, {
        method: 'POST',
        body: JSON.stringify(credentials),
    });

    if (!response.success || !response.data) {
        throw new Error(response.error || 'Connexion échouée');
    }

    return response.data;
}

/**
 * Inscription utilisateur
 */
export async function register(credentials: RegisterCredentials): Promise<User> {
    const response = await apiCall<User>(API_CONFIG.ROUTES.REGISTER, {
        method: 'POST',
        body: JSON.stringify(credentials),
    });

    if (!response.success || !response.data) {
        throw new Error(response.error || 'Inscription échouée');
    }

    return response.data;
}

/**
 * Récupère le profil de l'utilisateur connecté
 */
export async function getCurrentUser(): Promise<User | null> {
    const response = await apiCall<User>(API_CONFIG.ROUTES.USER, {
        method: 'GET',
        throwOnError: false,
    });

    if (!response.success) {
        return null;
    }

    return response.data ?? null;
}

/**
 * Déconnexion
 */
export async function logout(): Promise<void> {
    await apiCall(API_CONFIG.ROUTES.LOGOUT, {
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
    titre: string;
    type_fichier: string;
    url_fichier: string;
    description: string;
    version: number;
    created_at: string;
    updated_at: string;
}

/**
 * Récupère les documents de l'utilisateur
 */
export async function getMyDocuments(userId?: number): Promise<Document[]> {
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

    if (!response.success || !response.data) {
        throw new Error(response.error || 'Erreur lors de la récupération des documents');
    }

    return response.data.documents;
}

/**
 * Récupère TOUS les documents (pour profs/admins)
 */
export async function getAllDocuments(): Promise<Document[]> {
    const response = await apiCall<{
        count: number;
        documents: Document[];
    }>(API_CONFIG.ROUTES.DOCUMENTS_ALL, {
        method: 'GET',
    });

    if (!response.success || !response.data) {
        throw new Error(response.error || 'Erreur lors de la récupération des documents');
    }

    return response.data.documents;
}

/**
 * Crée un nouveau document
 */
export async function createDocument(data: Partial<Document>): Promise<Document> {
    const response = await apiCall<Document>(API_CONFIG.ROUTES.DOCUMENTS, {
        method: 'POST',
        body: JSON.stringify(data),
    });

    if (!response.success || !response.data) {
        throw new Error(response.error || 'Erreur lors de la création du document');
    }

    return response.data;
}

/**
 * Met à jour un document
 */
export async function updateDocument(id: number, data: Partial<Document>): Promise<Document> {
    const response = await apiCall<Document>(`${API_CONFIG.ROUTES.DOCUMENTS}/${id}`, {
        method: 'PUT',
        body: JSON.stringify(data),
    });

    if (!response.success || !response.data) {
        throw new Error(response.error || 'Erreur lors de la mise à jour');
    }

    return response.data;
}

/**
 * Supprime un document
 */
export async function deleteDocument(id: number): Promise<void> {
    const response = await apiCall(`${API_CONFIG.ROUTES.DOCUMENTS}/${id}`, {
        method: 'DELETE',
    });

    if (!response.success) {
        throw new Error(response.error || 'Erreur lors de la suppression');
    }
}

// ===============================================
// Upload
// ===============================================

export interface UploadResponse {
    filename: string;
    originalname: string;
    url: string;
    size: number;
    type: string;
    message: string;
}

/**
 * Upload un fichier
 */
export async function uploadFile(file: File): Promise<UploadResponse> {
    const formData = new FormData();
    formData.append('file', file);

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
