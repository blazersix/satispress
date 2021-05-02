<?php
/**
 * Download request handler.
 *
 * @package SatisPress
 * @license GPL-2.0-or-later
 * @since 0.3.0
 */

declare ( strict_types = 1 );

namespace SatisPress\Route;

use SatisPress\Capabilities;
use SatisPress\Exception\SatispressException;
use SatisPress\Exception\HttpException;
use SatisPress\Exception\InvalidReleaseVersion;
use SatisPress\HTTP\Request;
use SatisPress\HTTP\Response;
use SatisPress\Package;
use SatisPress\ReleaseManager;
use SatisPress\Repository\PackageRepository;

/**
 * Class to handle download requests.
 *
 * @since 0.3.0
 */
class Download implements Route {
	/**
	 * Latest version.
	 *
	 * @var string
	 */
	const LATEST_VERSION = 'latest';

	/**
	 * Regex for sanitizing package slugs.
	 *
	 * @var string
	 */
	const PACKAGE_SLUG_REGEX = '/[^A-Za-z0-9._\-]+/i';

	/**
	 * Regex for sanitizing package versions.
	 *
	 * @var string
	 */
	const PACKAGE_VERSION_REGEX = '/[^0-9a-z.-]+/i';

	/**
	 * Regex for sanitizing package package type.
	 *
	 * @var string
	 */
	const PACKAGE_TYPE_REGEX = '/[^A-Za-z0-9._\-]+/i';

	/**
	 * Release manager.
	 *
	 * @var ReleaseManager
	 */
	protected $release_manager;

	/**
	 * Package repository.
	 *
	 * @var PackageRepository
	 */
	protected $repository;

	/**
	 * Constructor.
	 *
	 * @since 0.3.0
	 *
	 * @param PackageRepository $repository      Package repository.
	 * @param ReleaseManager    $release_manager Release manager.
	 */
	public function __construct( PackageRepository $repository, ReleaseManager $release_manager ) {
		$this->repository      = $repository;
		$this->release_manager = $release_manager;
	}

	/**
	 * Process a download request.
	 *
	 * Determines if the current request is for packages.json or a whitelisted
	 * package and routes it to the appropriate method.
	 *
	 * @since 0.3.0
	 *
	 * @param Request $request HTTP request instance.
	 * @throws HttpException For invalid parameters or the user doesn't have
	 *                       permission to download the requested file.
	 * @return Response
	 */
	public function handle( Request $request ): Response {
		if ( ! current_user_can( Capabilities::DOWNLOAD_PACKAGES ) ) {
			throw HttpException::forForbiddenResource();
		}

		$slug = preg_replace( self::PACKAGE_SLUG_REGEX, '', $request['slug'] );
		if ( empty( $slug ) ) {
			throw HttpException::forUnknownPackage( $slug );
		}

		$version = '';
		if ( ! empty( $request['version'] ) ) {
			$version = preg_replace( self::PACKAGE_VERSION_REGEX, '', $request['version'] );
		}

		$type = '';
		if ( ! empty( $request['type'] ) ) {
			$type = preg_replace( self::PACKAGE_TYPE_REGEX, '', $request['type'] );
		}

		$package = $this->repository->first_where( [ 'slug' => $slug, 'type' => $type ] );

		// Send a 404 response if the package doesn't exist.
		if ( ! $package instanceof Package ) {
			throw HttpException::forUnknownPackage( $slug );
		}

		return $this->send_package( $package, $version );
	}

	/**
	 * Send a package zip.
	 *
	 * Sends a 404 header if the specified version isn't available.
	 *
	 * @since 0.3.0
	 *
	 * @param Package $package Package object.
	 * @param string  $version Version of the package to send.
	 * @throws HttpException For invalid or missing releases.
	 * @return Response
	 */
	protected function send_package( Package $package, string $version ): Response {
		if ( self::LATEST_VERSION === $version ) {
			$version = $package->get_latest_release()->get_version();
		}

		try {
			$release = $package->get_release( $version );
		} catch ( InvalidReleaseVersion $e ) {
			throw HttpException::forInvalidRelease( $package, $version );
		}

		// Ensure the user has access to download the release.
		if ( ! current_user_can( Capabilities::DOWNLOAD_PACKAGE, $package, $release ) ) {
			throw HttpException::forForbiddenPackage( $package );
		}

		try {
			// Cache the release if an artifact doesn't already exist.
			$release = $this->release_manager->archive( $release );
		} catch ( SatispressException $e ) {
			// Send a 404 if the release isn't available.
			throw HttpException::forMissingRelease( $release );
		}

		return $this->release_manager->send( $release );
	}
}
