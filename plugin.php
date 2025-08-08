<?php

/**
 * Vvveb
 *
 * Copyright (C) 2022  Ziadin Givan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

/*
Name: ActivityPub
Slug: activitypub
Category: blog
Url: https://plugins.vvveb.com/product/activitypub
Description: Publish to Fediverse
Author: givanz
Version: 0.1
Thumb: activitypub.svg
Author url: https://www.vvveb.com
Settings: /admin/index.php?module=plugins/activitypub/settings
*/

use Vvveb\System\Core\View;
use Vvveb\System\Routes;

if (! defined('V_VERSION')) {
	die('Invalid request!');
}

//use Vvveb\Plugins\McpPlugin\Remcp;

class ActivitypubPlugin {
	private $view;

	function admin() {
	}

	function app() {
		//add new route for plugin page
		Routes::addRoute('/.well-known/webfinger',  ['module' => 'plugins/activitypub/index/webfinger']);
		Routes::addRoute('/activitypub/{user}',  ['module' => 'plugins/activitypub/actors/index']);
		Routes::addRoute('/activitypub',  ['module' => 'plugins/activitypub/actors/index']);

		foreach (['following', 'followers', 'featured', 'inbox', 'outbox', 'posts', 'posts_more', 'posts_less'] as $route) {
			Routes::addRoute("/activitypub/$route",  ['module' => "plugins/activitypub/$route"]);
			Routes::addRoute("/activitypub/{user}/$route",  ['module' => "plugins/activitypub/actors/$route"]);
		}
	}

	function __construct() {
		$this->view     = View::getInstance();

		if (APP == 'admin') {
			//$this->admin();
		} else {
			if (APP == 'app') {
				$this->app();
			}
		}
	}
}

$activitypubPlugin = new ActivitypubPlugin();
