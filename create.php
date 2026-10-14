<?php
require_once __DIR__ . '/includes/functions.php';

$values = [
    'name' => '',
    'email' => '',
    'category' => '',
    'description' => ''
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['name'] = trim($_POST['name'] ?? '');
    $values['email'] = trim($_POST['email'] ?? '');
    $values['category'] = trim($_POST['category'] ?? '');
    $values['description'] = trim($_POST['description'] ?? '');

    // A POST bigger than post_max_size arrives with $_POST and $_FILES empty.
    if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $errors[] = 'The upload was too large for the server.';
    }

    // TODO 6: server-side validation
    if ($values['name'] === '') {
        $errors[] = 'Name is required.';
    } elseif (textLength($values['name']) > 100) {
        $errors[] = 'Name must be 100 characters or fewer.';
    }

    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    if (!in_array($values['category'], allowedCategories(), true)) {
        $errors[] = 'Choose a category from the list.';
    }

    if (textLength($values['description']) < 10) {
        $errors[] = 'Description must be at least 10 characters.';
    } elseif (textLength($values['description']) > 2000) {
        $errors[] = 'Description must be 2000 characters or fewer.';
    }

    $attachment = null;

    // TODO 7: optional file upload
    $file = $_FILES['attachment'] ?? null;
    if ($file !== null && $file['error'] !== UPLOAD_ERR_NO_FILE) {
        $maxBytes = 2 * 1024 * 1024;   // 2 MB
        // Allowed real types, and the extension we pick ourselves for each one.
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            $errors[] = 'The image must be 2 MB or smaller.';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'The file could not be uploaded (error code ' . (int)$file['error'] . ').';
        } elseif ($file['size'] > $maxBytes) {
            $errors[] = 'The image must be 2 MB or smaller.';
        } elseif (!is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'Invalid upload.';
        } else {
            // finfo reads the bytes of the file, so a renamed .txt file is caught.
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            if (!isset($allowedTypes[$mime]) || @getimagesize($file['tmp_name']) === false) {
                $errors[] = 'Only JPG, PNG or WebP images are allowed.';
            } elseif (empty($errors)) {
                // Random name: the user's original filename is never used.
                $safeName = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
                if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $safeName)) {
                    $attachment = $safeName;
                } else {
                    $errors[] = 'The image could not be saved.';
                }
            }
        }
    }

    if (empty($errors)) {
        $tickets = loadTickets();

        // TODO 8: build the new ticket
        $newTicket = [
            'id' => nextTicketId($tickets),
            'name' => $values['name'],
            'email' => $values['email'],
            'category' => $values['category'],
            'description' => $values['description'],
            'status' => 'Open',
            'attachment' => $attachment,
        ];

        $tickets[] = $newTicket;

        // TODO 9: save, then redirect so a refresh does not submit the form again
        if (saveTickets($tickets)) {
            header('Location: index.php');
            exit;
        }

        // Saving failed, so remove the uploaded file again and show an error.
        if ($attachment !== null) {
            @unlink(UPLOAD_DIR . $attachment);
        }
        $errors[] = 'The ticket could not be saved. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Support Ticket</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<main class="container">
    <p><a href="index.php">&larr; Back to tickets</a></p>

    <section class="panel">
        <h1>Create Support Ticket</h1>
        <p>Complete the form below. Important validation must occur on the server.</p>

        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <strong>Please correct the following:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="create.php" enctype="multipart/form-data">
            <label for="name">Customer Name</label>
            <input
                type="text"
                id="name"
                name="name"
                value="<?= e($values['name']) ?>"
            >

            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= e($values['email']) ?>"
            >

            <label for="category">Category</label>
            <select id="category" name="category">
                <option value="">Choose a category</option>
                <?php foreach (allowedCategories() as $category): ?>
                    <option
                        value="<?= e($category) ?>"
                        <?= $values['category'] === $category ? 'selected' : '' ?>
                    >
                        <?= e(ucfirst($category)) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="description">Description</label>
            <textarea id="description" name="description" rows="7"><?= e($values['description']) ?></textarea>

            <label for="attachment">Optional Image</label>
            <input
                type="file"
                id="attachment"
                name="attachment"
                accept="image/jpeg,image/png,image/webp"
            >

            <button type="submit">Submit Ticket</button>
        </form>
    </section>
</main>
</body>
</html>
