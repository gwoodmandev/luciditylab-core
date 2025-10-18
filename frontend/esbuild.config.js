import * as esbuild from 'esbuild';
import { sassPlugin } from 'esbuild-sass-plugin';
import { copy } from 'esbuild-plugin-copy';
import { clean } from 'esbuild-plugin-clean';
import * as fs from 'fs';
import * as path from 'path';

const isProduction = process.env.NODE_ENV === 'production';
const isWatch = process.argv.includes('--watch');

// Paths
const distDir = './dist';
const craftAssetsDir = '../craftcms/web/assets';

const config = {
  entryPoints: [
    'src/js/main.js',
    'src/scss/main.scss'
  ],
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
      
      const ctx = await esbuild.context(config);
      
      await ctx.watch();
      
      // Initial copy to Craft
      await copyToCraft();
      
      // Watch for changes and copy to Craft
      console.log('👀 Watching for file changes...');
      
      // Simple file watcher for dist changes
      fs.watch(distDir, { recursive: true }, async (eventType, filename) => {
        if (filename) {
          console.log(`📝 File changed: ${filename}`);
          await copyToCraft();
        }
      });
      
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