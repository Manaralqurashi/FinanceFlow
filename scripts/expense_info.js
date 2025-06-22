document.addEventListener("DOMContentLoaded", () => {
  const expenseInfoDiv = document.getElementById("expense-info");

  // Function to fetch budget data from the API
  async function fetchBudgetData() {
    const token = localStorage.getItem("authToken");

    try {
      const response = await fetch(
        `${window.location.origin}/api/category_expenses.php`,
        {
          method: "POST",
          headers: {
            Authorization: "Bearer " + token,
            "Content-Type": "application/json",
          },
        }
      );

      const data = await response.json();

      console.log(data.message);

      // Check if budget is not set
      if (data.message === "No active budget found for the user") {
        expenseInfoDiv.innerHTML = "<p>Budget is not set yet.</p>";
        return;
      }

      // Check if the request was successful
      if (response.ok) {
        renderBudgetData(data);
      } else {
        throw new Error("Failed to fetch budget data.");
      }
    } catch (error) {
      console.error("Error fetching budget data:", error);
      expenseInfoDiv.innerHTML =
        "<p>Something went wrong on the server. Please refresh the page.</p>";
    }
  }

  // Function to render budget data
  function renderBudgetData(data) {
    // Clear previous content
    expenseInfoDiv.innerHTML = "";

    // Loop through each category and create the progress bar
    data.categories.forEach((category) => {
      const { category_name, spent_percentage, extra_spent_percentage } =
        category;

      // Create status bar div
      const categoryDiv = document.createElement("div");
      categoryDiv.classList.add("mb-4");

      // Create labels for the category and percentage spent
      const categoryHeader = document.createElement("div");
      categoryHeader.classList.add("flex", "justify-between", "mb-1");
      categoryHeader.innerHTML = `
<span>${category_name.charAt(0).toUpperCase() + category_name.slice(1)}</span>
${
  extra_spent_percentage > 0
    ? `<span>${extra_spent_percentage}% of extra budget used</span>`
    : `<span>${spent_percentage}% of budget used</span>`
}
`;

      // Create the progress bar container
      const progressBarContainer = document.createElement("div");
      progressBarContainer.classList.add(
        "w-full",
        "bg-gray-200",
        "rounded-full",
        "h-2"
      );

      // Create the progress bar itself
      const progressBar = document.createElement("div");
      progressBar.classList.add("h-2", "rounded-full");

      // Set the progress bar color and width
      if (extra_spent_percentage > 0) {
        progressBar.classList.add("bg-red-500"); // Red for overspending
      } else {
        progressBar.classList.add("bg-green-500"); // Green for within budget
      }
      progressBar.style.width = `${spent_percentage}%`;

      // Append the progress bar to its container
      progressBarContainer.appendChild(progressBar);

      // Append the category header and progress bar container to the category div
      categoryDiv.appendChild(categoryHeader);
      categoryDiv.appendChild(progressBarContainer);

      // Append the category div to the main expense info div
      expenseInfoDiv.appendChild(categoryDiv);
    });
  }

  // Fetch the budget data when the page loads
  fetchBudgetData();
});
