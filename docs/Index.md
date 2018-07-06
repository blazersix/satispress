# SatisPress Documentation

Generate a Composer repository from installed WordPress plugins and themes.

## Why a WordPress Installation?

Many plugins and themes don't have public repositories, so managing them with Composer can be a hassle. Instead, SatisPress allows you to manage them in a standard WordPress installation, leveraging the built-in update process to handle the myriad licensing schemes that would be impossible to account for outside of WordPress.

The whitelisted packages are exposed via an automatically generated `packages.json` for inclusion as a Composer repository in a project's `composer.json` or even your own `satis.json`.

## Table of Contents

1. [Installation](Installation.md)
1. Managing SatisPress
	1. [Whitelisting Plugins and Themes](Whitelisting.md)
	1. [Settings](Settings.md)
	1. [Packages](Packages.md)
	1. [Security](Security.md)
1. [Requiring SatisPress Packages](Requiring.md) 
1. [Integrations](Integrations.md)
1. [Troubleshooting](Troubleshooting.md)
