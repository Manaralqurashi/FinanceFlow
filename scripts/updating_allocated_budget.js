document.addEventListener("DOMContentLoaded", async function () {
  const submitButton = document.getElementById("submit-allocation");
  const editButton = document.getElementById("edit-allocation");
  const allocationTable = document.getElementById("allocation-table");

  // Disable the submit button initially
  submitButton.disabled = true;
  submitButton.classList.add("opacity-50", "cursor-not-allowed");
  const token = localStorage.getItem("authToken");
  try {
    const response = await fetch(
      `${window.location.origin}/api/read_categories_allocated_percentage.php`,
      {
        method: "POST",
        headers: {
          Authorization: "Bearer " + token,
          "Content-Type": "application/json",
        },
      }
    );
    const data = await response.json();

    if (data.message === "Success" && data.data.length > 0) {
      // Update the allocation table with the dynamic data
      const sliders = allocationTable.querySelectorAll(".allocation-slider");
      const percentages = allocationTable.querySelectorAll(".percentage");
      const categories = allocationTable.querySelectorAll("span:last-child");

      data.data.forEach((item, index) => {
        if (sliders[index] && percentages[index] && categories[index]) {
          sliders[index].value = item.allocated_percentage;
          percentages[index].textContent = `${item.allocated_percentage}%`;
          categories[index].textContent = item.category_name;
        }
      });
    }

    // If no data is found, leave the default allocation values
  } catch (error) {
    console.error("Error fetching data:", error);
  }

  // Enable submit button when edit button is clicked
  editButton.addEventListener("click", function () {
    submitButton.disabled = false;
    submitButton.classList.remove("opacity-50", "cursor-not-allowed");

    // Enable sliders for editing
    const sliders = allocationTable.querySelectorAll(".allocation-slider");
    sliders.forEach((slider) => {
      slider.disabled = false;

      // Update percentage dynamically when the slider is adjusted
      slider.addEventListener("input", function () {
        const percentageLabel = this.previousElementSibling;
        percentageLabel.textContent = `${this.value}%`;
      });
    });
  });
});
