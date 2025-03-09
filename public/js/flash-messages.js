document.addEventListener("DOMContentLoaded", function () {
    const flashMessages = document.querySelectorAll(".alert");

    flashMessages.forEach((message) => {
        setTimeout(() => {
            message.style.transition = "opacity 0.5s";
            message.style.opacity = "0";
            setTimeout(() => message.remove(), 500);
        }, 3000);
    });
});
