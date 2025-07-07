document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById("sip2_login_form");
    const noticeElement = document.getElementById('user_notice');
    let index = 0;

    const messages = [
        "<span>Authenticating your library card number...</span>",
        "<span>Thank you for your patience, this can take up to 60 seconds...</span>",
    ];

    if (form) {
        form.addEventListener("submit", function(e) {
            e.preventDefault();
            noticeElement.style.display = "block";
            form.submit();

            setInterval(() => {
                index = (index + 1) % messages.length;
                noticeElement.innerHTML = messages[index];
            }, 15000); 
        });
    }
});