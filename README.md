# Network Site Details - WordPress Plugin

[![Build and Release](../../actions/workflows/build-release.yml/badge.svg)](../../actions/workflows/build-release.yml)

A WordPress multisite plugin that displays detailed information about each subsite including post counts, visitor statistics, and provides data export capabilities.

## 🚀 Quick Download

**For end users**: Download the latest release from the [Releases page](../../releases) - just download the `network-site-details.zip` file and upload it to your WordPress admin panel.

## 📦 Automatic Build Process

This plugin uses **GitHub Actions** to automatically build releases with optimized vendor dependencies. Here's how it works:

### 🔄 Build Workflow

1. **No Local Build Required**: Just push code to GitHub
2. **Automatic Composer Install**: GitHub Actions runs `composer install --no-dev --optimize-autoloader`
3. **Vendor Optimization**: Removes unnecessary files (docs, tests, examples, etc.)
4. **Archive Creation**: Creates a clean, production-ready ZIP file
5. **Automatic Release**: Creates GitHub release with downloadable ZIP

### 🎯 Build Triggers

- **Tags**: Push a tag like `v1.0.1` to create a release
- **Main Branch**: Every push to main branch creates a development build
- **Manual**: Use "Run workflow" button in GitHub Actions tab

### 📊 What Gets Optimized

The build process automatically removes from vendor/:
- Documentation files (`*.md`, `README`, `CHANGELOG`)
- License files (`LICENSE`, `COPYING`)
- Test directories and files
- Example and guide directories
- Configuration files (`*.json`, `*.xml`, `*.yml`)
- Development tools (`*.bat`, `*.sh`, `composer.lock`)
- Problematic files (spaces in names, special characters)

## 🛠️ Development Setup

```bash
git clone https://github.com/yourusername/network-site-details.git
cd network-site-details
composer install
```

## 📋 Features

- **Multisite Dashboard**: Overview of all subsites with post counts and visitor data
- **Data Export**: Export site statistics to Excel using PHPSpreadsheet
- **Interactive Charts**: Visual representation using Chart.js
- **Last Activity Tracking**: Shows last post update and user login per site
- **Search & Filter**: Find specific sites quickly
- **Google API Ready**: Includes Google API client for future enhancements

## 🔧 Requirements

- WordPress Multisite Network
- PHP 8.1 or higher
- Network Admin access
- Modern web browser for charts

## 📥 Installation

### Option 1: Download from Releases (Recommended)
1. Go to [Releases page](../../releases)
2. Download `network-site-details.zip`
3. WordPress Admin → Plugins → Add New → Upload Plugin
4. Select the ZIP file and click "Install Now"
5. Network Activate the plugin

### Option 2: Development Installation
1. Clone this repository to `/wp-content/plugins/`
2. Run `composer install`
3. Network Activate through WordPress admin

## 🎨 Screenshots

The plugin provides:
- Network dashboard with site statistics
- Interactive bar charts for post and visitor counts
- Sortable tables with site information
- One-click Excel export functionality

## 🔄 Version History

See [Releases](../../releases) for detailed version history and changelogs.

## 📜 License

GPLv2 or later - see LICENSE file for details.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Push to GitHub (automatic build will test your changes)
5. Create a Pull Request

The GitHub Actions workflow will automatically test and build your changes.

---

**Note**: This plugin is optimized for WordPress.org submission with automated vendor management and clean distribution archives.
