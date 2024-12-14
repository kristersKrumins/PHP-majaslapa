<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Event</title>
    <link rel="stylesheet" href="css/new_event.css">
</head>
<body>
    <header>
        <h1>Create a New Event</h1>
    </header>
    <main>
        <form action="save_event.php" method="post" enctype="multipart/form-data">
            <label for="title">Event Title:</label><br>
            <input type="text" id="title" name="title" required><br><br>

            <label for="description">Event Description:</label><br>
            <textarea id="description" name="description" rows="4" required></textarea><br><br>

            <label for="price">Price (€):</label><br>
            <input type="number" id="price" name="price" required><br><br>

            <label for="image">Upload Image:</label><br>
            <input type="file" id="image" name="image" accept="image/*" required><br><br>

            <button type="submit">Save Event</button>
        </form>
    </main>
</body>
</html>
