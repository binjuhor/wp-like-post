# Like System - WordPress Plugin

A comprehensive like system for WordPress posts with AJAX functionality, database storage, rate limiting, and email notifications.

## Features

- **AJAX Like Button**: Modern, animated like button with real-time updates
- **Shortcode Support**: Easy integration with `[wplp_like_button]` shortcode
- **Database Storage**: Custom database table to store all like data
- **User Tracking**: Supports both logged-in users and guests
- **Rate Limiting**: 60-second cooldown per user/IP per post
- **Duplicate Prevention**: One like per user (or IP for guests) per post
- **Email Notifications**: Configurable email notifications for each like
- **Settings Page**: Admin interface to configure notification email
- **Statistics Dashboard**: View like statistics and top posts
- **Translation Ready**: Fully translatable with Japanese translations included
- **Beautiful UI**: Gradient design with smooth animations
- **Responsive Design**: Mobile-friendly interface
- **Security**: AJAX nonce verification and data sanitization

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- jQuery (included with WordPress)

## Installation

1. Upload the `like-system` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The database table will be created automatically on activation

## Usage

### Display Like Button

Add the like button to your theme template:

```php
<?php wplp_render_like_button(); ?>
```

Or for a specific post:

```php
<?php wplp_render_like_button($post_id); ?>
```

### Common Locations

**In single post template (single.php):**

```php
<div class="post-content">
    <?php the_content(); ?>
</div>
<div class="post-actions">
    <?php wplp_render_like_button(); ?>
</div>
```

**In post loop (content.php or archive.php):**

```php
<article id="post-<?php the_ID(); ?>">
    <h2><?php the_title(); ?></h2>
    <?php the_excerpt(); ?>
    <?php wplp_render_like_button(get_the_ID()); ?>
</article>
```

### Using Shortcode

You can also use the shortcode anywhere in your content, widgets, or page builders:

**Basic usage (current post):**

```
[wplp_like_button]
```

**For a specific post:**

```
[wplp_like_button post_id="123"]
```

**Examples:**

In post/page content:
```
Here is my post content...

[wplp_like_button]

Thanks for reading!
```

In a widget (Text widget or Custom HTML widget):
```
<h3>Like this post?</h3>
[wplp_like_button post_id="456"]
```

In page builders (Elementor, WPBakery, etc.):
```
[wplp_like_button]
```

Show like button for post ID 789:
```
[wplp_like_button post_id="789"]
```

## Database Structure

The plugin creates a custom table `{prefix}_post_likes` with the following fields:

| Field | Type | Description |
|-------|------|-------------|
| id | bigint(20) | Primary key |
| post_id | bigint(20) | Post being liked |
| user_id | bigint(20) | User ID (NULL for guests) |
| ip_address | varchar(45) | User's IP address |
| user_agent | varchar(255) | Browser information |
| created_at | datetime | Timestamp |

### Unique Constraint

The table has a unique constraint on `(post_id, user_id, ip_address)` to prevent duplicate likes.

## Settings

Navigate to **Settings > Like System** in your WordPress admin to configure the plugin.

### Email Notifications

Configure the email address that receives like notifications:

1. Go to **Settings > Like System**
2. Enter your desired notification email address
3. Click "Save Settings"

By default, it uses the WordPress admin email address.

### Statistics Dashboard

The settings page also displays:
- Total number of likes
- Number of liked posts
- Registered user likes vs guest likes
- Top 10 most liked posts with links to edit

### Advanced Configuration

You can modify the rate limit in the plugin file:

```php
define('WPLP_RATE_LIMIT_SECONDS', 60);  // Rate limit in seconds (default: 60)
```

## API Endpoints

### Like a Post

**Action:** `wplp_send_like`

**Parameters:**
- `post_id` (int): Post ID to like
- `nonce` (string): Security nonce

**Response:**
```json
{
  "success": true,
  "data": {
    "message": "Liked successfully",
    "likes": 15,
    "has_liked": true
  }
}
```

### Get Like Status

**Action:** `wplp_get_like_status`

**Parameters:**
- `post_id` (int): Post ID
- `nonce` (string): Security nonce

