<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Olympia\Fssmo\Api\Types;

class Sponsor extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'Sponsor',
			'fields' => function ()
			{
				return [
					'id' => Type::int(),
					'title' => Type::string(),
					'image' => Type::string(),
					'url' => Type::string(),
					'type' => Type::string(),
				];
			}
		];

		parent::__construct($config);
	}
}