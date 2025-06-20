based on: https://www.twilio.com/en-us/blog/create-markdown-blog-php-slim-4

# Simple Website / Blog

A simple website / blog project that pulls content from markdown files in a directory. The purpose is to learn better coding techniques in PHP, use composer repositories, and just have some fun.

This site has no database, collects no information, and does not have tracking cookies.

## Installation

- Requires PHP 8.2+ and Composer.

If by any chance, you want to install this respository then follow these instructions:

- Clone Repository.
- Rename config.sample.yaml to config.yaml.
- Edit config.yaml to include your site information.
- Add your posts to data/posts
- Run `composer update`
- Run a web server: `php -S 127.0.0.1:8080 -t public`


## Configuration

### Links

Links are used to build the footer of the page. The code will read these and automatically build routes for any links that do not start with HTTPS.

## Content

Site Content is made up of markdown files located in the data/posts directory. These are parsed to HTML on display.

### Metadata
