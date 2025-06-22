async function handleLogout() {
  // Assuming the auth token is stored in localStorage (or sessionStorage)
  const token = localStorage.getItem("authToken");

  if (!token) {
    alert("You are already logged out.");
    return;
  }

  try {
    const response = await fetch(
      `${window.location.origin}/api/user_logout.php`,
      {
        method: "POST",
        headers: {
          Authorization: "Bearer " + token,
          "Content-Type": "application/json",
        },
      }
    );

    // Parse the JSON response
    const data = await response.json();

    if (response.ok) {
      // Logout successful, remove token and redirect or show success message
      localStorage.removeItem("authToken");
      alert(data.message); // You can also redirect to the login page here if needed
      window.location.href = "index.html"; // Redirect to the login page after logout
    } else {
      // Handle errors (e.g., token invalid)
      alert(data.message || "Failed to log out, please try again.");
    }
  } catch (error) {
    console.error("Error:", error);
    alert("An error occurred while logging out. Please try again.");
  }
}

// Attach the logout function to the button click event
document.getElementById("logout-btn").addEventListener("click", handleLogout);
