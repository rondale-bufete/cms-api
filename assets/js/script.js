document.addEventListener('DOMContentLoaded', function() {
    // Enable Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // File upload preview
    const fileInput = document.getElementById('file');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const fileNameDisplay = document.getElementById('file-name');
            if (this.files && this.files[0]) {
                const fileName = this.files[0].name;
                const fileSize = (this.files[0].size / (1024 * 1024)).toFixed(2); // Size in MB
                fileNameDisplay.innerHTML = `Selected file: <strong>${fileName}</strong> (${fileSize} MB)`;
                
                // Update the file type based on the extension
                const fileExtension = fileName.split('.').pop().toLowerCase();
                const fileTypeSelect = document.getElementById('file_type');
                if (fileTypeSelect && (fileExtension === 'obj' || fileExtension === 'mtl' || fileExtension === 'glb')) {
                    for (let i = 0; i < fileTypeSelect.options.length; i++) {
                        if (fileTypeSelect.options[i].value === fileExtension) {
                            fileTypeSelect.selectedIndex = i;
                            break;
                        }
                    }
                }
            } else {
                fileNameDisplay.innerHTML = 'No file selected';
            }
        });
    }
    
    // Related files form handling
    const addRelatedFileBtn = document.getElementById('add-related-file');
    if (addRelatedFileBtn) {
        let relatedFileCount = 0;
        addRelatedFileBtn.addEventListener('click', function() {
            const relatedFilesContainer = document.getElementById('related-files-container');
            relatedFileCount++;
            
            const html = `
                <div class="mb-3 related-file-group" id="related-file-group-${relatedFileCount}">
                    <div class="input-group">
                        <input type="file" class="form-control" name="related_files[]" required>
                        <button type="button" class="btn btn-danger remove-related-file" data-id="${relatedFileCount}">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            
            relatedFilesContainer.insertAdjacentHTML('beforeend', html);
            
            // Add event listener to remove button
            const removeBtn = document.querySelector(`.remove-related-file[data-id="${relatedFileCount}"]`);
            removeBtn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const group = document.getElementById(`related-file-group-${id}`);
                group.remove();
            });
        });
    }
    
    // Search functionality
    const searchForm = document.getElementById('search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const searchTerm = document.getElementById('search-input').value;
            window.location.href = `dashboard.php?search=${encodeURIComponent(searchTerm)}`;
        });
    }
    
    // Confirmation for delete actions
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });
    
    // AJAX for API requests (used in dashboard)
    const apiDeleteButtons = document.querySelectorAll('.api-delete-btn');
    apiDeleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const objectId = this.getAttribute('data-id');
            
            if (confirm('Are you sure you want to delete this object?')) {
                fetch(`api/objects.php?id=${objectId}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Remove the card from the UI
                        const card = this.closest('.card');
                        card.remove();
                        
                        // Show success message
                        const alertContainer = document.createElement('div');
                        alertContainer.className = 'alert alert-success alert-dismissible fade show';
                        alertContainer.role = 'alert';
                        alertContainer.innerHTML = `
                            ${data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                        
                        document.querySelector('main.container').prepend(alertContainer);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the object.');
                });
            }
        });
    });
});
