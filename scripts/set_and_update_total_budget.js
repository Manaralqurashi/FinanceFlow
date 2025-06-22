document.addEventListener("DOMContentLoaded", () => {
  const submitButton = document.getElementById("submit-budget-btn");
  const totalBudgetInput = document.getElementById("total-budget");
  const editButton = document.getElementById("edit-budget-btn");
  const updateBudgetButton = document.getElementById("update-budget-btn");

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
        // Assuming the API response has a field named 'budget'
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
    updateBudgetButton.disabled = false; // Disable the submit button
    updateBudgetButton.classList.remove("bg-gray-400");
    updateBudgetButton.classList.remove("hover:bg-gray-400");
  });

  function getSelectOptionValues(selectId) {
    // Get the select element by its ID
    const selectElement = document.getElementById(selectId);

    // Create an empty array to store option values
    let optionValues = [];

    // Loop through the options in the select element
    for (let i = 0; i < selectElement.options.length; i++) {
      optionValues.push(selectElement.options[i].value);
    }

    // Return the array of option values
    return optionValues;
  }

  document
    .getElementById("submit-budget-btn")
    .addEventListener("click", async function () {
      const totalBudgetInput = document.getElementById("total-budget");
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
        } else {
          alert(data.message || "Failed to set budget. Please try again."); // Show error message
        }
      } catch (error) {
        console.error("Error:", error);
        alert("An error occurred while setting the budget. Please try again.");
      }
    });
});
