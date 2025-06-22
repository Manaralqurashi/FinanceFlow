const currentYear = new Date().getFullYear();
const previousYears = [];

for (let i = 10; i >= 0; i--) {
  previousYears.push(currentYear - i);
}

const yearSelect = document.getElementById("year");

previousYears.forEach((year) => {
  const option = document.createElement("option");
  option.value = year;
  option.text = year;
  yearSelect.appendChild(option);
});
