document.addEventListener("DOMContentLoaded", () => {
  const yearSelect = document.getElementById("year");
  const monthSelect = document.getElementById("month");
  const submitButton = document.getElementById("submit-btn");
  const reportSection = document.getElementById("monthly-report-section");

  // Handle form submission
  submitButton.addEventListener("click", async function (event) {
    event.preventDefault(); // Prevent any default form behavior

    const selectedYear = yearSelect.value;
    const selectedMonth = monthSelect.value;
    const token = localStorage.getItem("authToken");

    if (!selectedYear || !selectedMonth) {
      alert("Please select both a year and a month.");
      return;
    }

    function updateSpending(firstData, secondData) {
      // Initialize a variable to hold the total spent for each category
      const spendingMap = {};

      // Iterate over the data from the second parameter
      secondData.data.forEach((item) => {
        const category = item.category;
        const amount = parseFloat(item.amount); // Convert amount to a float

        // Add the amount to the corresponding category in the spendingMap
        if (!spendingMap[category]) {
          spendingMap[category] = 0;
        }
        spendingMap[category] += amount;
      });

      // Update the total_spent in the first parameter
      firstData.categories.forEach((category) => {
        const categoryName = category.category_name;
        if (spendingMap[categoryName]) {
          category.total_spent = spendingMap[categoryName]; // Update the total spent
        }
      });

      // Calculate the total spendings for the first parameter
      firstData.monthly_total_spendings = Object.values(spendingMap).reduce(
        (acc, curr) => acc + curr,
        0
      );

      return { ...secondData, ...firstData }; // Return the updated first parameter
    }

    try {
      // Send request to API
      const response = await fetch(
        `${window.location.origin}/api/monthly_report.php`,
        {
          method: "POST",
          headers: {
            Authorization: "Bearer " + token,
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            year: selectedYear,
            month: selectedMonth,
          }),
        }
      );

      const data = await response.json();

      // Handle successful response
      if (response.ok) {
        const res = await fetch(
          `${window.location.origin}/api/expense_overview.php`,
          {
            method: "POST",
            headers: {
              Authorization: "Bearer " + token,
              "Content-Type": "application/json",
            },
            body: JSON.stringify({
              year: selectedYear,
              month: selectedMonth,
            }),
          }
        );
        if (res.ok) {
          const d = await res.json();
          const updatedData = updateSpending(data, d);
          console.log(updatedData);
          renderMonthlyReport(updatedData);
        }
      } else {
        throw new Error(data.message || "Failed to fetch report.");
      }
    } catch (error) {
      console.error("Error:", error);
      reportSection.innerHTML = `
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
          <strong class="font-bold">Error!</strong>
          <span class="block sm:inline">Something went wrong. Please try again.</span>
        </div>
      `;
    }
  });

  // Function to render the monthly report data
  function renderMonthlyReport(data) {
    reportSection.innerHTML = ""; // Clear any previous content

    const {
      month,
      year,
      monthly_total_budget,
      monthly_total_spendings,
      categories,
    } = data;

    // Create header for the monthly report
    const reportHeader = `
      <div class="space-y-4 mt-8 p-2">
        <h2 class="text-xl font-semibold text-gray-800 mb-2">${month} ${year} Report</h2>
        <p class="text-gray-700">Total Budget: <strong>${monthly_total_budget}</strong></p>
        <p class="text-gray-700">Total Spendings: <strong>${monthly_total_spendings}</strong></p>
      </div>
    `;

    // Create category-wise breakdown
    let categoryDetails = '<div class="mt-2 p-2">';

    categories.forEach((category) => {
      const { category_name, allocated_budget, total_spent } = category;
      const progressPercent = Math.min(
        (total_spent / allocated_budget) * 100,
        100
      ); // To cap progress at 100%

      // Determine progress bar color based on the percentage spent
      let progressBarColor = "bg-green-500"; // Default is green
      if (progressPercent >= 100) {
        progressBarColor = "bg-red-500"; // Red for exceeded budget
      } else if (progressPercent >= 80) {
        progressBarColor = "bg-yellow-500"; // Yellow for warning (close to limit)
      }

      categoryDetails += `
        <div class="my-4">
          <div class="flex justify-between text-gray-700 font-medium mb-1">
            <span>${
              category_name.charAt(0).toUpperCase() + category_name.slice(1)
            }</span>
            <span>Spent: ${total_spent} /  ${allocated_budget}</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="${progressBarColor} h-2 rounded-full" style="width: ${progressPercent}%;"></div>
          </div>
        </div>
      `;
    });

    categoryDetails += "</div>"; // Close category container

    // Append the complete report to the section
    reportSection.innerHTML = reportHeader + categoryDetails;
  }
});
