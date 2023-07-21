## phpQuery, new override here!

My intent is to have it easily integrated in different projects.
I've gathered some fix and new features here and there.

## Manual

* [Manual]() imported from http://code.google.com/p/phpquery/wiki

## Extracts from morrow README.md:

### What's phpQuery?
To quote the phpQuery *(originally conceived and developed by [Tobiasz Cudnik](https://github.com/TobiaszCudnik/phpquery), available on Google Code and Github)* project documentation:

>phpQuery is a server-side, chainable, CSS3 selector driven Document Object Model (DOM) API based on jQuery JavaScript Library.
>
>Library is written in PHP5 and provides additional Command Line Interface (CLI).

### Example usage

(copied from http://code.google.com/p/phpquery/wiki/Basics)

Complete working example:

```php
<?php
include 'phpQuery.php';

$file = 'test.html'; // see below for source

// loads the file
// basically think of your php script as a regular HTML page running client side with jQuery.  This loads whatever file you want to be the current page
phpQuery::newDocumentFileHTML($file);

// Once the page is loaded, you can then make queries on whatever DOM is loaded.
// This example grabs the title of the currently loaded page.
$titleElement = pq('title'); // in jQuery, this would return a jQuery object.  I'm guessing something similar is happening here with pq.

// You can then use any of the functionality available to that pq object.  Such as getting the innerHTML like I do here.
$title = $titleElement->html();

// And output the result
echo '<h2>Title:</h2>';
echo '<p>' . htmlentities( $title) . '</p>';
```

====

Source for test.html:

```html
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Hello World!</title>
</head>
<body>
</body>
</html>
```
