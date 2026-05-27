// Polyfill pour TextEncoder/TextDecoder AVANT tout (nécessaire pour react-router)
if (typeof global.TextEncoder === 'undefined') {
  try {
    const { TextEncoder, TextDecoder } = require('util');
    global.TextEncoder = TextEncoder;
    global.TextDecoder = TextDecoder;
  } catch (e) {
    // Fallback si util n'est pas disponible
  }
}

// jest-dom ajoute des matchers Jest personnalisés pour assertions sur les nœuds DOM
// permet de faire : expect(element).toHaveTextContent(/react/i)
import '@testing-library/jest-dom';

// Mock pour les fichiers CSS
jest.mock('./styles/tailwind.css', () => ({}));
jest.mock('./styles/globals.css', () => ({}));
jest.mock('./styles/theme.css', () => ({}));
jest.mock('./styles/fonts.css', () => ({}));

// Mock pour localStorage
const localStorageMock = {
  getItem: jest.fn(),
  setItem: jest.fn(),
  removeItem: jest.fn(),
  clear: jest.fn(),
};
global.localStorage = localStorageMock as any;

// Supprime les avertissements React 19
const originalError = console.error;
beforeAll(() => {
  console.error = (...args: any[]) => {
    if (
      typeof args[0] === 'string' &&
      args[0].includes('Warning: ReactDOM.render')
    ) {
      return;
    }
    originalError.call(console, ...args);
  };
});

afterAll(() => {
  console.error = originalError;
});
