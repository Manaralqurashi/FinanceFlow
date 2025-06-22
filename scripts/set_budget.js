// Function to handle form submission
document
  .getElementById("budgetForm")
  .addEventListener("submit", async function (event) {
    event.preventDefault();

    const dateString = document.getElementById("date").value; // "2024-10"
    // Split the string into year and month parts
    const [year, month] = dateString.split("-");
    // Concatenate the month and year in the desired format
    const date = month + "-" + year; // "10-2024"
    console.log(date); // Output: "10-2024"

    const totalBudget = parseFloat(
      document.getElementById("total-budget").value
    );
    const categoryList = document.getElementById("categoryList").children;

    // Check if at least one category is added
    if (categoryList.length === 0) {
      alert("Please add at least one category before submitting.");
      return; // Exit the function if no category is added
    }

    const categories = {};

    // Gather category data
    for (let i = 0; i < categoryList.length; i++) {
      const categoryText = categoryList[i].querySelector("div").textContent;
      const [categoryName, percentagePart] = categoryText.split(": ");
      const [percentage, value] = percentagePart.split("(");

      categories[categoryName.trim().toLowerCase()] = {
        [`allocated_percentage`]: parseFloat(percentage),
        [`allocated_value`]: parseFloat(value.replace(")", "")),
      };
    }

    // Create JSON data to send
    const requestData = {
      date: date,
      total_budget: totalBudget,
      categories: categories,
    };

    const token = localStorage.getItem("authToken");

    console.log("requested data format:", requestData);
    // Send data to API endpoint
    try {
      const response = await fetch(
        `${window.location.origin}/api/set_budget.php`,
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
        alert(data.message || "Budget set successfully!"); // Show success message
      } else {
        alert(
          data.message ||
            "Budget for that month already added. Update for that if you want"
        );
      }
    } catch (error) {
      console.error("Error: ", error);
    }
  });
