# SatisPress

A WordPress plugin that can generate a Composer repository from installed plugins and themes.

## Why a WordPress Installation?

Many plugins and themes don't have public repositories, so managing them with Composer can be a hassle. Instead, SatisPress allows you to manage them in a standard WordPress installation, leveraging the built-in update process to handle the myriad licensing schemes that would be impossible to account for outside of WordPress.

The whitelisted packages are exposed via an automatically generated `packages.json` for inclusion as a Composer repository in a project's `composer.json` or even your own `satis.json`.

## Documentation

For installation notes, and information about usage, security and more, see the [Documentation](docs/Index.md).

## Credits

Created by [Brady Vercher](https://www.blazersix.com/) and supported by [Gary Jones](https://gamajo.com).
