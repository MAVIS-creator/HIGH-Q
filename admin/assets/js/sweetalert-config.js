/**
 * Global SweetAlert2 Theme Configuration
 * Customizes all SweetAlert2 popups with HIGH-Q brand colors
 */

// Set global defaults for SweetAlert2 with device capability awareness
if (typeof SwalHighQ === 'undefined') {
  const baseConfig = {
    customClass: {
      popup: 'highq-popup',
      confirmButton: 'highq-confirm-btn',
      cancelButton: 'highq-cancel-btn',
      title: 'highq-title',
      htmlContainer: 'highq-content'
    },
    buttonsStyling: false,
    confirmButtonText: 'Confirm',
    cancelButtonText: 'Cancel',
    showClass: {
      popup: 'swal2-show',
      backdrop: 'swal2-backdrop-show',
      icon: 'swal2-icon-show'
    },
    hideClass: {
      popup: 'swal2-hide',
      backdrop: 'swal2-backdrop-hide',
      icon: 'swal2-icon-hide'
    }
  };

  // Merge with minimal config when needed
  let capabilityConfig = {};
  if (window.HQDeviceCapability && typeof window.HQDeviceCapability.getSweetAlertConfig === 'function') {
    capabilityConfig = window.HQDeviceCapability.getSweetAlertConfig() || {};
  }

  const mergedConfig = {
    ...baseConfig,
    ...capabilityConfig,
    customClass: {
      ...(baseConfig.customClass || {}),
      ...(capabilityConfig.customClass || {})
    },
    showClass: {
      ...(baseConfig.showClass || {}),
      ...(capabilityConfig.showClass || {})
    },
    hideClass: {
      ...(baseConfig.hideClass || {}),
      ...(capabilityConfig.hideClass || {})
    }
  };

  const SwalHighQ = Swal.mixin(mergedConfig);

// Add custom styles dynamically
if (!document.getElementById('highq-swal-styles')) {
  const style = document.createElement('style');
  style.id = 'highq-swal-styles';
  style.textContent = `
    /* HIGH-Q SweetAlert2 Custom Theme */
    .highq-popup {
      border-radius: 16px !important;
      padding: 2rem !important;
      box-shadow: 0 10px 40px rgba(0,0,0,0.15) !important;
    }

    .highq-title {
      color: #111 !important;
      font-weight: 700 !important;
      font-size: 1.75rem !important;
      margin-bottom: 0.5rem !important;
    }

    .highq-content {
      color: #555 !important;
      font-size: 1rem !important;
      line-height: 1.6 !important;
    }

    .highq-confirm-btn {
      background: linear-gradient(180deg, #ffd24d, #f6c23a) !important;
      color: #111 !important;
      border: none !important;
      padding: 12px 32px !important;
      border-radius: 10px !important;
      font-weight: 600 !important;
      font-size: 1rem !important;
      cursor: pointer !important;
      box-shadow: 0 4px 12px rgba(246,194,58,0.25) !important;
      transition: all 0.2s ease !important;
    }

    .highq-confirm-btn:hover {
      filter: brightness(0.98) !important;
      transform: translateY(-1px) !important;
      box-shadow: 0 6px 16px rgba(246,194,58,0.35) !important;
    }

    .highq-cancel-btn {
      background: #fff !important;
      color: #666 !important;
      border: 2px solid #ddd !important;
      padding: 12px 32px !important;
      border-radius: 10px !important;
      font-weight: 600 !important;
      font-size: 1rem !important;
      cursor: pointer !important;
      transition: all 0.2s ease !important;
    }

    .highq-cancel-btn:hover {
      background: #f8f8f8 !important;
      border-color: #ccc !important;
    }

    /* Icon colors */
    .swal2-success .swal2-success-ring {
      border-color: rgba(246,194,58,0.3) !important;
    }

    .swal2-success .swal2-success-line-tip,
    .swal2-success .swal2-success-line-long {
      background-color: #f6c23a !important;
    }

    .swal2-error .swal2-x-mark-line-left,
    .swal2-error .swal2-x-mark-line-right {
      background-color: #ff4b2b !important;
    }

    .swal2-warning {
      border-color: #ffd600 !important;
      color: #ffd600 !important;
    }

    .swal2-info {
      border-color: #3498db !important;
      color: #3498db !important;
    }

    /* Animation improvements */
    .swal2-show {
      animation: swal2-show 0.25s ease-out !important;
    }

    .swal2-hide {
      animation: swal2-hide 0.2s ease-in !important;
    }

    /* Backdrop */
    .swal2-backdrop-show {
      background: rgba(0,0,0,0.5) !important;
    }
  `;
  document.head.appendChild(style);
}

// Replace global Swal with customized version
if (typeof Swal !== 'undefined') {
  window.Swal = SwalHighQ;
}
} // Close the typeof SwalHighQ check
