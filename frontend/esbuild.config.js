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

// Automatically detect themes or manually define them
function getThemes() {
  const themesPath = path.join(__dirname, themesDir);
  
  // Check if themes directory exists
  if (!fs.existsSync(themesPath)) {
    console.warn(`⚠️  Themes directory not found: ${themesPath}`);
    return [];
  }
  
  // Read all directories in the themes folder
  const themes = fs.readdirSync(themesPath, { withFileTypes: true })
    .filter(dirent => dirent.isDirectory())
    .map(dirent => dirent.name);
  
  return themes;
}

// Get themes automatically
const themes = getThemes();

console.log(`🎨 Found themes: ${themes.join(', ')}`);

// Build entry points dynamically
function buildEntryPoints() {
  const entryPoints = {
    'js/main': 'src/js/main.js'
  };
  
  // Add theme entry points
  themes.forEach(theme => {
    const themeEntry = `src/scss/themes/${theme}/entry/main.scss`;
    const themeEntryPath = path.join(__dirname, themeEntry);
    
    // Check if the theme entry file exists
    if (fs.existsSync(themeEntryPath)) {
      entryPoints[`css/themes/${theme}/main`] = themeEntry;
      console.log(`  ✓ Added theme: ${theme}`);
    } else {
      console.warn(`  ⚠️  Theme entry not found: ${themeEntry}`);
    }
  });
  
  return entryPoints;
}

const config = {
  entryPoints: buildEntryPoints(),
  bundle: true,
  outdir: distDir,
  format: 'esm',
  splitting: true,
  sourcemap: !isProduction,
  minify: isProduction,
  target: ['es2020', 'chrome90', 'firefox88', 'safari14'],
  
  plugins: [
    // Clean dist directory before build
    clean({
      patterns: [distDir]
    }),
    
    // SASS/SCSS compilation
    sassPlugin({
      outputStyle: isProduction ? 'compressed' : 'expanded'
    }),
    
    // Copy static assets
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

async function copyToCraft() {
  console.log('📦 Copying assets to Craft CMS...');
  
  // Ensure Craft assets directory exists
  if (!fs.existsSync(craftAssetsDir)) {
    fs.mkdirSync(craftAssetsDir, { recursive: true });
  }
  
  // Copy dist to Craft web/assets
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

async function build() {
  try {
    if (isWatch) {
      console.log('👀 Starting ESBuild in watch mode...');
      
      let isBuilding = false;
      let shouldCopyAfterBuild = false;
      
      const ctx = await esbuild.context({
        ...config,
        plugins: [
          ...config.plugins,
          {
            name: 'copy-to-craft',
            setup(build) {
              build.onStart(() => {
                isBuilding = true;
              });
              
              build.onEnd(async (result) => {
                isBuilding = false;
                if (result.errors.length === 0) {
                  await copyToCraft();
                }
              });
            }
          }
        ]
      });
      
      await ctx.watch();
      
      console.log('✅ Watch mode active. Press Ctrl+C to stop.');
    } else {
      console.log('🔨 Building assets...');
      await esbuild.build(config);
      await copyToCraft();
      console.log('✅ Build complete!');
    }
  } catch (error) {
    console.error('❌ Build failed:', error);
    process.exit(1);
  }
}

build();