document.addEventListener("DOMContentLoaded", function () {
  const sliders = document.querySelectorAll(".allocation-slider");
  const totalAllocation = 100;

  sliders.forEach((slider) => {
    slider.addEventListener("input", updateAllocation);
  });

  function updateAllocation() {
    let total = 0;

    // Calculate the total allocation based on the current slider values
    sliders.forEach((slider) => {
      total += parseInt(slider.value);
    });

    // If the total is over 100, adjust other sliders proportionally
    if (total > totalAllocation) {
      let excess = total - totalAllocation;
      sliders.forEach((slider) => {
        if (slider !== this) {
          let currentVal = parseInt(slider.value);
          let adjustment = Math.min(excess, currentVal);
          slider.value = currentVal - adjustment;
          excess -= adjustment;
          if (excess <= 0) return;
        }
      });
    }

    // Update percentage labels dynamically
    sliders.forEach((slider) => {
      const percentageLabel = slider.previousElementSibling;
      percentageLabel.textContent = `${slider.value}%`;
    });
  }
});
