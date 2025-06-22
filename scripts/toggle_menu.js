// Get references to the sidebar and the toggle button
const sidebar = document.getElementById("sidebar");
const toggleButton = document.getElementById("toggleButton");
const closeMenu = document.getElementById("closeMenu");

closeMenu.addEventListener("click", () => {
  console.log("close menu function called");
  sidebar.classList.add("hidden");
});

// Add an event listener to the toggle button
toggleButton.addEventListener("click", function () {
  // Toggle the hidden class on the sidebar when clicked
  sidebar.classList.toggle("hidden");
});
