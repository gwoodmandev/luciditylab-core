# Craft Theme Editor

A private Craft CMS 5 plugin that reskins the Control Panel with preconfigured themes or fully custom colours and branding.

## Features

- 5 built-in presets: Default, Dark, Midnight, Warm, High Contrast
- Custom colour editor (backgrounds, text, accents, borders)
- Custom logo/branding via Craft asset or direct URL
- Site-wide default set by admins
- Per-user theme override stored in user preferences
- Live preview in the settings UI — no page reload needed

---

## Installation

### 1. Copy the plugin into your project

Place the `craft-theme-editor` directory inside your local plugins folder:

```
craftcms/local/craft-theme-editor/
```

### 2. Register the local path repository in your root `composer.json`

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./local/craft-theme-editor"
        }
    ]
}
```

### 3. Require the plugin

```bash
composer require vendor/craft-theme-editor:@dev
```

### 4. Install via the Craft CLI

```bash
php craft plugin/install craft-theme-editor
```

Or install it from **Settings → Plugins** in the Craft CP.

---

## Configuration

Optionally copy `config.php` into your project's `config/` directory and rename it to `craft-theme-editor.php` to set environment-level defaults:

```bash
cp local/craft-theme-editor/config.php config/craft-theme-editor.php
```

---

## Usage

1. Navigate to **Theme Editor** in the CP sidebar
2. Select a preset or choose **Custom** to configure your own colours
3. Upload a logo under **Branding** (optional)
4. Click **Save Site Default** to apply to all users

Individual users can override the site default from the same settings page — their preference is stored privately in their user account.

---

## Project Structure

```
craft-theme-editor/
├── composer.json
├── config.php                          ← Copyable config defaults
├── src/
│   ├── ThemeEditorPlugin.php           ← Plugin entry point
│   ├── controllers/
│   │   └── ThemeController.php         ← AJAX save/load endpoints
│   ├── models/
│   │   └── ThemeSettings.php           ← Site-wide settings model
│   └── services/
│       └── ThemeService.php            ← Theme resolution + CSS generation
├── templates/
│   ├── settings.twig                   ← Main settings page
│   └── _includes/
│       └── colour-picker.twig          ← Reusable colour input component
└── resources/
    ├── css/
    │   └── theme-editor.css            ← Settings UI styles
    └── js/
        └── theme-editor.js             ← Live preview + save logic
```

---

## Extending

### Adding a new preset

Open `src/services/ThemeService.php` and add a new entry to the `getPresets()` array:

```php
'brand' => [
    'label'           => 'Brand',
    'bgPrimary'       => '#fafafa',
    'bgSecondary'     => '#f0f0f0',
    'bgSidebar'       => '#1a1a2e',
    'textPrimary'     => '#111827',
    'textMuted'       => '#6b7280',
    'textSidebar'     => '#e5e7eb',
    'accentColor'     => '#7c3aed',
    'accentColorHover'=> '#6d28d9',
    'borderColor'     => '#e5e7eb',
],
```

Then add a matching card to `templates/settings.twig` in the presets array.

---

## Requirements

- Craft CMS 5.0+
- PHP 8.1+
