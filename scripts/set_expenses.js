document.addEventListener("DOMContentLoaded", () => {
  const submitButton = document.getElementById("submit-expense"); // Assuming there's only one button, otherwise use an ID
  const categorySelect = document.querySelector("select");
  const amountInput = document.querySelector("input[type='number']");
  const getdate = document.getElementById("date");

  // Function to handle form submission
  submitButton.addEventListener("click", async function (event) {
    event.preventDefault(); // Prevent form from reloading the page

    const category = categorySelect.value;
    const amount = amountInput.value;
    const date = getdate.value;
    const token = localStorage.getItem("authToken");

    if (!category || !amount || !token || !date) {
      alert(
        "Please select a category, date, enter an amount, and ensure you are logged in."
      );
      return;
    }

    try {
      const response = await fetch(
        `${window.location.origin}/api/adding_expenses.php`,
        {
          method: "POST",
          headers: {
            Authorization: "Bearer " + token,
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            category_name: category,
            amount_spent: amount,
            date: date,
          }),
        }
      );

      const data = await response.json();

      if (response.ok) {
        alert(data.message || "Expense successfully submitted!"); // Show success message
        amountInput.value = ""; // Clear the input field
      } else {
        alert(data.message || "Failed to submit expense. Please try again."); // Show error message
      }
    } catch (error) {
      console.error("Error:", error);
      alert(
        "An error occurred while submitting the expense. Please try again."
      );
    }
  });
});
