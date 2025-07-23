/**
 * This script specifically fixes the Section dropdown issue
 * where "Select Section" appears twice and has a blue highlight
 */
document.addEventListener('DOMContentLoaded', function() {
    // Get the section dropdown
    const sectionDropdown = document.getElementById('section');
    if (!sectionDropdown) return;
    
    // Get the education level and grade level dropdowns
    const educationLevelDropdown = document.getElementById('education_level_id');
    const gradeLevelDropdown = document.getElementById('grade_level');
    
    if (!educationLevelDropdown || !gradeLevelDropdown) return;
    
    // Function to fix the dropdown - removes duplicates
    function fixSectionDropdown() {
        // Remove duplicate options
        const options = sectionDropdown.options;
        const seen = new Set();
        
        for (let i = options.length - 1; i >= 0; i--) {
            // Remove any styling
            options[i].style = '';
            
            // If this is a duplicate option with empty value, remove it
            if ((options[i].value === '' && seen.has('')) || 
                (options[i].value !== '' && seen.has(options[i].value))) {
                sectionDropdown.remove(i);
            } else {
                seen.add(options[i].value);
            }
        }
        
        // Ensure first option is "Select Section" if applicable
        if (options.length > 0 && options[0].value === '') {
            options[0].text = 'Select Section';
        }
    }
    
    // Also fix the grade level dropdown
    function fixGradeLevelDropdown() {
        // Remove duplicate options
        const options = gradeLevelDropdown.options;
        const seen = new Set();
        
        for (let i = options.length - 1; i >= 0; i--) {
            // Remove any styling
            options[i].style = '';
            
            // If this is a duplicate option with empty value or duplicate value, remove it
            if ((options[i].value === '' && seen.has('')) || 
                (options[i].value !== '' && seen.has(options[i].value))) {
                gradeLevelDropdown.remove(i);
            } else {
                seen.add(options[i].value);
            }
        }
        
        // Ensure first option is "Select Grade Level" if applicable
        if (options.length > 0 && options[0].value === '') {
            options[0].text = 'Select Grade Level';
        }
    }
    
    // Function to load sections based on education level and grade level
    function loadSections() {
        // Clear section dropdown
        sectionDropdown.innerHTML = '<option value="">Select Section</option>';
        sectionDropdown.disabled = true;
        
        // If no grade level selected, do nothing more
        if (!gradeLevelDropdown.value) return;
        
        // If no education level selected, alert user
        if (!educationLevelDropdown.value) {
            alert('Please select an Education Level first');
            gradeLevelDropdown.value = '';
            return;
        }
        
        // Get education level name
        const educationLevelName = educationLevelDropdown.options[educationLevelDropdown.selectedIndex].text;
        
        // Check if this is Kindergarten
        const isKinder = educationLevelName.toLowerCase().includes('kinder') || 
                        educationLevelName.toLowerCase().includes('kindergarten') ||
                        gradeLevelDropdown.value === 'K';
        
        if (isKinder) {
            sectionDropdown.innerHTML = '<option value="">Not applicable for Kindergarten</option>';
            sectionDropdown.disabled = true;
            return;
        }
        
        // Show loading indicator
        sectionDropdown.innerHTML = '<option value="">Loading sections...</option>';
        
        // Make AJAX request to get sections
        fetch('get_sections.php?education_level=' + encodeURIComponent(educationLevelName) + 
             '&grade_level=' + encodeURIComponent(gradeLevelDropdown.value))
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Check if there's an error
                if (data.error) {
                    console.error('Error loading sections:', data.error, data.message);
                    sectionDropdown.innerHTML = '<option value="">Error: ' + data.message + '</option>';
                    return;
                }
                
                // Clear dropdown and add default option
                sectionDropdown.innerHTML = '<option value="">Select Section</option>';
                
                // If no sections found
                if (data.length === 0) {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'No sections found';
                    option.disabled = true;
                    sectionDropdown.appendChild(option);
                } else {
                    // Add sections to dropdown
                    data.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section.name;
                        option.textContent = section.name + (section.room ? ' (' + section.room + ')' : '');
                        sectionDropdown.appendChild(option);
                    });
                }
                
                // Enable dropdown
                sectionDropdown.disabled = false;
                
                // Fix any duplicate options
                fixSectionDropdown();
            })
            .catch(error => {
                console.error('Error loading sections:', error);
                sectionDropdown.innerHTML = '<option value="">Error loading sections</option>';
            });
    }
    
    // Initialize on page load
    fixSectionDropdown();
    fixGradeLevelDropdown();
    
    // If both education level and grade level are already selected, load sections
    if (educationLevelDropdown.value && gradeLevelDropdown.value) {
        loadSections();
    }
    
    // Fix when the dropdown is clicked
    sectionDropdown.addEventListener('click', function() {
        setTimeout(fixSectionDropdown, 10);
    });
    
    // Fix when the dropdown changes
    sectionDropdown.addEventListener('change', function() {
        setTimeout(fixSectionDropdown, 10);
    });
    
    // Fix grade level dropdown when clicked or changed
    gradeLevelDropdown.addEventListener('click', function() {
        setTimeout(fixGradeLevelDropdown, 10);
    });
    
    gradeLevelDropdown.addEventListener('change', function() {
        setTimeout(fixGradeLevelDropdown, 10);
        loadSections();
    });
    
    // Clear grade level and section when education level changes
    educationLevelDropdown.addEventListener('change', function() {
        gradeLevelDropdown.value = '';
        sectionDropdown.innerHTML = '<option value="">Select Grade Level First</option>';
        sectionDropdown.disabled = true;
    });
    
    // Add a mutation observer to detect when options are added to the section dropdown
    const observeSection = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                fixSectionDropdown();
            }
        });
    });
    
    observeSection.observe(sectionDropdown, { childList: true });
    
    // Add a mutation observer for grade level dropdown
    const observeGrade = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                fixGradeLevelDropdown();
            }
        });
    });
    
    observeGrade.observe(gradeLevelDropdown, { childList: true });
    
    // Add CSS to ensure no blue highlight
    const style = document.createElement('style');
    style.textContent = `
        .form-select option {
            background-color: white !important;
            color: black !important;
        }
        .form-select option:checked {
            background-color: #f8f9fa !important;
            color: black !important;
        }
    `;
    document.head.appendChild(style);
}); 