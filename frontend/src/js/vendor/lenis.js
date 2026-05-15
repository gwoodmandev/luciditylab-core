import Lenis from 'lenis'
import 'lenis/dist/lenis.css'

// Initialize Lenis
export function init() {
  window.lenis = new Lenis({
    autoRaf: true,
    duration: 0.75,
    allowNestedScroll: true,
  });

  // Listen for the scroll event and log the event data
  window.lenis.on('scroll', (e) => {
    //console.log(e);
  });
}