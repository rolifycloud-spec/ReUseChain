document.addEventListener("DOMContentLoaded", function () {

    initAll();

    // fallback za Elementor lazy load
    setTimeout(initAll, 500);
    setTimeout(initAll, 1000);
});

function initAll() {
    document.querySelectorAll(".rc-gauge-v3").forEach(initGauge);
}

function initGauge(gauge) {

    const maxEl = document.querySelector(".jet-listing-dynamic-field__content");
    const qtyInput = document.querySelector("input.qty");

    if (!maxEl || !gauge) return;

    const fill = gauge.querySelector(".rc-fill");
    const currentEl = gauge.querySelector(".rc-current");
    const maxText = gauge.querySelector(".rc-max");

    if (!fill || !currentEl || !maxText) return;

    const max = parseFloat(maxEl.innerText) || 0;

    function update() {

        let current = qtyInput ? parseFloat(qtyInput.value) : 1;

        if (isNaN(current)) current = 1;

        let percent = max > 0 ? (current / max) * 100 : 0;
        percent = Math.max(0, Math.min(100, percent));

        fill.style.width = percent + "%";

        currentEl.innerText = current;
        maxText.innerText = max;
    }

    update();

    if (qtyInput) {
        qtyInput.addEventListener("input", update);
    }

    document.querySelectorAll(".qty-plus, .qty-minus").forEach(btn => {
        btn.addEventListener("click", function () {
            setTimeout(update, 50);
        });
    });
}