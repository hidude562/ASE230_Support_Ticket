# Reflection

**Why does searching use GET while creating a ticket uses POST?**
Searching only reads data. It does not change anything on the server, so the search words can be in the URL (for example `index.php?status=Open&q=login`). That means the page can be bookmarked and shared, and a refresh is safe. Creating a ticket changes the data, and the details can be private. POST keeps them out of the URL, and the browser warns before it sends them again.

**Why is server-side validation still necessary if the HTML form performs validation?**
The checks in the HTML only run in the browser, and the user controls the browser. Someone can turn them off, edit the form, or send a request without the form at all, for instance with curl. So the server cannot trust anything it receives. It has to check every value again before it saves it.

**What happens to an uploaded file before PHP moves it to the final upload directory?**
PHP first saves it in a temporary folder with a temporary name, and it puts the details in `$_FILES` (the temp path, the size, and an error code). The file is deleted when the request ends unless the code moves it. That is why I check the error code, the size and the real type first, and then call `move_uploaded_file()`. I also give it a new random name, so the original filename is never used.

**Where could you use a similar request-processing pattern in your capstone project?**
My project is NorseWall, a small social media site. The same pattern works for registering, logging in, and making a post. Each one is request, validate, process, save, then respond. The friend request and the admin delete actions would use POST and check the ID and the user's role first, the same way the status update checks the ticket ID and the new status.
