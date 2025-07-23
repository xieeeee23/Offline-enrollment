document.addEventListener('DOMContentLoaded', function() {
    // Fix for the Grade Level dropdown
    const gradeLevelSelect = document.getElementById('grade_level');
    if (gradeLevelSelect) {
        // Remove any duplicate "Select Grade Level" options
        const options = gradeLevelSelect.options;
        const values = [];
        
        for (let i = options.length - 1; i >= 0; i--) {
            // Remove any styling
            options[i].style = '';
            
            // Check if this is a duplicate "Select Grade Level" option
            if (options[i].text === "Select Grade Level" && i > 0) {
                gradeLevelSelect.remove(i);
            }
            // Check for other duplicates
            else if (values.includes(options[i].value) && options[i].value !== "") {
                gradeLevelSelect.remove(i);
            } else {
                values.push(options[i].value);
            }
        }
    }
    
    // Fix for the Section dropdown
    const sectionSelect = document.getElementById('section');
    if (sectionSelect) {
        // Remove any duplicate "Select Section" options
        const options = sectionSelect.options;
        const values = [];
        
        for (let i = options.length - 1; i >= 0; i--) {
            // Check if this is a duplicate "Select Section" option
            if (options[i].text === "Select Section" && i > 0) {
                sectionSelect.remove(i);
            }
            // Check for other duplicates
            else if (values.includes(options[i].value) && options[i].value !== "") {
                sectionSelect.remove(i);
            } else {
                values.push(options[i].value);
            }
        }
    }
    
    // Fix for the Education Level dropdown
    const educationLevelSelect = document.getElementById('education_level_id');
    if (educationLevelSelect) {
        // Remove any duplicate "Select Education Level" options
        const options = educationLevelSelect.options;
        const values = [];
        
        for (let i = options.length - 1; i >= 0; i--) {
            // Remove any styling
            options[i].style = '';
            
            // Check if this is a duplicate "Select Education Level" option
            if ((options[i].text === "Select Education Level" || options[i].value === "") && i > 0) {
                educationLevelSelect.remove(i);
            }
            // Check for other duplicates
            else if (values.includes(options[i].value) && options[i].value !== "") {
                educationLevelSelect.remove(i);
            } else {
                values.push(options[i].value);
            }
        }
        
        // Handle education level change
        educationLevelSelect.addEventListener('change', function() {
            // If no education level selected, do nothing more
            if (!this.value) return;
            
            // Get education level name for the AJAX request
            const educationLevelId = this.value;
            const educationLevelText = this.options[this.selectedIndex].text;
            
            // Clear grade level dropdown except first option
            while (gradeLevelSelect.options.length > 1) {
                gradeLevelSelect.remove(1);
            }
            
            // Show loading indicator
            const firstOption = gradeLevelSelect.options[0];
            firstOption.text = "Loading grade levels...";
            
            // Make AJAX request to get grade levels for this education level
            fetch('get_grade_levels.php?education_level_id=' + educationLevelId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    // Reset first option text
                    firstOption.text = "Select Grade Level";
                    
                    // Check if there's an error
                    if (data.error) {
                        console.error('Error loading grade levels:', data.error, data.message);
                        return;
                    }
                    
                    // Check if this is for Kindergarten level
                    const isKinder = educationLevelText.toLowerCase().includes('kinder') || 
                                    educationLevelText.toLowerCase().includes('kindergarten');
                    
                    if (data.min === 'K' || isKinder) {
                        const option = document.createElement('option');
                        option.value = 'K';
                        option.textContent = 'Kindergarten';
                        gradeLevelSelect.appendChild(option);
                        
                        // If this is Kindergarten, pre-select it and disable the section dropdown
                        if (isKinder) {
                            gradeLevelSelect.value = 'K';
                            
                            // Clear and disable section dropdown
                            if (sectionSelect) {
                                sectionSelect.innerHTML = '<option value="">Not applicable for Kindergarten</option>';
                                sectionSelect.disabled = true;
                            }
                        }
                    }
                    
                    const minGrade = data.min === 'K' ? 1 : parseInt(data.min);
                    const maxGrade = parseInt(data.max);
                    
                    for (let i = minGrade; i <= maxGrade; i++) {
                        const option = document.createElement('option');
                        option.value = i.toString();
                        option.textContent = 'Grade ' + i;
                        gradeLevelSelect.appendChild(option);
                    }
                    
                    // Trigger a change event on the grade level dropdown to update sections
                    // Only if not Kindergarten
                    if (!isKinder) {
                        const event = new Event('change');
                        gradeLevelSelect.dispatchEvent(event);
                    }
                })
                .catch(error => {
                    console.error('Error loading grade levels:', error);
                    firstOption.text = "Select Grade Level";
                });
        });
    }
    
    // Fix for any select element with a blue highlight issue
    const allSelects = document.querySelectorAll('select.form-select');
    allSelects.forEach(select => {
        // Check if any option is highlighted with a blue background
        const options = select.querySelectorAll('option');
        options.forEach(option => {
            // Remove any styling
            option.style = '';
            
            // If this is a duplicate of the first option, remove it
            if (option.value === '' && options[0].value === '' && option !== options[0]) {
                option.remove();
            }
        });
    });
    
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