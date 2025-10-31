=== Hook Explorer ===
Contributors: kiunye
Donate link: https://kiunyearaya.dev/
Tags: hooks, actions, filters, development, debugging, documentation
Requires at least: 6.0
Requires PHP: 8.1
Tested up to: 6.8
Stable tag: 0.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Discover and explore WordPress hooks (actions and filters) across core, plugins, and themes with static and runtime discovery.

== Description ==

Hook Explorer is a powerful WordPress development tool that helps you discover, document, and understand WordPress hooks throughout your site. It scans your WordPress installation to find all `do_action()` and `apply_filters()` calls, making it easier to understand hook dependencies and create proper integrations.

**Key Features:**

* **Static Discovery**: Scans PHP files to find hooks defined in WordPress core, active plugins, and themes
* **Runtime Capture**: Optionally captures hooks as they fire during page requests (with sampling for performance)
* **Interactive UI**: Modern React-based admin interface with search, filtering, and pagination
* **Documentation Export**: Generate markdown documentation of all discovered hooks
* **REST API**: Full REST API for programmatic access to hook data
* **Background Processing**: Efficiently handles large-scale scans with background queue processing

**Use Cases:**

* Understand which hooks are available in your WordPress installation
* Discover plugin and theme hook dependencies
* Document your custom hooks for team members
* Debug hook execution order and timing
* Plan integrations and customizations

The plugin creates a comprehensive database of hooks with details about their source (core/plugin/theme), file location, line numbers, and hook types (action vs filter).

A few notes about the sections above:

*   "Contributors" is a comma separated list of wp.org/wp-plugins.org usernames
*   "Tags" is a comma separated list of tags that apply to the plugin
*   "Requires at least" is the lowest version that the plugin will work on
*   "Tested up to" is the highest version that you've *successfully used to test the plugin*. Note that it might work on
higher versions... this is just the highest one you've verified.
*   Stable tag should indicate the Subversion "tag" of the latest stable version, or "trunk," if you use `/trunk/` for
stable.

    Note that the `readme.txt` of the stable tag is the one that is considered the defining one for the plugin, so
if the `/trunk/readme.txt` file says that the stable tag is `4.3`, then it is `/tags/4.3/readme.txt` that'll be used
for displaying information about the plugin.  In this situation, the only thing considered from the trunk `readme.txt`
is the stable tag pointer.  Thus, if you develop in trunk, you can update the trunk `readme.txt` to reflect changes in
your in-development version, without having that information incorrectly disclosed about the current stable version
that lacks those changes -- as long as the trunk's `readme.txt` points to the correct stable tag.

    If no stable tag is provided, it is assumed that trunk is stable, but you should specify "trunk" if that's where
you put the stable version, in order to eliminate any doubt.

== Installation ==

1. Upload the `hook-directory` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Settings > Hook Explorer** to configure scan options
4. Click **"Scan Now"** in the List tab to discover hooks on your site

**First-time Setup:**

After activation, the plugin will create a database table to store discovered hooks. If the table creation fails during activation, you can manually trigger it via the REST API endpoint `/wp-json/hook-explorer/v1/create-table` (requires admin permissions).

**Building the Admin UI:**

The plugin includes a React-based admin interface. To build the assets:

```bash
cd admin/js/app
npm install
npm run build
```

The built assets will be automatically loaded by WordPress.

== Frequently Asked Questions ==

= How does static discovery work? =

Static discovery scans PHP files using token parsing to find `do_action()`, `do_action_ref_array()`, `apply_filters()`, and `apply_filters_ref_array()` calls. It searches WordPress core, active plugins, and themes based on your settings.

= What is runtime capture? =

Runtime capture listens to the `all` hook to record hooks as they fire during page requests. You can enable sampling (e.g., capture every Nth request) to reduce performance impact on production sites.

= Will this slow down my site? =

Static scans run on-demand and are optimized with background processing for large installations. Runtime capture can be disabled or configured with sampling to minimize performance impact. The plugin is designed to be lightweight during normal operation.

= Can I export the hook data? =

Yes! The plugin includes a REST API endpoint (`/wp-json/hook-explorer/v1/docs`) that returns markdown documentation of all discovered hooks. You can also access hook data programmatically via the `/hooks` endpoint.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= 0.1.0 =
* Initial release
* Static discovery engine with token-based PHP parsing
* Runtime hook capture with sampling support
* React-based admin UI with List tab
* REST API endpoints for hooks, stats, docs, and scanning
* Background processing for large-scale scans
* Documentation generator with markdown export

== Upgrade Notice ==

= 0.1.0 =
Initial release of Hook Explorer. Install to start discovering and documenting WordPress hooks across your site.