<?php

/**
 * Vvveb
 *
 * Copyright (C) 2025  Ziadin Givan
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

use Vvveb\Controller\Base as AppBase;
use function Vvveb\siteSettings;
use Vvveb\Sql\PostSQL;
use Vvveb\System\Images;
use Vvveb\System\User\Admin;
use function Vvveb\url;

class Base extends AppBase {
	protected $user;

	function init() {
		parent::init();
		$this->user = $this->request->get['user'] ?? '';
		$this->url  = 'https://' . SITE_URL;
		$this->response->setType('activityjson');
	}

	protected function error($type, $code = 404) {
		$this->response->setType('json');

		$response = [
			'type'     => 'about:blank',
			'title'    => 'activitypub_wrong_' . $type,
			'detail'   => "Resource $type does not match blog $type",
			'status'   => $code,
			'metadata' => [
				'code'    => 'activitypub_wrong_' . $type,
				'message' => "Resource $type does not match blog $type",
				'data'    => ['status' => $code],
			],
		];

		$this->response->setStatus($code);
	}

	function index() {
		$displayName = $user ?? '';
		$bio         = $user ?? '';

		$this->response->setType('activityjson');

		if ($this->user) {
			$user = Admin::get(['username' => $this->user]);

			if ($user) {
				$url = $this->url . '/activitypub/' . $this->user;

				$response = [
					'@context' => [
						'https://www.w3.org/ns/activitystreams',
						'https://w3id.org/security/v1',
					],
					'id'                        => $url,
					'type'                      => 'Person',
					'following'                 => $url . '/following',
					'followers'                 => $url . '/followers',
					'featured'                  => $url . '/featured',
					'inbox'                     => $url . '/inbox',
					'outbox'                    => $url . '/outbox',
					'preferredUsername'         => $user['username'],
					'name'                      => $user['display_name'], //$user['first_name'] . ' ' . $user['last_name'],
					'summary'                   => $user['bio'],
					'url'                       => "{$this->url}",
					'manuallyApprovesFollowers' => false,
					'discoverable'              => true,
					'published'                 => date('c', strtotime($user['created_at'])),
					'icon'                      => [
						'type'      => 'Image',
						'mediaType' => 'image/jpeg',
						'url'       => $this->url . Images::image($user['avatar'], 'user'),
					],
					'image' => [
						'type'      => 'Image',
						'mediaType' => 'image/jpeg',
						'url'       => $this->url . Images::image($user['cover'], 'user'),
					],
				];
			} else {
				$this->error('user');
			}
		} else {
			$site = siteSettings();

			if ($site) {
				$response = [
					'@context' => [
						'https://www.w3.org/ns/activitystreams',
						'https://w3id.org/security/v1',
					],
					'id'                        => $this->url,
					'type'                      => 'Application',
					'following'                 => "{$this->url}/following",
					'followers'                 => "{$this->url}/followers",
					'featured'                  => "{$this->url}/featured",
					'inbox'                     => "{$this->url}/inbox",
					'outbox'                    => "{$this->url}/outbox",
					'preferredUsername'         => $site['description'][$this->global['language_id']]['title'],
					'name'                      => $site['description'][$this->global['language_id']]['title'], //$user['first_name'] . ' ' . $user['last_name'],
					'summary'                   => $user['bio'],
					'url'                       => $this->url,
					'manuallyApprovesFollowers' => true,
					'discoverable'              => true,
					//'published'                 => date('Y-m-d\TH:i:sP', strtotime($site['created_at'])),
					'icon'                      => [
						'type'      => 'Image',
						'mediaType' => 'image/jpeg',
						'url'       => $site['logo'],
					],
					'image' => [
						'type'      => 'Image',
						'mediaType' => 'image/jpeg',
						'url'       => $site['webbanner'],
					],
				];
			} else {
				$this->error('host');
			}
		}

		return $response;
	}

	function remoteFollow() {
		return [];
	}

	function remoteReply() {
		return [];
	}

	function inbox() {
		return  [
			'@context'     => ['https://www.w3.org/ns/activitystreams'],
			'id'           => "{$this->url}/activitypub/{$this->user}/inbox",
			'type'         => 'OrderedCollection',
			'totalItems'   => 0,
			'orderedItems' => [],
		];
	}

	function outbox() {
		if ($this->user) {
			$user = Admin::get(['username' => $this->user]);

			$this->response->setType('activityjson');

			if ($user) {
				$postModel = new PostSQL();
				$posts     = $postModel->getAll(['admin_id' => $user['admin_id']] + $this->global);

				if ($posts) {
					$items = [];

					foreach ($posts['post'] as $post) {
						$url     = $this->url . '/' . $post['slug'];
						$items[] = [/*
							'@context' => [
								'https://www.w3.org/ns/activitystreams',
								[
									'ostatus'          => 'http://ostatus.org#',
									'atomUri'          => 'ostatus:atomUri',
									'inReplyToAtomUri' => 'ostatus:inReplyToAtomUri',
									'conversation'     => 'ostatus:conversation',
									'sensitive'        => 'as:sensitive',
									'toot'             => 'http://joinmastodon.org/ns#',
									'votersCount'      => 'toot:votersCount',
								],
							],*/
							'id'               => $url,
							'type'             => 'Note',
							'to'               => ['https://www.w3.org/ns/activitystreams#Public'],
							'cc'               => ["{$this->url}/{$this->user}/followers"],
								'id'                => $url,
								'summary'           => null,
								'inReplyTo'         => null,
								'published'         => date('c', strtotime($post['created_at'])),
								'updated'           => date('c', strtotime($post['updated_at'])),
								'url'               => $url,
								'attributedTo'      => "{$this->url}/activitypub/{$this->user}",
								'sensitive'         => false,
								'atomUri'           => $url,
								'inReplyToAtomUri'  => null,
								'conversation'      => "tag:{$this->url}," . date('Y-m-d', strtotime($post['created_at'])) . ':objectId=' . $post['post_id'] . ':objectType=Conversation',
								'content'           => $post['content'],
								'contentMap'        => ['en' => $post['content']],
								'attachment'        => [],
								'tag'               => [],
								'replies'           => [/*
									'id'    => "{$this->url}/posts/1/replies",
									'type'  => 'Collection',
									'first' => [
										'type'   => 'CollectionPage',
										'next'   => "{$this->url}/posts/1/replies_more",
										'partOf' => "{$this->url}/posts/1/replies",
										'items'  => [],
									*/],
								],
						];
					}

					$response = [
						'@context'     => 'https://www.w3.org/ns/activitystreams',
						'id'           => $this->url . '/outbox',
						'actor'            => "{$this->url}/activitypub/{$this->user}",
						'type'         => 'OrderedCollection',
						'totalItems'   => $posts['count'],
						'orderedItems' => $items,
						//'first'      => $this->global['site']['url'] . '/toots',
					];
				}

				return $response;
			} else {
				$this->error('user');
			}
		}
		{
			$this->error('user');
		}
	}

	function followers() {
		$response = [
			'@context'     => ['https://www.w3.org/ns/activitystreams'],
			'id'           => "{$this->url}/activitypub/{$this->user}/followers",
			'type'         => 'OrderedCollection',
			'totalItems'   => 0,
			'orderedItems' => [],
		];

		return $response;
	}

	function following() {
		$response = [
			'@context'     => ['https://www.w3.org/ns/activitystreams'],
			'id'           => "{$this->url}/activitypub/{$this->user}/following",
			'type'         => 'OrderedCollection',
			'totalItems'   => 0,
			'orderedItems' => [],
		];

		return $response;
	}

	function collections() {
		$response = [
			'@context'     => ['https://www.w3.org/ns/activitystreams'],
			'id'           => "{$this->url}/activitypub/{$this->user}/collections",
			'type'         => 'OrderedCollection',
			'totalItems'   => 0,
			'orderedItems' => [],
		];

		return $response;
	}

	function posts() {
		return [];
	}
}
