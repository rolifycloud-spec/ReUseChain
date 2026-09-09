jQuery(document).ready(function($) {
    function updateTotalPrice() {
        var unitPriceText = $('.woocommerce-Price-amount').first().text().replace(/[^0-9,.]/g, '');
        var unitPrice = parseFloat(unitPriceText.replace(',', '.')); // Handle different currency formats
        var quantity = $('input.qty').val();
        if (!quantity || quantity < 1) quantity = 1; // Ensure at least 1 quantity

        var totalPrice = (unitPrice * quantity).toFixed(2);

        // Update the displayed total price dynamically (Appending " KM")
        $('.custom-total-price').html(totalPrice + ' KM');
    }

    // Update price when quantity input changes
    $(document).on('input change', 'input.qty', function() {
        updateTotalPrice();
    });

    // Set the correct total price on page load
    updateTotalPrice();
});