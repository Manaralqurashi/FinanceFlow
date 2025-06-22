document.addEventListener("DOMContentLoaded", () => {
  const submitButton = document.getElementById("submit-budget-btn");
  const totalBudgetInput = document.getElementById("total-budget");
  const editButton = document.getElementById("edit-budget-btn");
  const allocationSubmitButton = document.getElementById("submit-allocation");
  const sliders = document.querySelectorAll(".allocation-slider");
  const totalAllocation = 100;

  // Function to fetch data from API
  async function fetchData() {
    const token = localStorage.getItem("authToken");
    console.log(token);
    try {
      const response = await fetch(
        `${window.location.origin}/api/get_budget.php`,
        {
          method: "POST",
          headers: {
            Authorization: "Bearer " + token,
            "Content-Type": "application/json",
          },
          body: {},
        }
      );
      if (response.ok) {
        const data = await response.json();
        console.log(data);
        totalBudgetInput.value = data.budget.total_budget; // Update the input field with the budget from API
        submitButton.disabled = true; // Disable the submit button
        submitButton.classList.add("bg-gray-400");
        submitButton.classList.add("hover:bg-gray-400");
      } else {
        console.error("Failed to fetch data:", response.statusText);
      }
    } catch (error) {
      console.error("Error fetching data try later:", error);
    }
  }

  // Fetch data when the page loads
  fetchData();

  editButton.addEventListener("click", () => {
    submitButton.disabled = false;
    submitButton.classList.remove("bg-gray-400");
    submitButton.classList.remove("hover:bg-gray-400");
  });

  submitButton.addEventListener("click", async function () {
    const total_budget = totalBudgetInput.value;
    const token = localStorage.getItem("authToken");

    if (!total_budget || !token) {
      alert("Please enter a total budget and ensure you are logged in.");
      return;
    }

    try {
      const response = await fetch(
        `${window.location.origin}/api/set_budget.php`,
        {
          method: "POST",
          headers: {
            Authorization: "Bearer " + token,
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            total_budget: total_budget,
            categories: ["entertainment", "rent", "groceries", "others"],
          }),
        }
      );

      const data = await response.json();

      if (response.ok) {
        alert(data.message); // Show success message
        totalBudgetInput.value = ""; // Clear the input field
        fetchData();

        // Trigger the second script (allocation) after successful budget setup
        await allocateBudgetToCategories(); // Call the allocation function after successful budget setup
      } else {
        alert(data.message || "Failed to set budget. Please try again."); // Show error message
      }
    } catch (error) {
      console.error("Error:", error);
      alert("An error occurred while setting the budget. Please try again.");
    }
  });

  // Second Script: Allocate Budget to Categories
  function updateAllocation() {
    let total = 0;

    sliders.forEach((slider) => {
      total += parseInt(slider.value);
    });

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

    sliders.forEach((slider) => {
      const percentageLabel = slider.previousElementSibling;
      percentageLabel.textContent = `${slider.value}%`;
    });
  }

  sliders.forEach((slider) => {
    slider.addEventListener("input", updateAllocation);
  });

  async function allocateBudgetToCategories() {
    const categories = [];

    sliders.forEach((slider) => {
      const percentage = parseInt(slider.value);
      const categoryName = slider.nextElementSibling.textContent;
      categories.push({ name: categoryName, percentage });
    });

    const data = { categories };
    const token = localStorage.getItem("authToken");
    console.log(categories);

    try {
      const response = await fetch(
        `${window.location.origin}/api/allocate_budget.php`,
        {
          method: "POST",
          headers: {
            Authorization: "Bearer " + token,
            "Content-Type": "application/json",
          },
          body: JSON.stringify(data),
        }
      );

      if (response.ok) {
        const result = await response.json();
        alert("Data successfully saved!");
        console.log(result);
      } else {
        const errorResult = await response.json();
        alert(`Error: ${errorResult.message}`);
      }
    } catch (error) {
      alert("An error occurred while saving the data.");
      console.error(error);
    }
  }

  allocationSubmitButton.addEventListener("click", allocateBudgetToCategories);
});
