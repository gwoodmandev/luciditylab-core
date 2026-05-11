/**
 * Craft Theme Editor — settings page JS.
 *
 * Wires up the preset cards: clicking one sets it active, writes its key into
 * the hidden `settings[activePreset]` input (which Craft will save), and
 * applies its CSS variables + extra rules live so the user can preview the
 * change immediately.
 *
 * Preset data shape (per preset):
 *   { vars: { '--foo': 'value', ... }, extraCss: 'css string' }
 */
class CraftThemeEditor {
    constructor() {
        this.input   = document.getElementById('activePreset');
        this.cards   = document.querySelectorAll('.theme-preset-card');
        this.presets = window.CraftThemeEditorPresets || {};
        this.initial = window.CraftThemeEditorInitial || 'default';

        // Snapshot every variable any preset touches so we can revert cleanly
        // when switching between presets.
        this.touchedVars = new Set();
        Object.values(this.presets).forEach((preset) => {
            const vars = (preset && preset.vars) || {};
            Object.keys(vars).forEach((k) => this.touchedVars.add(k));
        });

        // <style> tag we'll use to inject each preset's extraCss block.
        this.styleEl = document.getElementById('theme-editor-preview-extra');
        if (!this.styleEl) {
            this.styleEl = document.createElement('style');
            this.styleEl.id = 'theme-editor-preview-extra';
            document.head.appendChild(this.styleEl);
        }

        this.bindPresetCards();
    }

    bindPresetCards() {
        this.cards.forEach((card) => {
            card.addEventListener('click', (e) => {
                e.preventDefault();

                const preset = card.dataset.preset;

                this.cards.forEach((c) => c.classList.remove('is-active'));
                card.classList.add('is-active');

                if (this.input) {
                    this.input.value = preset;
                }

                this.previewPreset(preset);
            });
        });
    }

    previewPreset(presetKey) {
        const root    = document.documentElement;
        const preset  = this.presets[presetKey] || { vars: {}, extraCss: '' };
        const vars    = preset.vars || {};
        const extra   = preset.extraCss || '';

        // Clear all variables any preset might set, then apply the new ones.
        this.touchedVars.forEach((name) => {
            root.style.removeProperty(name);
        });

        Object.entries(vars).forEach(([name, value]) => {
            root.style.setProperty(name, value);
        });

        // Swap in the preset's extra-CSS overrides.
        this.styleEl.textContent = extra;
    }
}

// Expose globally so the inline {% js %} block in settings.twig can use it.
window.CraftThemeEditor = CraftThemeEditor;
