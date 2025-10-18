// Main JavaScript Entry Point
// Import your ES6 modules here

// Example module imports
// import { initNavigation } from './modules/navigation.js';
// import { initAnimations } from './modules/animations.js';

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', () => {
  console.log('🚀 George Woodman Dev - Frontend Initialized');
  
  // Initialize your modules here
  // initNavigation();
  // initAnimations();
});

// Hot Module Replacement (for development)
if (import.meta.hot) {
  import.meta.hot.accept();
}