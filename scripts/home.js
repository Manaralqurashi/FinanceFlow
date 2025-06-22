document.addEventListener("DOMContentLoaded", async function () {
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

    if (data.message === "Success" || data.data.length > 0) {
      // Hide the first div (waiting for budget setup)
      const firstDiv = document.querySelector(".first-div");
      firstDiv.style.display = "none";

      // Show the second div with dynamic content
      const secondDiv = document.querySelector(".second-div");
      secondDiv.classList.remove("hidden");

      // Set total budget
      const totalBudgetInput = secondDiv.querySelector('input[type="number"]');
      totalBudgetInput.value = data.total_budget;

      // // Generate dynamic category content
      // const categoryContainer = secondDiv.querySelectorAll(".mb-6");
      // const template = categoryContainer[0].cloneNode(true); // Use the existing template

      // // Clear previous categories if any (except the first one)
      // categoryContainer.forEach((item, index) => {
      //   if (index > 0) {
      //     item.remove();
      //   }
      // });

      // // Add categories dynamically
      // data.data.forEach((item, index) => {
      //   let categoryElement;
      //   if (index === 0) {
      //     // For the first category, reuse the existing element
      //     categoryElement = categoryContainer[0];
      //   } else {
      //     // For other categories, clone the template
      //     categoryElement = template.cloneNode(true);
      //     secondDiv.appendChild(categoryElement);
      //   }

      //   // Set category name and percentage
      //   const selectInput = categoryElement.querySelector("select");
      //   const rangeInput = categoryElement.querySelector('input[type="range"]');
      //   const percentageLabel = categoryElement.querySelector("span");

      //   // Update select options and selected value
      //   selectInput.innerHTML = `<option value="${item.category_name}">${item.category_name}</option>`;
      //   rangeInput.value = item.allocated_percentage;
      //   percentageLabel.textContent = `${item.allocated_percentage}%`;
      // });
    } else {
      // No data found, show the default "budget is waiting" message
      document.querySelector(".second-div").style.display = "none";
    }
  } catch (error) {
    console.error("Error fetching data:", error);
  }
});