**Response:**
```json
{
  "success": true,
  "data": {
    "likes": 15,
    "has_liked": false
  }
}
```

## Helper Functions

### `wplp_get_like_count($post_id)`

Get the total number of likes for a post.

```php
$likes = wplp_get_like_count(123);
echo "This post has {$likes} likes";
```

### `wplp_has_user_liked($post_id)`

Check if the current user has liked a post.

```php
if (wplp_has_user_liked(123)) {
    echo 'You have already liked this post';
}
```

### `wplp_get_user_ip()`

Get the current user's IP address.

```php
$ip = wplp_get_user_ip();
```

## Customization

### CSS Customization

Override the default styles by adding CSS to your theme:

```css
/* Change button color */
.wplp-like {
    background: linear-gradient(135deg, #your-color-1 0%, #your-color-2 100%);
}

/* Change liked state color */
.wplp-like.wplp-liked {
    background: linear-gradient(135deg, #your-color-3 0%, #your-color-4 100%);
}
```

### JavaScript Customization

You can hook into the like button events:

```javascript
jQuery(document).on('click', '.wplp-like', function(e) {
    // Your custom code here
});
```

## Email Notifications

Admin receives an email notification for each like containing:

- Post title and URL
- Total like count
- User information (name/ID for logged-in users, IP for guests)
- Timestamp

Example email:

```
Subject: [Your Site] New Like: Post Title

A post has just been liked!

Title: Post Title
URL: https://yoursite.com/post-slug
Total likes: 15
User: John Doe (ID: 123)
Time: 2025-10-21 14:30:00
```

## Security Features

- AJAX nonce verification
- Data sanitization and validation
- SQL injection prevention with prepared statements
- XSS protection with output escaping
- Rate limiting to prevent spam
- Secure IP detection

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers

## Translation

The plugin is fully translatable and includes Japanese translations out of the box.

### Included Languages

- English (default)
- Japanese (日本語)

### Using Translations

The plugin will automatically load the appropriate translation based on your WordPress language settings:

1. Go to **Settings > General** in WordPress admin
2. Set **Site Language** to your preferred language
3. The plugin interface will automatically update

### Adding New Translations

To translate the plugin to your language:

1. **Using Poedit or Loco Translate:**
   - Install [Poedit](https://poedit.net/) (desktop app) or [Loco Translate](https://wordpress.org/plugins/loco-translate/) (WordPress plugin)
   - Open the template file: `languages/like-system.pot`
   - Translate all strings
   - Save as `like-system-{locale}.po` (e.g., `like-system-fr_FR.po` for French)
   - The `.mo` file will be generated automatically

2. **Using WP-CLI:**
   ```bash
   wp i18n make-pot . languages/like-system.pot
   wp i18n make-mo languages/
   ```

3. **Place translation files:**
   - Copy your `.po` and `.mo` files to `/wp-content/plugins/wp-like-post/languages/`
   - Or use the `/wp-content/languages/plugins/` directory for site-wide translations

### Available Strings

All user-facing strings are translatable including:
- Button labels ("Like", "Liked")
- Admin settings interface
- Email notification content
- Error messages
- Statistics dashboard

### Contributing Translations

If you'd like to contribute a translation:
1. Create your translation files using the `.pot` template
2. Submit a pull request or create an issue on GitHub
3. Your translation will be included in future releases

## Troubleshooting

### Like button doesn't appear

1. Make sure you've activated the plugin
2. Check that you've added `wplp_render_like_button()` to your template
3. Verify jQuery is loaded

### Likes not saving

1. Check database table was created (look for `{prefix}_post_likes`)
2. Check browser console for JavaScript errors
3. Verify AJAX requests are successful (Network tab in browser dev tools)

### Email not received

1. Check WordPress email configuration
2. Verify `WPLP_MAIL_TO` is set correctly
3. Check spam folder
4. Test WordPress email functionality with other plugins

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## Author

**Binjuhor**
- GitHub: https://github.com/binjuhor

## Support

For bug reports and feature requests, please use the [GitHub repository](https://github.com/binjuhor/like-system).

## Changelog

### 1.0.0
- Initial release
- AJAX like functionality
- Database storage
- User and guest support
- Rate limiting
- Email notifications
- Responsive design
