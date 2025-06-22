const categoryContainer = document.getElementById("categoryList");
const totalBudgetInput = document.getElementById("total-budget");
const totalPercentageError = document.getElementById("totalPercentageError");
let selectedCategories = [];
let totalAllocatedPercentage = 0;

function clearAllocations() {
  selectedCategories = [];
  totalAllocatedPercentage = 0;
  renderCategories();
}

function addCategory() {
  const categorySelect = document.getElementById("category");
  const percentageInput = document.getElementById("percentage");
  const category = categorySelect.value;
  const percentage = parseInt(percentageInput.value);
  const totalBudget = parseFloat(totalBudgetInput.value);

  // Validation checks
  if (!category || isNaN(percentage) || percentage <= 0) {
    alert("Please select a category and enter a valid percentage.");
    return;
  }
  if (selectedCategories.some((item) => item.category === category)) {
    alert("Category already added. delete the existing entry.");
    return;
  }
  if (totalAllocatedPercentage + percentage > 100) {
    totalPercentageError.classList.remove("hidden");
    return;
  } else {
    totalPercentageError.classList.add("hidden");
  }

  const allocatedAmount = ((totalBudget * percentage) / 100).toFixed(2);
  const newCategory = {
    category,
    percentage,
    allocatedAmount,
  };
  selectedCategories.push(newCategory);
  totalAllocatedPercentage += percentage;

  renderCategories();
  categorySelect.value = "";
  percentageInput.value = "";
}

function editCategory(index) {
  const category = selectedCategories[index];
  document.getElementById("category").value = category.category;
  document.getElementById("percentage").value = category.percentage;
  deleteCategory(index);
}

function deleteCategory(index) {
  totalAllocatedPercentage -= selectedCategories[index].percentage;
  selectedCategories.splice(index, 1);
  renderCategories();
}

function renderCategories() {
  categoryContainer.innerHTML = "";
  selectedCategories.forEach((item, index) => {
    const categoryDiv = document.createElement("div");
    categoryDiv.classList.add(
      "flex",
      "items-center",
      "justify-between",
      "p-2",
      "border",
      "rounded-lg",
      "bg-white",
      "shadow-sm"
    );

    categoryDiv.innerHTML = `
       <div class="text-gray-700 font-semibold">${item.category}: ${item.percentage}% (${item.allocatedAmount})</div>
       <div>
         <button onclick="deleteCategory(${index})" class="text-sm text-red-500">Delete</button>
       </div>
       `;
    categoryContainer.appendChild(categoryDiv);
  });
}
