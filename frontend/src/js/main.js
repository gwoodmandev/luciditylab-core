// Main JavaScript Entry Point
// Import your ES6 modules here

// Example module imports
import { init as initLenis } from './vendor/lenis.js';
import { init as initNavigation } from './modules/navigation.js';
import { init as initAnimate } from './functions/animate.js';

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', () => {
  console.log('🚀 George Woodman Dev - Frontend Initialized');

  // Initialize your modules here
  initLenis();
  initNavigation();
  initAnimate();
});