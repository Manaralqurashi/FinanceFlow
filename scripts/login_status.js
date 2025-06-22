async function checkAuthToken() {
  const token = localStorage.getItem("authToken");

  if (!token) {
    window.location.href = "index.html";
    return;
  }

  try {
    const response = await fetch(
      `${window.location.origin}/api/validate_token.php`,
      {
        method: "POST",
        headers: {
          Authorization: "Bearer " + token,
          "Content-Type": "application/json",
        },
      }
    );

    const data = await response.json();

    if (!response.ok || !data.valid) {
      localStorage.removeItem("authToken");
      window.location.href = "index.html";
    } else {
      document.body.style.display = "block"; // Show the content once the auth check is successful
    }
  } catch (error) {
    console.error("Error while checking auth token:", error);
    window.location.href = "index.html";
  }
}

// Check the auth token when the page loads
document.addEventListener("DOMContentLoaded", checkAuthToken);
