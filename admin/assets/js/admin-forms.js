// Common form handling and alerts for admin panel
function initAdminForms() {
    // Handle delete confirmations
    document.querySelectorAll('form[action*="delete"]').forEach(form => {
        form.onsubmit = async (e) => {
            e.preventDefault();
            const result = await Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            });

            if (result.isConfirmed) {
                form.submit();
            }
        };
    });

    // Handle general form submissions
    document.querySelectorAll('form:not([action*="delete"])').forEach(form => {
        if (form.classList.contains('search-form')) return; // Skip search forms
        
        form.onsubmit = async (e) => {
            e.preventDefault();
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            }

            try {
                const formData = new FormData(form);
                const response = await fetch(form.action || window.location.href, {
                    method: form.method || 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    await Swal.fire({
                        title: 'Success!',
                        text: data.message || 'Operation completed successfully',
                        icon: 'success'
                    });
                    
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else if (!data.preventReload) {
                        window.location.reload();
                    }
                } else {
                    throw new Error(data.message || 'Operation failed');
                }
            } catch (error) {
                await Swal.fire({
                    title: 'Error!',
                    text: error.message || 'Something went wrong',
                    icon: 'error'
                });
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = submitBtn.dataset.originalText || 'Submit';
                }
            }
        };
    });

    // Store original button text
    document.querySelectorAll('button[type="submit"]').forEach(btn => {
        btn.dataset.originalText = btn.innerHTML;
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', initAdminForms);

// Function to show loading state
function showLoading(message = 'Processing...') {
    Swal.fire({
        title: message,
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });
}

// Function to show success message
function showSuccess(message, callback = null) {
    Swal.fire({
        title: 'Success!',
        text: message,
        icon: 'success'
    }).then(() => {
        if (callback) callback();
    });
}

// Function to show error message
function showError(message) {
    Swal.fire({
        title: 'Error!',
        text: message,
        icon: 'error'
    });
}

// Function to show confirmation dialog
async function confirmAction(title, text) {
    const result = await Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes',
        cancelButtonText: 'Cancel'
    });
    
    return result.isConfirmed;
}