// Form Submit Handler - Disable empty rows before submission
document.querySelector('form').addEventListener('submit', function(e) {
    const rows = document.querySelectorAll('#itemsTable tbody tr');
    
    rows.forEach(row => {
        const productId = row.querySelector('.selected-product-id');
        const batchId = row.querySelector('.batch-id');
        
        // If row has no product selected, disable all inputs in that row
        if (!productId || !productId.value || !batchId || !batchId.value) {
            row.querySelectorAll('input, select, button').forEach(input => {
                input.disabled = true;
            });
        }
    });
    
    // Allow form to submit
    return true;
});

// Initialize: Add 5 rows on page load
window.addEventListener('DOMContentLoaded', () => {
    for (let i = 0; i < 5; i++) {
        addRow();
    }
});
