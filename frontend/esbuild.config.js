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

function getCssModularEntries(theme) {
  const entries = {};

  for (const [sourceFolder, outputFolder] of Object.entries(cssModularCategories)) {
    const categoryPath = path.join(__dirname, 'src/scss', sourceFolder);
    const files = findFilesByExt(categoryPath, '.scss');

    for (const file of files) {
      entries[`css/themes/${theme}/${outputFolder}/${file.name}`] = file.relativePath;
    }
  }

  return entries;
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
      {
        name: 'copy-to-craft',
        setup(build) {
          build.onEnd(async (result) => {
            if (result.errors.length === 0 && isLastTheme) {
              await copyToCraft();
            }
          });
        }
      }
    ]
  };
}

function createModularConfig(theme) {
  return {
    ...createBaseConfig(false),
    entryPoints: getCssModularEntries(theme),
    plugins: [createSassPlugin(theme)]
  };
}

// ============================================================================== //
// js functions
// ============================================================================== //

function getJsModularEntries() {
  const entries = {};

  for (const [sourceFolder, outputFolder] of Object.entries(jsModularCategories)) {
    const categoryPath = path.join(__dirname, 'src/js', sourceFolder);
    const files = findFilesByExt(categoryPath, '.js');

    for (const file of files) {
      entries[`js/${outputFolder}/${file.name}`] = file.relativePath;
    }
  }

  return entries;
}

function createJsModularConfig() {
  return {
    ...createBaseConfig(true),
    entryPoints: getJsModularEntries(),
    plugins: []
  };
}

// ============================================================================== //
// build the frontend
// ============================================================================== //

async function build() {
  try {
    if (THEMES.length === 0) throw new Error('No themes found! Please create at least one theme in src/scss/themes/');

    if (isWatch) {
      console.log('👀 Starting ESBuild in watch mode...');

      const contexts = [];

      // build the scss themes in watch mode
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

        const ctx = await esbuild.context(createThemeConfig(theme, isFirstTheme, isLastTheme));
        await ctx.watch();
        contexts.push(ctx);

        const modularConfig = createModularConfig(theme);
        if (Object.keys(modularConfig.entryPoints).length > 0) {
          const modularCtx = await esbuild.context(modularConfig);
          await modularCtx.watch();
          contexts.push(modularCtx);
        }
        console.log(`✓ Watching theme: ${theme}`);
      }

      // build the modular js in watch mode
      const jsModularConfig = createJsModularConfig();
      if (Object.keys(jsModularConfig.entryPoints).length > 0) {
        const jsModularCtx = await esbuild.context(jsModularConfig);
        await jsModularCtx.watch();
        contexts.push(jsModularCtx);
        console.log('✓ Watching modular JS');
      }

      console.log('✅ Watch mode active. Press Ctrl+C to stop.');
      await new Promise(() => {});
    } else {
      console.log('🔨 Building assets...');

      // build the scss themes
      for (let i = 0; i < THEMES.length; i++) {
        const theme = THEMES[i];
        const themeEntry = `src/scss/themes/${theme}/entry/main.scss`;
        const themeEntryPath = path.join(__dirname, themeEntry);

        if (!fs.existsSync(themeEntryPath)) {
          console.warn(`⚠️  Theme entry not found: ${themeEntry}`);
          continue;
        }

        const isFirstTheme = i === 0;
        const isLastTheme = i === THEMES.length - 1;

        await esbuild.build(createThemeConfig(theme, isFirstTheme, isLastTheme));

        const modularConfig = createModularConfig(theme);
        if (Object.keys(modularConfig.entryPoints).length > 0) await esbuild.build(modularConfig);

        console.log(`✓ Built theme: ${theme}`);
      }

      // build the modular js
      const jsModularConfig = createJsModularConfig();
      if (Object.keys(jsModularConfig.entryPoints).length > 0) {
        await esbuild.build(jsModularConfig);
        console.log('✓ Built modular JS');
      }

      console.log('✅ Build complete!');
    }
  } catch (error) {
    console.error('❌ Build failed:', error);
    process.exit(1);
  }
}

build();