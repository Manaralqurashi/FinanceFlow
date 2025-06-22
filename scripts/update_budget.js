// Function to fetch existing budget data
async function fetchBudgetData(date) {
  const token = localStorage.getItem("authToken");
  try {
    const response = await fetch(`${window.location.origin}/api/get_budget.php`, {
      method: "POST",
      headers: {
        Authorization: "Bearer " + token,
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ date: date }),
    });

    const data = await response.json();
    
    if (response.ok && data.valid) {
      // Update the form with existing data
      document.getElementById("total-budget").value = data.data.total_budget;
      
      // Clear existing categories
      const categoryList = document.getElementById("categoryList");
      categoryList.innerHTML = "";
      
      // Add existing categories
      Object.entries(data.data.categories).forEach(([name, details]) => {
        addCategoryToSelected(
          name,
          details.allocated_percentage,
          details.allocated_value
        );
      });
    } else if (response.status === 404) {
      // Clear form for new entry
      document.getElementById("total-budget").value = "";
      document.getElementById("categoryList").innerHTML = "";
    }
  } catch (error) {
    console.error("Error fetching budget data:", error);
  }
}

// Listen for date input changes
document.getElementById("date").addEventListener("change", function() {
  const dateString = this.value; // Format: "2024-10"
  const [year, month] = dateString.split("-");
  const formattedDate = month + "-" + year; // Format: "10-2024"
  fetchBudgetData(formattedDate);
});

// Function to handle form submission
document
  .getElementById("budgetForm")
  .addEventListener("submit", async function (event) {
    event.preventDefault();

    const dateString = document.getElementById("date").value;
    const [year, month] = dateString.split("-");
    const date = month + "-" + year;

    const totalBudget = parseFloat(
      document.getElementById("total-budget").value
    );
    const categoryList = document.getElementById("categoryList").children;

    // Check if at least one category is added
    if (categoryList.length === 0) {
      alert("Please add at least one category before submitting.");
      return;
    }

    const categories = {};

    // Gather category data
    for (let i = 0; i < categoryList.length; i++) {
      const categoryText = categoryList[i].querySelector("div").textContent;
      const [categoryName, percentagePart] = categoryText.split(": ");
      const [percentage, value] = percentagePart.split("(");

      categories[categoryName.trim().toLowerCase()] = {
        allocated_percentage: parseFloat(percentage),
        allocated_value: parseFloat(value.replace(")", "")),
      };
    }

    // Create JSON data to send
    const requestData = {
      date: date,
      total_budget: totalBudget,
      categories: categories,
    };

    const token = localStorage.getItem("authToken");

    try {
      const response = await fetch(
        `${window.location.origin}/api/update_budget.php`,
        {
          method: "POST",
          headers: {
            Authorization: "Bearer " + token,
            "Content-Type": "application/json",
          },
          body: JSON.stringify(requestData),
        }
      );

      const data = await response.json();

      if (response.ok) {
        alert("Budget updated successfully!");
      } else {
        alert(data.message || "Failed to update budget. Please try again.");
      }
    } catch (error) {
      console.error("Error: ", error);
      alert("An error occurred while updating the budget.");
    }
  });
