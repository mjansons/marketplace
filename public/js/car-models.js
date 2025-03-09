document.addEventListener('DOMContentLoaded', function () {
    const brandField = document.querySelector('.brand-selector');
    const modelField = document.querySelector('.model-selector');

    if (brandField && modelField) {
        brandField.addEventListener('change', function () {
            const selectedBrand = brandField.value;

            modelField.innerHTML = '<option value="">Choose a model</option>';
            if (!selectedBrand) return;

            fetch('/get-models', {  // Make sure this matches the Symfony route
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ brand: selectedBrand })
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(models => {
                    if (models.error) {
                        console.error(models.error);
                        return;
                    }
                    models.forEach(modelName => {
                        let option = new Option(modelName, modelName);
                        modelField.appendChild(option);
                    });
                })
                .catch(error => console.error("Error fetching models:", error));
        });
    }
});
