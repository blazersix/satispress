<?php
/**
 * Composer repository transformer.
 *
 * @package SatisPress
 * @license GPL-2.0-or-later
 * @since 0.3.0
 */

declare ( strict_types = 1 );

namespace SatisPress\Transformer;

use Psr\Log\LoggerInterface;
use SatisPress\Capabilities;
use SatisPress\Exception\FileNotFound;
use SatisPress\Exception\SatispressException;
use SatisPress\Package;
use SatisPress\ReleaseManager;
use SatisPress\Repository\PackageRepository;
use SatisPress\VersionParser;
use UnexpectedValueException;

/**
 * Composer repository transformer class.
 *
 * @since 0.3.0
 */
class ComposerRepositoryTransformer implements PackageRepositoryTransformer {
	/**
	 * Release manager.
	 *
	 * @var ReleaseManager
	 */
	protected $release_manager;

	/**
	 * Composer package transformer.
	 *
	 * @var PackageTransformer.
	 */
	protected $composer_transformer;

	/**
	 * Logger.
	 *
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * Version parser.
	 *
	 * @var VersionParser
	 */
	protected $version_parser;

	/**
	 * Constructor.
	 *
	 * @since 0.3.0
	 *
	 * @param PackageTransformer $composer_transformer Composer package transformer.
	 * @param ReleaseManager     $release_manager      Release manager.
	 * @param VersionParser      $version_parser       Version parser.
	 * @param LoggerInterface    $logger               Logger.
	 */
	public function __construct(
		PackageTransformer $composer_transformer,
		ReleaseManager $release_manager,
		VersionParser $version_parser,
		LoggerInterface $logger
	) {
		$this->composer_transformer = $composer_transformer;
		$this->release_manager      = $release_manager;
		$this->version_parser       = $version_parser;
		$this->logger               = $logger;
	}

	/**
	 * Transform a repository of packages into the format used in packages.json.
	 *
	 * @since 0.3.0
	 *
	 * @param PackageRepository $repository Package repository.
	 * @return array
	 */
	public function transform( PackageRepository $repository ): array {
		$items = [];

		foreach ( $repository->all() as $slug => $package ) {
			/* @var Package $package Package. */
			if ( ! $package->has_releases() ) {
				continue;
			}

			$package = $this->composer_transformer->transform( $package );
			$item    = $this->transform_item( $package );

			// Skip if there aren't any viewable releases.
			if ( empty( $item ) ) {
				continue;
			}

			$items[ $package->get_name() ] = $item;
		}

		return [ 'packages' => $items ];
	}

	/**
	 * Transform an item.
	 *
	 * @param Package $package Package instance.
	 * @return array
	 */
	protected function transform_item( Package $package ): array {
		$data = [];

		foreach ( $package->get_releases() as $release ) {
			// Cache the release in case an artifact doesn't already exist for
			// the installed version.
			if ( $package->is_installed() && $package->is_installed_release( $release ) ) {
				try {
					$release = $this->release_manager->archive( $release );
				} catch ( SatispressException $e ) {
					$this->logger->error(
						'Error archiving {package}.',
						[
							'exception' => $e,
							'package'   => $package->get_name(),
						]
					);
				}
			}

			$version = $release->get_version();

			try {
				$data[ $version ] = [
					'name'               => $package->get_name(),
					'version'            => $version,
					'version_normalized' => $this->version_parser->normalize( $version ),
					'dist'               => [
						'type'   => 'zip',
						'url'    => $release->get_download_url(),
						'shasum' => $this->release_manager->checksum( 'sha1', $release ),
					],
					'require'            => [
						'composer/installers' => '^1.0',
					],
					'type'               => $package->get_type(),
					'authors'            => [
						'name'     => $package->get_author(),
						'homepage' => esc_url( $package->get_author_url() ),
					],
					'description'        => $package->get_description(),
					'homepage'           => $package->get_homepage(),
				];
			} catch ( FileNotFound $e ) {
				$this->logger->error(
					'Package artifact could not be found for {package}:{version}.',
					[
						'exception' => $e,
						'package'   => $package->get_name(),
						'version'   => $version,
					]
				);
			} catch ( UnexpectedValueException $e ) {
				$this->logger->error(
					'Invalid version string for {package}:{version}.',
					[
						'exception' => $e,
						'package'   => $package->get_name(),
						'version'   => $version,
					]
				);
			}
		}

		return $data;
	}
}
