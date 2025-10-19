import * as esbuild from 'esbuild';
import { sassPlugin } from 'esbuild-sass-plugin';
import { copy } from 'esbuild-plugin-copy';
import { clean } from 'esbuild-plugin-clean';
import * as fs from 'fs';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const isProduction = process.env.NODE_ENV === 'production';
const isWatch = process.argv.includes('--watch');

// Paths
const distDir = './dist';
const craftAssetsDir = '../craftcms/web/assets';
const themesDir = './src/scss/themes';

// Automatically detect themes
function getThemes() {
  const themesPath = path.join(__dirname, themesDir);
  
  if (!fs.existsSync(themesPath)) {
    console.warn(`⚠️  Themes directory not found: ${themesPath}`);
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
  
  if (!fs.existsSync(craftAssetsDir)) {
    fs.mkdirSync(craftAssetsDir, { recursive: true });
  }
  
  const copyRecursive = (src, dest) => {
    if (!fs.existsSync(src)) return;
    
    if (fs.statSync(src).isDirectory()) {
      if (!fs.existsSync(dest)) {
        fs.mkdirSync(dest, { recursive: true });
      }
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

// Build configuration for a specific theme
function createThemeConfig(theme, isFirstTheme, isLastTheme) {
  const themeEntry = `src/scss/themes/${theme}/entry/main.scss`;
  const themePath = path.join(__dirname, `src/scss/themes/${theme}`);
  const scssPath = path.join(__dirname, 'src/scss');
  
  return {
    entryPoints: {
      [`css/themes/${theme}/main`]: themeEntry,
      ...(isFirstTheme ? { 'js/main': 'src/js/main.js' } : {}) // Only include JS once
    },
    bundle: true,
    outdir: distDir,
    format: 'esm',
    splitting: true,
    sourcemap: !isProduction,
    minify: isProduction,
    target: ['es2020', 'chrome90', 'firefox88', 'safari14'],
    
    plugins: [
      // Clean only on first theme
      ...(isFirstTheme ? [
        clean({
          patterns: [distDir]
        })
      ] : []),
      
      // SASS compilation with theme-specific load paths
      sassPlugin({
        outputStyle: isProduction ? 'compressed' : 'expanded',
        loadPaths: [
          themePath,  // This allows @use 'theme' to resolve to this theme's facade
          scssPath    // This allows other imports to work normally
        ]
      }),
      
      // Copy static assets only once (on first theme)
      ...(isFirstTheme ? [
        copy({
          resolveFrom: 'cwd',
          assets: [
            {
              from: ['./src/assets/**/*'],
              to: ['./dist/assets']
            }
          ],
          watch: isWatch
        })
      ] : []),
      
      // Copy to Craft after last theme builds
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
    ],
    
    loader: {
      '.png': 'file',
      '.jpg': 'file',
      '.jpeg': 'file',
      '.svg': 'file',
      '.gif': 'file',
      '.woff': 'file',
      '.woff2': 'file',
      '.ttf': 'file',
      '.eot': 'file'
    }
  };
}

async function build() {
  try {
    if (THEMES.length === 0) {
      throw new Error('No themes found! Please create at least one theme in src/scss/themes/');
    }

    if (isWatch) {
      console.log('👀 Starting ESBuild in watch mode...');
      
      // Create watch context for each theme
      const contexts = [];
      
      for (let i = 0; i < THEMES.length; i++) {
        const theme = THEMES[i];
        const themeEntry = `src/scss/themes/${theme}/entry/main.scss`;
        const themeEntryPath = path.join(__dirname, themeEntry);
        
        if (!fs.existsSync(themeEntryPath)) {
          console.warn(`  ⚠️  Theme entry not found: ${themeEntry}`);
          continue;
        }
        
        const isFirstTheme = i === 0;
        const isLastTheme = i === THEMES.length - 1;
        const themeConfig = createThemeConfig(theme, isFirstTheme, isLastTheme);
        
        const ctx = await esbuild.context(themeConfig);
        await ctx.watch();
        contexts.push(ctx);
        
        console.log(`  ✓ Watching theme: ${theme}`);
      }
      
      console.log('✅ Watch mode active. Press Ctrl+C to stop.');
      
      // Keep the process running
      await new Promise(() => {});
      
    } else {
      console.log('🔨 Building assets...');
      
      for (let i = 0; i < THEMES.length; i++) {
        const theme = THEMES[i];
        const themeEntry = `src/scss/themes/${theme}/entry/main.scss`;
        const themeEntryPath = path.join(__dirname, themeEntry);
        
        if (!fs.existsSync(themeEntryPath)) {
          console.warn(`  ⚠️  Theme entry not found: ${themeEntry}`);
          continue;
        }
        
        const isFirstTheme = i === 0;
        const isLastTheme = i === THEMES.length - 1;
        const themeConfig = createThemeConfig(theme, isFirstTheme, isLastTheme);
        
        await esbuild.build(themeConfig);
        console.log(`  ✓ Built theme: ${theme}`);
      }
      
      console.log('✅ Build complete!');
    }
  } catch (error) {
    console.error('❌ Build failed:', error);
    process.exit(1);
  }
}

build();