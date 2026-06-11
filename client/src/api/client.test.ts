import * as client from './client';

// Mock de fetch global
global.fetch = jest.fn();

describe('API Client', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    sessionStorage.clear();
    (global.fetch as jest.Mock).mockClear();
  });

  describe('Token Management', () => {
    it('doit stocker et récupérer le token', () => {
      client.storeToken('test-token');
      expect(client.getToken()).toBe('test-token');
    });

    it('doit supprimer le token', () => {
      client.storeToken('test-token');
      client.clearToken();
      expect(client.getToken()).toBeNull();
    });
  });

  describe('API Call - Success', () => {
    it('doit faire un appel API GET réussi', async () => {
      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: {
          get: () => 'application/json',
        },
        json: async () => ({
          success: true,
          data: { id: '1', name: 'Test' },
        }),
      });

      const result = await client.apiCall('/test');

      expect(result.success).toBe(true);
      expect(result.data).toEqual({ id: '1', name: 'Test' });
      expect(global.fetch).toHaveBeenCalled();
    });

    it('doit faire un appel API POST avec données', async () => {
      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: {
          get: () => 'application/json',
        },
        json: async () => ({
          success: true,
          data: { id: '1' },
        }),
      });

      const result = await client.apiCall('/test', {
        method: 'POST',
        body: JSON.stringify({ name: 'Test' }),
      });

      expect(result.success).toBe(true);
      expect(global.fetch).toHaveBeenCalledWith(
        expect.anything(),
        expect.objectContaining({
          method: 'POST',
        })
      );
    });
  });

  describe('API Call - Error Handling', () => {
    it('doit gérer les erreurs HTTP', async () => {
      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: false,
        status: 401,
        headers: {
          get: () => 'application/json',
        },
        json: async () => ({
          success: false,
          error: 'Unauthorized',
        }),
      });

      const result = await client.apiCall('/test', { throwOnError: false });

      expect(result.success).toBe(false);
      expect(result.error).toBe('Unauthorized');
    });

    it('doit lever une exception si throwOnError est true', async () => {
      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: false,
        status: 401,
        headers: {
          get: () => 'application/json',
        },
        json: async () => ({
          success: false,
          error: 'Unauthorized',
        }),
      });

      await expect(
        client.apiCall('/test', { throwOnError: true })
      ).rejects.toThrow();
    });

    it('doit gérer les réponses non-JSON', async () => {
      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: false,
        status: 500,
        headers: {
          get: () => 'text/html',
        },
        text: async () => '<html>Error</html>',
      });

      const result = await client.apiCall('/test', { throwOnError: false });

      expect(result.success).toBe(false);
      expect(result.error).toContain('invalide');
    });
  });

  describe('Token in Headers', () => {
    it('doit ajouter le token dans les headers', async () => {
      client.storeToken('bearer-token');

      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: {
          get: () => 'application/json',
        },
        json: async () => ({
          success: true,
          data: {},
        }),
      });

      await client.apiCall('/test');

      expect(global.fetch).toHaveBeenCalledWith(
        expect.anything(),
        expect.objectContaining({
          headers: expect.objectContaining({
            Authorization: 'Bearer bearer-token',
          }),
        })
      );
    });
  });
});
