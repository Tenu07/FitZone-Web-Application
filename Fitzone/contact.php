<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - FitZone</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        textarea { height: 150px; }
        button { background: #4CAF50; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; }
        button:hover { background: #45a049; }
        #response-message { margin-top: 15px; padding: 10px; border-radius: 4px; display: none; }
        .success { background: #dff0d8; color: #3c763d; }
        .error { background: #f2dede; color: #a94442; }
    </style>
</head>
<body>
    <h1>Contact FitZone</h1>
    <form id="contactForm">
    <div class="form-group">
        <label for="name">Name*</label>
        <input type="text" id="name" name="name" required>
    </div>
    <div class="form-group">
        <label for="email">Email*</label>
        <input type="email" id="email" name="email" required>
    </div>
    <div class="form-group">
        <label for="phone">Phone</label>
        <input type="tel" id="phone" name="phone">
    </div>
    <div class="form-group">
        <label for="subject">Subject*</label>
        <select id="subject" name="subject" required>
            </select>
    </div>
    <div class="form-group">
        <label for="message">Message*</label>
        <textarea id="message" name="message" required></textarea>
    </div>
    <button type="submit">Send Message</button>
</form>
<div id="response-message"></div>

    <script>
        document.getElementById('contactForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        const form = e.target;
        const formData = new FormData(form); // Get form data
        const responseMessage = document.getElementById('response-message');

        // Send data to process_contact.php
        fetch('process_contact.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json()) // Expect JSON response
        .then(data => { // Handle response
            responseMessage.style.display = 'block';
            if (data.success) {
                responseMessage.className = 'success';
                form.reset(); // Clear form on success
            } else {
                responseMessage.className = 'error';
            }
            responseMessage.textContent = data.message;
        })
        .catch(error => { // Handle errors
            responseMessage.style.display = 'block';
            responseMessage.className = 'error';
            responseMessage.textContent = 'There was an error submitting your form.';
        });
    });
    </script>
</body>
</html>