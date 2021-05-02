<?php
/**
 * Package release.
 *
 * @package SatisPress
 * @license GPL-2.0-or-later
 * @since 0.3.0
 */

declare ( strict_types = 1 );

namespace SatisPress;

/**
 * Package release class.
 *
 * @since 0.3.0
 */
class Release {
	/**
	 * Package.
	 *
	 * @var Package
	 */
	protected $package;

	/**
	 * Source URL.
	 *
	 * @var string
	 */
	protected $source_url;

	/**
	 * Version.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Create a release.
	 *
	 * @since 0.3.0
	 *
	 * @param Package $package    Package.
	 * @param string  $version    Version.
	 * @param string  $source_url Optional. Release source URL.
	 */
	public function __construct( Package $package, string $version, string $source_url = '' ) {
		$this->package    = $package;
		$this->version    = $version;
		$this->source_url = $source_url;
	}

	/**
	 * Retrieve the URL to download the release.
	 *
	 * @since 0.3.0
	 *
	 * @param array $args Query parameters to add to the URL.
	 * @return string
	 */
	public function get_download_url( array $args = [] ): string {

		// un-prefix the package type
		$package_type = str_replace( 'wordpress-', '', $this->get_package()->get_type() );

		$url = sprintf(
			'/satispress/%s/%s/%s',
			$package_type,
			$this->get_package()->get_slug(),
			$this->get_version()
		);

		return add_query_arg( $args, network_home_url( $url ) );
	}

	/**
	 * Retrieve the relative path to a release artifact.
	 *
	 * @since 0.3.0
	 *
	 * @return string
	 */
	public function get_file_path(): string {

		// un-prefix the package type
		$package_type = str_replace( 'wordpress-', '', $this->get_package()->get_type() );

		$path = sprintf(
			'%1$s/%2$s/%3$s',
			$package_type,
			$this->get_package()->get_slug(),
			$this->get_file()
		);

		return $path;
	}

	/**
	 * Retrieve the name of the file.
	 *
	 * @since 0.3.0
	 *
	 * @return string
	 */
	public function get_file(): string {
		return sprintf(
			'%1$s-%2$s.zip',
			$this->get_package()->get_slug(),
			$this->get_version()
		);
	}

	/**
	 * Retrieve the package.
	 *
	 * @since 0.3.0
	 *
	 * @return Package
	 */
	public function get_package(): Package {
		return $this->package;
	}

	/**
	 * Retrieve the source URL for a release.
	 *
	 * @since 0.3.0
	 *
	 * @return string
	 */
	public function get_source_url(): string {
		return $this->source_url;
	}

	/**
	 * Retrieve the version number for the release.
	 *
	 * @since 0.3.0
	 *
	 * @return string
	 */
	public function get_version(): string {
		return $this->version;
	}
}
