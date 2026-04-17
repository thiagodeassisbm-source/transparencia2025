/**
 * Masks system for Transparência 2026
 * Handles monetary and other field masks.
 */

document.addEventListener('DOMContentLoaded', function() {
    initMoneyMasks();
});

/**
 * Initializes monetary masks on inputs with the 'money-mask' class
 */
function initMoneyMasks() {
    const moneyInputs = document.querySelectorAll('.money-mask');
    
    moneyInputs.forEach(input => {
        // Format initial value if exists
        if (input.value) {
            input.value = formatMoney(input.value);
        }

        input.addEventListener('input', function(e) {
            let value = e.target.value;
            
            // Remove everything except numbers
            value = value.replace(/\D/g, "");
            
            // Format to 0,00
            if (value.length > 2) {
                value = value.replace(/(\d+)(\d{2})$/, "$1,$2");
            } else if (value.length === 2) {
                value = "0," + value;
            } else if (value.length === 1) {
                value = "0,0" + value;
            } else {
                value = "0,00";
            }
            
            // Add thousand separator
            if (value.length > 6) {
                let parts = value.split(',');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                value = parts.join(',');
            }
            
            e.target.value = value;
        });

        // Prevention for non-numeric keys and better UX
        input.addEventListener('keydown', function(e) {
            if (e.key === '.' || e.key === ',') {
                e.preventDefault();
            }
        });
    });
}

/**
 * Helper to format a numeric string or number to monetary format (BRL)
 */
function formatMoney(value) {
    if (!value || value === "") return "0,00";
    
    // Convert to string and clean up if it's already BRL format to get a clean number
    let cleanValue = value.toString().replace(/\./g, '').replace(',', '.');
    let num = parseFloat(cleanValue);
    
    if (isNaN(num)) {
        // Fallback: maybe it's already a clean decimal string from DB?
        num = parseFloat(value);
    }
    
    if (isNaN(num)) return "0,00";
    
    return num.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
