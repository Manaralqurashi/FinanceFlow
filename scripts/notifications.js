document.addEventListener("DOMContentLoaded", () => {
  const notificationsSection = document.getElementById("notifications-section");

  async function fetchNotificationsData() {
    const token = localStorage.getItem("authToken");

    try {
      const response = await fetch(
        `${window.location.origin}/api/notifications.php`,
        {
          method: "POST",
          headers: {
            Authorization: "Bearer " + token,
            "Content-Type": "application/json",
          },
        }
      );

      const data = await response.json();

      if (data.message === "No active budget found for the user") {
        notificationsSection.innerHTML = `
          <div class="bg-white border border-gray-400 text-gray-700 px-4 py-3 rounded relative transition duration-500 transform hover:scale-105 flex items-center notification">
            <img src="_public/notification.png" alt="Notification Icon" class="w-6 h-6 mr-4">
            <strong class="font-bold">Attention!</strong>
            <span class="block sm:inline">No active budget found for you.</span>
          </div>
        `;
        return;
      }

      if (response.ok) {
        renderNotifications(data.notifications);
      } else {
        throw new Error("Failed to fetch notifications.");
      }
    } catch (error) {
      console.error("Error fetching notifications data:", error);
      notificationsSection.innerHTML = `
        <div class="bg-white border border-gray-400 text-gray-700 px-4 py-3 rounded relative transition duration-500 transform hover:scale-105 flex items-center notification">
          <img src="_public/notification.png" alt="Notification Icon" class="w-6 h-6 mr-4">
          <strong class="font-bold">Error!</strong>
          <span class="block sm:inline">Something went wrong while fetching notifications. Please refresh the page.</span>
        </div>
      `;
    }
  }

  function renderNotifications(notifications) {
    notificationsSection.innerHTML = "";

    if (notifications.length === 0) {
      notificationsSection.innerHTML = `
        <div class="bg-white border border-gray-400 text-gray-700 px-4 py-3 rounded relative transition duration-500 transform hover:scale-105 flex items-center notification">
          <img src="_public/notification.png" alt="Notification Icon" class="w-6 h-6 mr-4">
          <strong class="font-bold">You're on track!</strong>
          <span class="block sm:inline">You're staying within your budget!</span>
        </div>
      `;
      return;
    }

    notifications.forEach((notification) => {
      const { category_name, message, spent_percentage, allocated_percentage } = notification;

      const bgColor = "bg-white";
      const textColor = "text-gray-700";

      const notificationDiv = document.createElement("div");
      notificationDiv.classList.add(
        bgColor,
        "border",
        "border-gray-400",
        textColor,
        "px-4",
        "py-3",
        "rounded",
        "mb-2",
        "flex",
        "items-center",
        "notification"
      );

      let notificationMessage;
      if (category_name.toLowerCase() === "savings") {
        if (spent_percentage > allocated_percentage) {
          notificationMessage = `Great job! You've exceeded your savings goal (${spent_percentage}%)`;
        } else {
          notificationMessage = `Keep going! You have saved ${spent_percentage}% of your goal.`;
        }
      } else {
        notificationMessage = `${message} (${spent_percentage}%)`;
      }

      notificationDiv.innerHTML = `
        <img src="_public/notification.png" alt="Notification Icon" class="w-6 h-6 mr-4">
        <strong class="font-bold">${
          category_name.charAt(0).toUpperCase() + category_name.slice(1)
        } </strong>
        <span class="block sm:inline">${notificationMessage}</span>
      `;

      notificationsSection.appendChild(notificationDiv);
    });
  }

  fetchNotificationsData();
});