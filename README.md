# Like System - WordPress Plugin

A comprehensive like system for WordPress posts with AJAX functionality, database storage, rate limiting, and email notifications.

## Features

- **AJAX Like Button**: Modern, animated like button with real-time updates
- **Database Storage**: Custom database table to store all like data
- **User Tracking**: Supports both logged-in users and guests
- **Rate Limiting**: 60-second cooldown per user/IP per post
- **Duplicate Prevention**: One like per user (or IP for guests) per post
- **Email Notifications**: Admin receives email notification for each like
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

## Configuration

You can modify the following constants in `like-system.php`:

```php
define('WPLP_MAIL_TO', get_option('admin_email'));      // Email recipient
define('WPLP_RATE_LIMIT_SECONDS', 60);                  // Rate limit in seconds
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
