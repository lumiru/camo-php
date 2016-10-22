
# Camo in PHP

Camo is all about making insecure assets look secure. This is an SSL image proxy to prevent mixed content warnings on secure pages served from GitHub.

This project is a lightweight PHP version of [Camo](https://github.com/atmos/camo).

## URL Formats

Camo supports two distinct URL formats:

	https://example.org/camo.php/<digest>?url=<image-url>
	https://example.org/camo.php/<digest>/<image-url>

The `<digest>` is a 40 character hex encoded HMAC digest generated with a shared secret key and the unescaped `<image-url>` value. The `<image-url>` is the absolute URL locating an image. In the first format, the `<image-url>` should be URL escaped aggressively to ensure the original value isn't mangled in transit. In the second format, each byte of the `<image-url>` should be hex encoded such that the resulting value includes only characters `[0-9a-f]`.

## Usage

You only need to upload the file to your web server and put your custom shared key in `SERVER_KEY` constant.

Apache configuration can be used to delete the file name in URI (`https://example.org/` instead of `https://example.org/camo.php/`) :

	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule ^([0-9a-f]{40}/?[0-9a-f]*/?)$ camo.php/$1 [L,NC]

## Configuration

Configurations are made in constants at the begining of the file :

* `SERVER_KEY` : The shared key used to generate the HMAC digest (same as `CAMO_KEY` in original camo project).

## Examples

* Ruby - https://github.com/ankane/camo
* PHP - https://github.com/willwashburn/Phpamo
