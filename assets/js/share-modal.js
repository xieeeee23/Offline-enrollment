/**
 * Share Modal Functionality
 * This script handles the share modal functionality for the application.
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Find the share button element
    const shareButton = document.getElementById('shareButton');
    
    // Only initialize if the share button exists
    if (shareButton) {
        shareButton.addEventListener('click', function() {
            // Get the current page URL
            const pageUrl = window.location.href;
            
            // Check if Web Share API is supported
            if (navigator.share) {
                navigator.share({
                    title: document.title,
                    url: pageUrl
                })
                .then(() => console.log('Successful share'))
                .catch((error) => console.log('Error sharing:', error));
            } else {
                // Fallback for browsers that don't support the Web Share API
                // Show a modal with copy link functionality
                showShareModal(pageUrl);
            }
        });
    }
    
    // Function to show the share modal
    function showShareModal(url) {
        // Create modal if it doesn't exist
        let modal = document.getElementById('shareModal');
        
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'shareModal';
            modal.className = 'modal fade';
            modal.setAttribute('tabindex', '-1');
            modal.setAttribute('aria-labelledby', 'shareModalLabel');
            modal.setAttribute('aria-hidden', 'true');
            
            modal.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="shareModalLabel">Share This Page</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Copy the link below to share this page:</p>
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" id="shareUrl" value="${url}" readonly>
                                <button class="btn btn-outline-primary" type="button" id="copyLinkBtn">Copy</button>
                            </div>
                            <div class="d-flex justify-content-center mt-3">
                                <a href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}" target="_blank" class="btn btn-outline-primary mx-1">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}" target="_blank" class="btn btn-outline-primary mx-1">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="https://wa.me/?text=${encodeURIComponent(url)}" target="_blank" class="btn btn-outline-primary mx-1">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                                <a href="mailto:?body=${encodeURIComponent(url)}" class="btn btn-outline-primary mx-1">
                                    <i class="fas fa-envelope"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Add copy functionality
            const copyBtn = document.getElementById('copyLinkBtn');
            if (copyBtn) {
                copyBtn.addEventListener('click', function() {
                    const shareUrlInput = document.getElementById('shareUrl');
                    shareUrlInput.select();
                    document.execCommand('copy');
                    
                    // Change button text temporarily
                    const originalText = copyBtn.textContent;
                    copyBtn.textContent = 'Copied!';
                    setTimeout(() => {
                        copyBtn.textContent = originalText;
                    }, 2000);
                });
            }
        }
        
        // Show the modal
        try {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        } catch (error) {
            console.error('Error showing modal:', error);
        }
    }
}); 