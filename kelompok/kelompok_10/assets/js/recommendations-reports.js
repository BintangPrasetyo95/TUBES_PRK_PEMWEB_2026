document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | Validasi Form Simulasi Diskon
    |--------------------------------------------------------------------------
    */

    const discountForm = document.querySelector(
        'form[data-form="discount"]'
    );

    if (discountForm) {
        discountForm.addEventListener('submit', function (event) {
            const discountInput = discountForm.querySelector(
                '[name="discount_percent"]'
            );

            const discount = Number(discountInput.value);

            if (
                discountInput.value.trim() === '' ||
                !Number.isFinite(discount) ||
                discount < 0 ||
                discount > 100
            ) {
                event.preventDefault();

                alert(
                    'Persentase diskon harus berupa angka antara 0 sampai 100%.'
                );

                discountInput.focus();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Validasi Form Rekomendasi Restock
    |--------------------------------------------------------------------------
    */

    const restockForm = document.querySelector(
        'form[data-form="restock"]'
    );

    if (restockForm) {
        restockForm.addEventListener('submit', function (event) {
            const numberInputs = restockForm.querySelectorAll(
                'input[type="number"]'
            );

            for (const input of numberInputs) {
                const value = Number(input.value);

                if (
                    input.value.trim() === '' ||
                    !Number.isFinite(value) ||
                    value < 0
                ) {
                    event.preventDefault();

                    alert(
                        'Pastikan semua input restock berisi angka yang valid dan tidak negatif.'
                    );

                    input.focus();
                    return;
                }
            }
        });
    }

});
