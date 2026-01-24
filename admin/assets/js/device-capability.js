/**
 * Device Capability Detection & Animation Preference Manager
 * Detects low-capacity/mobile devices and respects user animation preferences
 */

(function() {
  'use strict';

  const STORAGE_KEY = 'hq_admin_animation_preference';
  const CLASS_NAME = 'hq-minimal-animations';
  const CLASS_FULL = 'hq-full-animations';

  /**
   * Check if device is low-capacity based on multiple criteria
   */
  function isLowCapacityDevice() {
    // 1. Check if mobile/tablet
    const isMobile = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(
      navigator.userAgent.toLowerCase()
    );

    if (!isMobile) return false;

    // 2. Check screen size (small screens = lower capacity usually)
    const isSmallScreen = window.innerWidth < 768;

    // 3. Check device memory (if available)
    let lowMemory = false;
    if (navigator.deviceMemory) {
      lowMemory = navigator.deviceMemory <= 4; // 4GB or less
    }

    // 4. Check if in low-power mode or reduce motion preference
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // 5. Check connection speed (if available)
    let slowConnection = false;
    if (navigator.connection) {
      const effectiveType = navigator.connection.effectiveType;
      slowConnection = effectiveType === '2g' || effectiveType === '3g' || effectiveType === '4g';
    }

    return (isMobile && isSmallScreen) || lowMemory || reducedMotion || slowConnection;
  }

  /**
   * Get user's animation preference from storage
   * Returns: 'auto' (default), 'full', or 'minimal'
   */
  function getAnimationPreference() {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored) return stored;

    // Auto-detect on first visit
    return isLowCapacityDevice() ? 'minimal' : 'auto';
  }

  /**
   * Set animation preference
   */
  function setAnimationPreference(preference) {
    if (['auto', 'full', 'minimal'].includes(preference)) {
      localStorage.setItem(STORAGE_KEY, preference);
      applyAnimationPreference();
      return true;
    }
    return false;
  }

  /**
   * Apply animation preference to DOM
   */
  function applyAnimationPreference() {
    const preference = getAnimationPreference();
    const html = document.documentElement;
    const isLowCapacity = isLowCapacityDevice();

    // Determine if minimal animations should be applied
    let useMinimal = false;
    if (preference === 'minimal') {
      useMinimal = true;
    } else if (preference === 'auto' && isLowCapacity) {
      useMinimal = true;
    }

    // Apply classes
    html.classList.remove(CLASS_FULL, CLASS_NAME);
    if (useMinimal) {
      html.classList.add(CLASS_NAME);
    } else {
      html.classList.add(CLASS_FULL);
    }

    // Set data attribute for CSS hooks
    html.setAttribute('data-animation-mode', useMinimal ? 'minimal' : 'full');
  }

  /**
   * SweetAlert configuration based on device capability
   */
  function getSweetAlertConfig() {
    const preference = getAnimationPreference();
    const isLowCapacity = isLowCapacityDevice();

    let useMinimal = false;
    if (preference === 'minimal') {
      useMinimal = true;
    } else if (preference === 'auto' && isLowCapacity) {
      useMinimal = true;
    }

    if (useMinimal) {
      return {
        allowOutsideClick: false,
        didOpen: function(modal) {
          modal.style.animation = 'none';
        },
        customClass: {
          popup: 'hq-swal-minimal',
          title: 'swal-title-minimal',
          htmlContainer: 'swal-content-minimal',
          confirmButton: 'swal-button-minimal',
          cancelButton: 'swal-button-cancel-minimal',
        },
        showClass: {
          popup: ''
        },
        hideClass: {
          popup: ''
        }
      };
    }

    return {
      customClass: {
        popup: 'hq-swal',
      },
      showClass: {
        popup: 'fadeIn'
      },
      hideClass: {
        popup: 'fadeOut'
      }
    };
  }

  /**
   * Expose public API
   */
  window.HQDeviceCapability = {
    isLowCapacityDevice,
    getAnimationPreference,
    setAnimationPreference,
    applyAnimationPreference,
    getSweetAlertConfig,
    getPreference: getAnimationPreference,
    setPreference: setAnimationPreference
  };

  /**
   * Initialize on DOM ready
   */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applyAnimationPreference);
  } else {
    applyAnimationPreference();
  }
})();
