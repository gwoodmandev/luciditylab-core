// ============================================================================== //
// animate function
// ============================================================================== //

export function init() {
  animate();
}

function animate() {
  console.log('Init Animate');
  parallax.init();
}

const parallax = (() => {
  // All elements currently inside the viewport
  const visible = new Set();

  // Shared rAF loop — only runs when elements are visible
  let rafId = null;

  function tick() {
    const scrollY = window.scrollY;
    const scrollX = window.scrollX;

    for (const {
        el,
        speed,
        direction
      } of visible) {
      const rect = el.getBoundingClientRect();

      // Centre of the element relative to the middle of the viewport
      const viewportCentreY = window.innerHeight / 2;
      const viewportCentreX = window.innerWidth / 2;

      const offsetY = (rect.top + rect.height / 2 - viewportCentreY) * (speed - 1);
      const offsetX = (rect.left + rect.width / 2 - viewportCentreX) * (speed - 1);

      if (direction === "x") {
        el.style.transform = `translateX(${offsetX}px)`;
      } else {
        el.style.transform = `translateY(${offsetY}px)`;
      }
    }

    rafId = visible.size > 0 ? requestAnimationFrame(tick) : null;
  }

  function startLoop() {
    if (!rafId) rafId = requestAnimationFrame(tick);
  }

  function stopLoop() {
    if (rafId) {
      cancelAnimationFrame(rafId);
      rafId = null;
    }
  }

  function init(root = document) {
    const elements = root.querySelectorAll("[data-speed]");
    if (!elements.length) return;

    const observer = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) {
          const el = entry.target;
          const speed = parseFloat(el.dataset.speed) || 1;
          const direction = (el.dataset.direction || "y").toLowerCase();

          if (entry.isIntersecting) {
            visible.add({
              el,
              speed,
              direction
            });
            startLoop();
          } else {
            // Remove from Set by reference — find and delete the matching entry
            for (const item of visible) {
              if (item.el === el) {
                visible.delete(item);
                break;
              }
            }
            if (visible.size === 0) stopLoop();
          }
        }
      }, {
        // Slightly oversized root margin so parallax begins just before
        // the element scrolls into view, avoiding a pop-in effect.
        rootMargin: "10% 0px",
      }
    );

    elements.forEach((el) => {
      // Prevent layout shifts — the host element should handle overflow hidden
      // if the parallax movement would escape its bounds.
      el.style.willChange = "transform";
      observer.observe(el);
    });

    return observer; // Return so callers can disconnect() if needed
  }

  // Handle reduced-motion preference — skip all transforms
  const prefersReducedMotion = window.matchMedia(
    "(prefers-reduced-motion: reduce)"
  );

  if (prefersReducedMotion.matches) {
    return {
      init: () => {}
    }; // No-op
  }

  return {
    init
  };
})();