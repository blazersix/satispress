<?php
/**
 * Base package.
 *
 * @package SatisPress
 * @license GPL-2.0-or-later
 * @since 0.3.0
 */

declare ( strict_types = 1 );

namespace SatisPress\PackageType;

use SatisPress\Exception\InvalidReleaseVersion;
use SatisPress\Package;
use SatisPress\Release;

/**
 * Base package class.
 *
 * @since 0.3.0
 */
class BasePackage implements \ArrayAccess, Package {
	/**
	 * Package name.
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Author.
	 *
	 * @var string
	 */
	protected $author = '';

	/**
	 * Author URL.
	 *
	 * @var string
	 */
	protected $author_url = '';

	/**
	 * Description.
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * Homepage URL.
	 *
	 * @var string
	 */
	protected $homepage = '';

	/**
	 * Whether the package is installed.
	 *
	 * @var boolean
	 */
	protected $is_installed = false;

	/**
	 * Releases.
	 *
	 * @var Release[]
	 */
	protected $releases = [];

	/**
	 * Package slug.
	 *
	 * @var string
	 */
	protected $slug = '';

	/**
	 * Package type.
	 *
	 * @var string
	 */
	protected $type = '';

	/**
	 * Magic setter.
	 *
	 * @since 0.3.0
	 *
	 * @param string $name  Property name.
	 * @param mixed  $value Property value.
	 */
	public function __set( string $name, $value ) {
		// Don't allow undefined properties to be set.
		return;
	}

	/**
	 * Retrieve the author.
	 *
	 * @since 0.3.0
	 *
	 * @return string
	 */
	public function get_author(): string {
		return $this->author;
	}

	/**
	 * Retrieve the author URL.
	 *
	 * @since 0.3.0
	 *
	 * @return string
	 */
	public function get_author_url(): string {
		return $this->author_url;
	}

	/**
	 * Retrieve the description.
	 *
	 * @since 0.3.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Retrieve the homepage URL.
	 *
	 * @since 0.3.0
	 *
	 * @return string
	 */
	public function get_homepage(): string {
		return $this->homepage;
	}

	/**
	 * Retrieve the name.
	 *
	 * @since 0.3.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Retrieve the slug.
	 *
	 * @since 0.3.0
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return $this->slug;
	}

	/**
	 * Retrieve the package type.
	 *
	 * @since 0.3.0
	 *
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * Whether the package is installed.
	 *
	 * @since 0.3.0
	 *
	 * @return boolean
	 */
	public function is_installed(): bool {
		return $this->is_installed;
	}

	/**
	 * Whether the package has any releases.
	 *
	 * @since 0.3.0
	 *
	 * @return boolean
	 */
	public function has_releases(): bool {
		return ! empty( $this->releases );
	}

	/**
	 * Retrieve a release by version.
	 *
	 * @since 0.3.0
	 *
	 * @param string $version Version string.
	 * @throws InvalidReleaseVersion If the version is invalid.
	 * @return Release
	 */
	public function get_release( string $version ): Release {
		if ( ! isset( $this->releases[ $version ] ) ) {
			throw InvalidReleaseVersion::fromVersion( $version, $this->get_package_name() );
		}

		return $this->releases[ $version ];
	}

	/**
	 * Retrieve releases.
	 *
	 * @since 0.3.0
	 *
	 * @return Release[]
	 */
	public function get_releases(): array {
		return $this->releases;
	}

	/**
	 * Retrieve the latest release version.
	 *
	 * @since 0.3.0
	 *
	 * @throws InvalidReleaseVersion If the package doesn't have any releases.
	 * @return string
	 */
	public function get_latest_release(): Release {
		if ( $this->has_releases() ) {
			return reset( $this->releases );
		}

		throw InvalidReleaseVersion::hasNoReleases( $this->get_package_name() );
	}

	/**
	 * Retrieve the version for the latest release.
	 *
	 * @since 0.3.0
	 *
	 * @throws InvalidReleaseVersion If the package doesn't have any releases.
	 * @return string
	 */
	public function get_latest_version(): string {
		return $this->get_latest_release()->get_version();
	}

	/**
	 * Whether a property exists.
	 *
	 * Checks for an accessor method rather than the actual property.
	 *
	 * @since 0.3.0
	 *
	 * @param string $name Property name.
	 * @return boolean
	 */
	public function offsetExists( $name ): bool {
		return method_exists( $this, "get_{$name}" );
	}

	/**
	 * Retrieve a property value.
	 *
	 * @since 0.3.0
	 *
	 * @param string $name Property name.
	 * @return array
	 */
	public function offsetGet( $name ) {
		$method = "get_{$name}";

		if ( ! method_exists( $this, $method ) ) {
			return null;
		}

		return call_user_func( [ $this, $method ] );
	}

	/**
	 * Set a property value.
	 *
	 * @since 0.3.0
	 *
	 * @param string $name  Property name.
	 * @param array  $value Property value.
	 */
	public function offsetSet( $name, $value ) {
		return;
	}

	/**
	 * Unset a property.
	 *
	 * @since 0.3.0
	 *
	 * @param string $name Property name.
	 */
	public function offsetUnset( $name ) {
		return;
	}
}
