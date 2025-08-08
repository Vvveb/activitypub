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

namespace Vvveb\Plugins\Activitypub\Controller;

use function Vvveb\siteSettings;
use function Vvveb\url;

class Index extends Base {
	protected $user;

	function webfinger() {
		//$site        = siteSettings();
		$resource = $this->request->get['resource'] ?? '';
		$host     = '';

		if (preg_match('/acct:(?<user>.+?)@(?<host>.+)/', $resource, $matches)) {
			$user = $matches['user'];
			$host = $matches['host'];
		}

		if ($host && $user) {
			$url     = trim(url('index/index', ['host' => $host, 'scheme' => 'https' /*$_SERVER['REQUEST_SCHEME'] ?? 'https'*/]), '/');

			$acct = [
				'subject' => "acct:$user@$host",
				/*
				'aliases' => [
					"$url/activitypub/$user",
				],*/
				'links' => [
					[
						'rel'        => 'self',
						'type'       => 'application/activity+json',
						'href'       => "$url/activitypub/$user",
						/*
						'properties' => [
							'https://www.w3.org/ns/activitystreams#type' => $user,
						],*/
					], /*
					[
						'rel'  => 'http://webfinger.net/rel/profile-page',
						'type' => 'text/html',
						'href' => "$url/activitypub/$user",
					],
					[
						'rel'      => 'http://ostatus.org/schema/1.0/subscribe',
						'template' => "$url/activitypub/interactions?uri={uri}",
					],*/
				],
			];
		} else {
			$acct = [
				'type'     => 'about:blank',
				'title'    => 'activitypub_wrong_host',
				'detail'   => 'Resource host does not match blog host',
				'status'   => 404,
				'metadata' => [
					'code'    => 'activitypub_wrong_host',
					'message' => 'Resource host does not match blog host',
					'data'    => ['status' => 404],
				],
			];

			$this->response->setStatus(404);
		}

		$this->response->setType('jrdjson');
		$this->response->output($acct);
	}
}
