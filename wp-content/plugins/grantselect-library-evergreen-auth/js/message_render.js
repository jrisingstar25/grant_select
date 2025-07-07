document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById("sip2_login_form");
    const messageElement = document.getElementById('user_notice');
    let index = 0;

    const messages = [
        "Authenticating your library card number...",
        "Thank you for your patience, this can take up to 60 seconds...",
    ];

    if (form) {
        form.addEventListener("submit", function(e) {
            e.preventDefault(); // ✅ Prevent default form submission
            messageElement.style.display = "block";
            form.submit();

            setInterval(() => {
                index = (index + 1) % messages.length;
                messageElement.textContent = messages[index];
            }, 15000); 
        });
    }
});