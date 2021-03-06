## Donation
If you find this plugin useful, please consider making a donation. Thank you!

[![paypal](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=HQFGGDAGHHM22)

# PHP User Cache

A simple cache solution written in PHP. Available caching options include  WinCache, APCu, SQLSVR, MySQLi, or file (by default).

## To get started:

* Include Cache.php and the cache config file (whatever you named it) into your project
* Instantiate the class with the config array
* Set content equal to the return value of the get_cached_content() method
* Check if the cache is expired. If so, get your content and cache it.
* Display your content


## Example
```php
// Set up the cache
$cache = new Cache( $config );

// Get content from cache (if exists)
$content = $cache->get_cached_content();

// If cache is expired or cache content doesn't exist, retreive content and cache it
if( !$content )
{
	// Get your dynamic content here
	$content = $someWhackyClass->get_complicated_content();

	$cache->set_content( $content )->cache();
}

// Returns cache time and type
echo $cache->info;

// The content
echo $content;
```

Happy Caching!
