# cache

A simple cache solution written in PHP

## To get started:

* Include Cache.php and the cache config file (whatever you named it) into your project
* Instantiate the class with the config array
* Set content equal to the return value of the get_cached_content() method
* Check if the cache is expired. If so, get your content and cache it.
* Display your content


## Example
```php
// Set up the cache
$cache = new uco\Cache( $config );

// Get content from cache (if exists)
$cache->content = $cache->get_cached_content();

// If cache is expired or cache content doesn't exist, retreive content and cache it
if( $cache->cache_expired() )
{
	// Get your dynamic content here
	$content = $someWhackyClass->get_complicated_content();

	$cache->set_content( $content )->cache();
}

// Returns cache time and type
echo $cache->info;

// The content
echo $cache->content;
```

Happy Caching!
