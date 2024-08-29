# wp-gpt-content

PHP script that reviews and refines WordPress content using ChatGPT to improve SEO. It updates post content, tags, and categories to enhance readability and optimize search engine performance.

Works with OpenAi free account and tested with Wordpress version 6.6.1.

⚠️ Use at your own risk!

⚠️ Backup your data before!

## Requirements
- [wordpress](https://wordpress.org)
- [wp-cli](https://wp-cli.org)
- [openai api key](https://platform.openai.com/api-keys)

## Config
Edit `$prompt`, `$api_key` and `$max_tokens` in the php file

## How to Use
In the server which wp is running, execute:

```sh
wp eval-file wp-gpt-content.php --allow-root --path={WP_DIR}
```

For debugging, set the variable `DEBUG=1`:

```sh
DEBUG=1 wp eval-file wp-gpt-content.php --allow-root --path={WP_DIR}
```

## License

MIT
