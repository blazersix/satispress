<?php
/**
 * Archiver.
 *
 * Creates package artifacts in the system's temporary directory. Methods return
 * the absolute path to the artifact. Code making use of this class should move
 * or delete the artifacts as necessary.
 *
 * @package SatisPress
 * @license GPL-2.0-or-later
 * @since 0.3.0
 */

declare ( strict_types = 1 );

namespace SatisPress;

use PclZip;
use Psr\Log\LoggerInterface;
use SatisPress\Exception\FileArchiveInvalid;
use SatisPress\Exception\FileDownloadFailed;
use SatisPress\Exception\FileOperationFailed;
use SatisPress\Exception\InvalidReleaseVersion;
use SatisPress\Exception\PackageNotInstalled;
use SatisPress\PackageType\Plugin;

/**
 * Archiver class.
 *
 * @since 0.3.0
 */
class Archiver {
	/**
	 * Logger.
	 *
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * Constructor.
	 *
	 * @param LoggerInterface $logger Logger.
	 */
	public function __construct( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Create a package artifact from the installed source.
	 *
	 * @since 0.3.0
	 *
	 * @param Package $package Installed package instance.
	 * @param string  $version Release version.
	 * @throws InvalidReleaseVersion If the version is invalid.
	 * @throws FileOperationFailed If a temporary working directory can't be created.
	 * @throws FileOperationFailed If zip creation fails.
	 * @return string Absolute path to the artifact.
	 */
	public function archive_from_source( Package $package, string $version ): string {
		if ( ! $package->is_installed() ) {
			throw PackageNotInstalled::unableToArchiveFromSource( $package );
		}

		$release     = $package->get_release( $version );
		$remove_path = \dirname( $package->get_directory() );
		$excludes    = $this->get_excluded_files( $package, $release );

		$files = $package->get_files( $excludes );

		if ( $package instanceof Plugin && $package->is_single_file() ) {
			$remove_path = $package->get_directory();
		}

		$filename = $this->get_absolute_path( $release->get_file() );

		if ( ! wp_mkdir_p( \dirname( $filename ) ) ) {
			throw FileOperationFailed::unableToCreateTemporaryDirectory( $filename );
		}

		$zip = new PclZip( $filename );

		$contents = $zip->create(
			$files,
			PCLZIP_OPT_REMOVE_PATH,
			$remove_path
		);

		if ( 0 === $contents ) {
			throw FileOperationFailed::unableToCreateZipFile( $filename );
		}

		$this->logger->info(
			'Archived {package} {version} from source.',
			[
				'package' => $package->get_name(),
				'version' => $version,
			]
		);

		return $filename;
	}

	/**
	 * Get the list of files to exclude from a package artifact.
	 *
	 * @since 0.6.0
	 *
	 * @param Package $package Installed package instance.
	 * @param Release $release Release instance.
	 * @return string[] Array of files to exclude.
	 */
	protected function get_excluded_files( Package $package, Release $release ): array {
		$dist_ignore_path = $package->get_directory() . '/.distignore';

		if ( ( ! $package instanceof Plugin || ! $package->is_single_file() ) && file_exists( $dist_ignore_path ) ) {
			$excludes = $this->get_dist_ignored_files( $dist_ignore_path );
		} else {
			$excludes = [
				'.DS_Store',
				'.git',
				'node_modules',
			];
		}

		return apply_filters( 'satispress_archive_excludes', $excludes, $release );
	}

	/**
	 * Get the list of files to exclude based on a .distignore file.
	 *
	 * @since 0.5.0
	 *
	 * @param string $dist_ignore_path Path to the .distignore file. File must already exist.
	 * @return string[]
	 */
	private function get_dist_ignored_files( string $dist_ignore_path ): array {
		$ignored_files = array();

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$maybe_ignored_files = explode( PHP_EOL, file_get_contents( $dist_ignore_path ) );

		foreach ( $maybe_ignored_files as $file ) {
			$file = trim( $file );

			if ( ! $file || 0 === strpos( $file, '#' ) ) {
				continue;
			}

			$ignored_files[] = $file;
		}

		return $ignored_files;
	}

	/**
	 * Create a package artifact from a URL.
	 *
	 * @since 0.3.0
	 *
	 * @param Release $release Release instance.
	 * @throws FileDownloadFailed  If the artifact can't be downloaded.
	 * @throws FileOperationFailed If a temporary working directory can't be created.
	 * @throws FileOperationFailed If a temporary artifact can't be renamed.
	 * @return string Absolute path to the artifact.
	 */
	public function archive_from_url( Release $release ): string {
		include_once ABSPATH . 'wp-admin/includes/file.php';

		$filename = $this->get_absolute_path( $release->get_file() );
		$tmpfname = download_url( $release->get_source_url() );

		if ( is_wp_error( $tmpfname ) ) {
			$this->logger->error(
				'Download failed.',
				[
					'error' => $tmpfname,
					'url'   => $release->get_source_url(),
				]
			);

			throw FileDownloadFailed::forFileName( $filename );
		}

		$zip = new pclzip($tmpfname);

		if ( $zip->properties()['status'] !== 'ok' ) {
			$this->logger->error(
				'File archive invalid.',
				[
					'error' => $tmpfname,
					'url'   => $release->get_source_url(),
				]
			);

			throw FileArchiveInvalid::forFileName( $filename );
		}

		if ( ! wp_mkdir_p( \dirname( $filename ) ) ) {
			throw FileOperationFailed::unableToCreateTemporaryDirectory( $filename );
		}

		if ( ! rename( $tmpfname, $filename ) ) {
			throw FileOperationFailed::unableToRenameTemporaryArtifact( $filename, $tmpfname );
		}

		$this->logger->info(
			'Archived {package} {version} from URL.',
			[
				'package' => $release->get_package()->get_name(),
				'version' => $release->get_version(),
			]
		);

		return $filename;
	}

	/**
	 * Retrieve the absolute path to a file.
	 *
	 * @since 0.3.0
	 *
	 * @param string $path Optional. Relative path.
	 * @return string
	 */
	protected function get_absolute_path( string $path = '' ): string {
		return get_temp_dir() . 'satispress/' . ltrim( $path, '/' );
	}
}
