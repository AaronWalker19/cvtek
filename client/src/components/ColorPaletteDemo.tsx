import { COLORS } from '../styles/colors';

interface ColorBoxProps {
  name: string;
  hex: string;
  variable?: string;
}

function ColorBox({ name, hex, variable }: ColorBoxProps) {
  return (
    <div className="flex flex-col gap-2">
      <div
        className="w-32 h-24 rounded border border-gray-200"
        style={{ backgroundColor: hex }}
      />
      <div className="text-xs">
        <p className="font-semibold text-gray-900">{name}</p>
        <p className="text-gray-600">{hex}</p>
        {variable && <p className="text-gray-500">var({variable})</p>}
      </div>
    </div>
  );
}

/**
 * Composant de démonstration complet
 */
export function ColorPaletteDemo() {
  return (
    <div className="p-8 bg-[#ffffff]">
      <h1 className="text-3xl font-bold mb-2 text-primary">
        🎨 Palette de Couleurs
      </h1>
      <p className="text-gray-600 mb-8">
        Toutes les couleurs du site sont centralisées et faciles à modifier
      </p>

      {/* COULEURS PRIMAIRES */}
      <section className="mb-12">
        <h2 className="text-2xl font-bold mb-4 text-primary-dark">
          Couleurs Primaires
        </h2>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <ColorBox
            name="Primary"
            hex={COLORS.primary}
            variable="--color-primary"
          />
          <ColorBox
            name="Primary Dark"
            hex={COLORS.primaryDark}
            variable="--color-primary-dark"
          />
          <ColorBox
            name="Primary Darker"
            hex={COLORS.primaryDarker}
            variable="--color-primary-darker"
          />
        </div>
      </section>

      {/* COULEURS NEUTRES CLAIRES */}
      <section className="mb-12">
        <h2 className="text-2xl font-bold mb-4 text-primary-dark">
          Couleurs Neutres Claires
        </h2>
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
          <ColorBox name="White" hex={COLORS.white} variable="--color-white" />
          <ColorBox
            name="White Off"
            hex={COLORS.whiteOff}
            variable="--color-white-off"
          />
          <ColorBox
            name="Gray 50"
            hex={COLORS.gray50}
            variable="--color-gray-50"
          />
          <ColorBox
            name="Gray 100"
            hex={COLORS.gray100}
            variable="--color-gray-100"
          />
          <ColorBox
            name="Gray 150"
            hex={COLORS.gray150}
            variable="--color-gray-150"
          />
          <ColorBox
            name="Gray 200"
            hex={COLORS.gray200}
            variable="--color-gray-200"
          />
          <ColorBox
            name="Gray 300"
            hex={COLORS.gray300}
            variable="--color-gray-300"
          />
          <ColorBox
            name="Gray 400"
            hex={COLORS.gray400}
            variable="--color-gray-400"
          />
          <ColorBox
            name="Gray Light"
            hex={COLORS.grayLight}
            variable="--color-gray-light"
          />
        </div>
      </section>

      {/* COULEURS NEUTRES FONCÉES */}
      <section className="mb-12">
        <h2 className="text-2xl font-bold mb-4 text-primary-dark">
          Couleurs Neutres Foncées
        </h2>
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
          <ColorBox name="Black" hex={COLORS.black} variable="--color-black" />
          <ColorBox
            name="Black Alt"
            hex={COLORS.blackAlt}
            variable="--color-black-alt"
          />
          <ColorBox
            name="Black Variant"
            hex={COLORS.blackVariant}
            variable="--color-black-variant"
          />
          <ColorBox
            name="Dark Gray"
            hex={COLORS.darkGray}
            variable="--color-dark-gray"
          />
          <ColorBox
            name="Gray Medium"
            hex={COLORS.grayMedium}
            variable="--color-gray-medium"
          />
          <ColorBox
            name="Gray Muted"
            hex={COLORS.grayMuted}
            variable="--color-gray-muted"
          />
          <ColorBox
            name="Gray Muted Light"
            hex={COLORS.grayMutedLight}
            variable="--color-gray-muted-light"
          />
        </div>
      </section>

      {/* COULEURS DE STATUT */}
      <section className="mb-12">
        <h2 className="text-2xl font-bold mb-4 text-primary-dark">
          Couleurs de Statut
        </h2>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <ColorBox
            name="Success"
            hex={COLORS.success}
            variable="--color-success"
          />
          <ColorBox
            name="Warning"
            hex={COLORS.warning}
            variable="--color-warning"
          />
          <ColorBox name="Error" hex={COLORS.error} variable="--color-error" />
          <ColorBox
            name="Error Accent"
            hex={COLORS.errorAccent}
            variable="--color-error-accent"
          />
          <ColorBox
            name="Error Destructive"
            hex={COLORS.errorDestructive}
            variable="--color-error-destructive"
          />
          <ColorBox
            name="Error Dark"
            hex={COLORS.errorDark}
            variable="--color-error-dark"
          />
          <ColorBox name="Gold" hex={COLORS.gold} variable="--color-gold" />
        </div>
      </section>

      {/* EXEMPLES D'UTILISATION */}
      <section>
        <h2 className="text-2xl font-bold mb-4 text-primary-dark">
          Exemples d'utilisation
        </h2>

        <div className="space-y-4">
          {/* Boutons */}
          <div>
            <h3 className="text-lg font-semibold mb-2 text-gray-900">
              Boutons
            </h3>
            <div className="flex flex-wrap gap-2">
              <button className="px-4 py-2 bg-primary text-white rounded hover:bg-primary-dark transition">
                Primaire
              </button>
              <button className="px-4 py-2 bg-success text-white rounded hover:opacity-90 transition">
                Succès ✓
              </button>
              <button className="px-4 py-2 bg-error text-white rounded hover:bg-error-dark transition">
                Erreur ✗
              </button>
              <button className="px-4 py-2 bg-warning text-white rounded hover:opacity-90 transition">
                Avertissement ⚠
              </button>
            </div>
          </div>

          {/* Cartes */}
          <div>
            <h3 className="text-lg font-semibold mb-2 text-gray-900">Cartes</h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="bg-gray-50 border-2 border-primary p-4 rounded">
                <h4 className="text-primary font-semibold mb-1">
                  Carte avec bordure primaire
                </h4>
                <p className="text-gray-600">Fond gris clair</p>
              </div>

              <div className="bg-primary text-white p-4 rounded">
                <h4 className="font-semibold mb-1">Carte primaire</h4>
                <p className="opacity-90">Texte blanc sur primaire</p>
              </div>
            </div>
          </div>

          {/* Alertes */}
          <div>
            <h3 className="text-lg font-semibold mb-2 text-gray-900">
              Alertes
            </h3>
            <div className="space-y-2">
              <div className="bg-success/10 border-l-4 border-success p-3 rounded text-success">
                ✓ Succès: L'opération a été complétée
              </div>
              <div className="bg-warning/10 border-l-4 border-warning p-3 rounded text-warning">
                ⚠ Attention: Veuillez faire attention
              </div>
              <div className="bg-error/10 border-l-4 border-error p-3 rounded text-error">
                ✗ Erreur: Une erreur s'est produite
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* INFORMATIONS */}
      <section className="mt-12 p-4 bg-gray-50 rounded border border-gray-200">
        <h3 className="text-lg font-semibold mb-2">ℹ️ Informations</h3>
        <ul className="text-sm text-gray-600 space-y-1">
          <li>
            • Fichier de configuration:{' '}
            <code className="bg-[#ffffff] px-2 py-1 rounded">
              src/styles/theme.css
            </code>
          </li>
          <li>
            • Variables JavaScript:{' '}
            <code className="bg-[#ffffff] px-2 py-1 rounded">
              src/styles/colors.ts
            </code>
          </li>
          <li>
            • Configuration Tailwind:{' '}
            <code className="bg-[#ffffff] px-2 py-1 rounded">tailwind.config.js</code>
          </li>
          <li>
            • Documentation:{' '}
            <code className="bg-[#ffffff] px-2 py-1 rounded">
              COULEURS_DOCUMENTATION.md
            </code>
          </li>
        </ul>
      </section>
    </div>
  );
}

export default ColorPaletteDemo;
