<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Olympia\Fssmo\Api\Types;

class PhotoAlbum extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'PhotoAlbum',
			'fields' => function ()
			{
				return [
					'id' => Type::int(),
					'title' => Type::string(),
					'date' => Type::string(),
					'location' => Type::string(),
					'url' => Type::string(),
					'photos' => Type::listOf(Types::getInstance()->get('Photo')),
				];
			}
		];

		parent::__construct($config);
	}
}