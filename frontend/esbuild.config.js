// ============================================================================== //
// esbuild configuration
// ============================================================================== //

// import esbuild dependencies
import * as esbuild from 'esbuild';
import * as fs from 'fs';
import * as path from 'path';

// import esbuild plugins
import { sassPlugin } from 'esbuild-sass-plugin';
import { copy } from 'esbuild-plugin-copy';
import { clean } from 'esbuild-plugin-clean';
import { fileURLToPath } from 'url';

// set filepath constants
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// define production/watch settings
const isProduction = process.env.NODE_ENV === 'production';
const isWatch = process.argv.includes('--watch');
const logState = isWatch ? 'Watching' : 'Built';

// set directory constants
const distDir = './dist';
const craftAssetsDir = '../craftcms/web/assets';
const themesDir = './src/scss/themes';

// set css modular style directories (source folder name -> output folder name)
const cssModularCategories = {
  components: 'component',
  modules: 'module',
  structure: 'structure'
};

// set js modular style directories (source folder name -> output folder name)
const jsModularCategories = {
  functions: 'function',
  components: 'component',
  modules: 'module'
};

// shared file-type loaders
const fileLoaders = {
  '.png': 'file',
  '.jpg': 'file',
  '.jpeg': 'file',
  '.svg': 'file',
  '.gif': 'file',
  '.woff': 'file',
  '.woff2': 'file',
  '.ttf': 'file',
  '.eot': 'file'
};

// ============================================================================== //
// shared css/js functions
// ============================================================================== //

function findFilesByExt(dir, ext, baseDir = dir) {
  let results = [];

  if (!fs.existsSync(dir)) return results;

  const entries = fs.readdirSync(dir, { withFileTypes: true });

  for (const entry of entries) {
    const fullPath = path.join(dir, entry.name);

    if (entry.isDirectory()) {
      results = results.concat(findFilesByExt(fullPath, ext, baseDir));
    } else if (entry.isFile() && entry.name.endsWith(ext)) {
      const relative = path.relative(baseDir, fullPath);
      const nameWithoutExt = relative.slice(0, -ext.length);
      const cleanName = nameWithoutExt.replace(/(^|\/|\\)_/g, '$1');
      results.push({
        name: cleanName.replace(/\\/g, '/'),
        relativePath: path.relative(__dirname, fullPath)
      });
    }
  }

  return results;
}

function createBaseConfig(splitting) {
  return {
    entryPoints: {},
    bundle: true,
    outdir: distDir,
    format: 'esm',
    splitting,
    sourcemap: !isProduction,
    minify: isProduction,
    target: ['es2020', 'chrome90', 'firefox88', 'safari14'],
    plugins: [],
    loader: fileLoaders
  };
}

function getModularEntries({ baseDir, ext, categories, outputPrefix }) {
  const entries = {};
  for (const [sourceFolder, outputFolder] of Object.entries(categories)) {
    const categoryPath = path.join(__dirname, baseDir, sourceFolder);
    const files = findFilesByExt(categoryPath, ext);
    for (const file of files) {
      entries[`${outputPrefix}/${outputFolder}/${file.name}`] = file.relativePath;
    }
  }
  return entries;
}

// ============================================================================== //
// css functions
// ============================================================================== //

function getThemes() {
  const themesPath = path.join(__dirname, themesDir);

  if (!fs.existsSync(themesPath)) {
    console.warn(`⚠️ Themes directory not found: ${themesPath}`);
    return [];
  }

  const themes = fs.readdirSync(themesPath, { withFileTypes: true })
    .filter(dirent => dirent.isDirectory())
    .map(dirent => dirent.name);

  return themes;
}

const THEMES = getThemes();
console.log(`🎨 Found themes: ${THEMES.join(', ')}`);

async function copyToCraft() {
  console.log('📦 Copying assets to Craft CMS...');

  if (!fs.existsSync(craftAssetsDir)) fs.mkdirSync(craftAssetsDir, { recursive: true });

  const copyRecursive = (src, dest) => {
    if (!fs.existsSync(src)) return;

    if (fs.statSync(src).isDirectory()) {
      if (!fs.existsSync(dest)) fs.mkdirSync(dest, { recursive: true });

      fs.readdirSync(src).forEach(file => {
        copyRecursive(path.join(src, file), path.join(dest, file));
      });
    } else {
      fs.copyFileSync(src, dest);
    }
  };

  copyRecursive(distDir, craftAssetsDir);
  console.log('✅ Assets copied to Craft CMS successfully!');
}

