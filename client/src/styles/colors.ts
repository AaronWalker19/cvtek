/**
 * Palette de couleurs du site CVTEK
 * Centralisée et utilisable en JavaScript/TypeScript
 * 
 * Usage:
 * import { COLORS } from '@/styles/colors.ts';
 * 
 * // Utiliser dans du code JS
 * const bgColor = COLORS.primary;
 * 
 * // Ou utiliser dans du JSX avec des variables
 * <div style={{ backgroundColor: COLORS.primary }}>Content</div>
 */

export const COLORS = {
  /* ============ COULEURS PRIMAIRES ============ */
  primary: '#183542',       // Bleu-gris principal
  primaryDark: '#0f2835',   // Bleu-gris très foncé
  primaryDarker: '#0d1f28', // Bleu-gris encore plus foncé

  /* ============ COULEURS NEUTRES CLAIRES ============ */
  white: '#ffffff',         // Blanc pur
  whiteOff: '#faf8f7',      // Blanc cassé
  gray50: '#f3f3f5',        // Gris très clair
  gray100: '#e9ebef',       // Gris ultra-pâle
  gray150: '#ececf0',       // Gris ultra-pâle variant
  gray200: '#e5e5e5',       // Gris très pâle
  gray300: '#d9d9d9',       // Gris pâle
  gray400: '#c9c9c9',       // Gris clair
  grayLight: '#f2eff8',     // Gris-bleu ultra-pâle

  /* ============ COULEURS NEUTRES FONCÉES ============ */
  black: '#030213',         // Noir très foncé
  blackAlt: '#0a0908',      // Noir très foncé alternatif
  blackVariant: '#1b0f0f',  // Noir très foncé variant
  darkGray: '#453c3e',      // Gris-marron foncé
  grayMedium: '#706167',    // Gris-marron moyen
  grayMuted: '#717182',     // Gris bleuté
  grayMutedLight: '#b5adb0', // Gris-marron clair

  /* ============ COULEURS DE STATUT ============ */
  success: '#137300',       // Vert (réussi/complet)
  warning: '#ff9500',       // Orange (avertissement)
  error: '#c9232c',         // Rouge éclatant
  errorAccent: '#ff404a',   // Rouge appel à l'action
  errorDestructive: '#d4183d', // Rouge destructif
  errorDark: '#a01f26',     // Rouge foncé (hover)
  gold: '#bd8100',          // Or/doré foncé
} as const;

/**
 * Alias pour une meilleure contextualité
 */
export const COLOR_GROUPS = {
  primary: {
    main: COLORS.primary,
    dark: COLORS.primaryDark,
    darker: COLORS.primaryDarker,
  },
  neutral: {
    light: [
      COLORS.white,
      COLORS.whiteOff,
      COLORS.gray50,
      COLORS.gray100,
      COLORS.gray150,
      COLORS.gray200,
      COLORS.gray300,
      COLORS.gray400,
      COLORS.grayLight,
    ],
    dark: [
      COLORS.black,
      COLORS.blackAlt,
      COLORS.blackVariant,
      COLORS.darkGray,
      COLORS.grayMedium,
      COLORS.grayMuted,
      COLORS.grayMutedLight,
    ],
  },
  status: {
    success: COLORS.success,
    warning: COLORS.warning,
    error: COLORS.error,
    errorAccent: COLORS.errorAccent,
    errorDestructive: COLORS.errorDestructive,
    errorDark: COLORS.errorDark,
    gold: COLORS.gold,
  },
};

/**
 * Fonction utilitaire pour obtenir une couleur avec opacité
 * @param color - La couleur (en hex)
 * @param opacity - L'opacité (0-1)
 * @returns Couleur en format rgba()
 */
export function withOpacity(color: string, opacity: number): string {
  // Convertir hex en RGB
  const hex = color.replace('#', '');
  const r = parseInt(hex.substring(0, 2), 16);
  const g = parseInt(hex.substring(2, 4), 16);
  const b = parseInt(hex.substring(4, 6), 16);
  return `rgba(${r}, ${g}, ${b}, ${opacity})`;
}

/**
 * Type pour l'autocomplétion
 */
export type ColorKey = keyof typeof COLORS;