function createSassPlugin(theme) {
  const themePath = path.join(__dirname, `src/scss/themes/${theme}`);
  const scssPath = path.join(__dirname, 'src/scss');

  return sassPlugin({
    outputStyle: isProduction ? 'compressed' : 'expanded',
    loadPaths: [
      themePath,
      scssPath
    ]
  });
}

function createThemeConfig(theme, isFirstTheme, isLastTheme) {
  const themeEntry = `src/scss/themes/${theme}/entry/main.scss`;

  return {
    ...createBaseConfig(true),

    entryPoints: {
      [`css/themes/${theme}/main`]: themeEntry,
      ...(isFirstTheme ? { 'js/main': 'src/js/main.js' } : {})
    },

    plugins: [
      ...(isFirstTheme ? [clean({ patterns: [distDir] })] : []),
      createSassPlugin(theme),
      ...(isFirstTheme ? [
        copy({
          resolveFrom: 'cwd',
          assets: [{ from: ['./src/assets/**/*'], to: ['./dist'] }],
          watch: isWatch
        })
      ] : []),
      // in watch mode, re-copy whenever this theme rebuilds so changes reach
      // Craft without a full restart
      ...(isWatch ? [copyOnRebuildPlugin()] : [])
    ]
  };
}

// Copies dist -> Craft after a rebuild. Only used in watch mode; one-off
// builds copy once at the end of build(), after every config has run.
function copyOnRebuildPlugin() {
  return {
    name: 'copy-to-craft',
    setup(build) {
      build.onEnd(async (result) => {
        if (result.errors.length === 0) await copyToCraft();
      });
    }
  };
}

function createCssModularConfig(theme) {
  return {
    ...createBaseConfig(false),
    entryPoints: getModularEntries({
      baseDir: 'src/scss',
      ext: '.scss',
      categories: cssModularCategories,
      outputPrefix: `css/themes/${theme}`
    }),
    plugins: [
      createSassPlugin(theme),
      ...(isWatch ? [copyOnRebuildPlugin()] : [])
    ]
  };
}

// ============================================================================== //
// js functions
// ============================================================================== //

function createJsModularConfig() {
  return {
    ...createBaseConfig(true),
    entryPoints: getModularEntries({
      baseDir: 'src/js',
      ext: '.js',
      categories: jsModularCategories,
      outputPrefix: 'js'}),
    plugins: [
      ...(isWatch ? [copyOnRebuildPlugin()] : [])
    ]
  };
}

// ============================================================================== //
// build the frontend
// ============================================================================== //

async function runConfig(config, contexts) {
  if (Object.keys(config.entryPoints).length === 0) return;

  if (isWatch) {
    const ctx = await esbuild.context(config);
    await ctx.watch();
    contexts.push(ctx);
  } else {
    await esbuild.build(config);
  }
}

async function buildCss(contexts = []) {
  for (let i = 0; i < THEMES.length; i++) {
    const theme = THEMES[i];
    const themeEntry = `src/scss/themes/${theme}/entry/main.scss`;
    const themeEntryPath = path.join(__dirname, themeEntry);

    if (!fs.existsSync(themeEntryPath)) {
      console.warn(`⚠️ Theme entry not found: ${themeEntry}`);
      continue;
    }

    const isFirstTheme = i === 0;
    const isLastTheme = i === THEMES.length - 1;

    // build the core theme files (main.css)
    const themeConfig = createThemeConfig(theme, isFirstTheme, isLastTheme);
    await runConfig(themeConfig, contexts);

    // build the modular theme files
    const modularConfig = createCssModularConfig(theme);
    await runConfig(modularConfig, contexts);

    console.log(`✓ ${logState} theme: ${theme}`);
  }
}

async function buildJs(contexts = []) {
  const jsModularConfig = createJsModularConfig();
  await runConfig(jsModularConfig, contexts);
  console.log(`✓ ${logState} modular JS`);
}

async function build() {
  try {
    if (THEMES.length === 0) throw new Error('No themes found! Please create at least one theme in src/scss/themes/');

    const startLog = isWatch ? '👀 Starting ESBuild in watch mode...' : '🔨 Building assets...';
    console.log(startLog);

    const contexts = [];
    await buildCss(contexts);
    await buildJs(contexts);

    // copy once everything has been written — the modular CSS/JS configs run
    // after the theme config, so copying any earlier would miss their output
    await copyToCraft();

    const endLog = isWatch ? '✅ Watch mode active. Press Ctrl+C to stop.' : '✅ Build complete!';
    console.log(endLog);

    if (isWatch) await new Promise(() => {});
  } catch (error) {
    console.error('❌ Build failed:', error);
    process.exit(1);
  }
}

build();