<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Olympia\Fssmo\Api\Types;

class News extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'News',
			'fields' => function ()
			{
				return [
					'id' => Type::int(),
					'url' => Type::string(),
					'title' => Type::string(),
					'preview' => Type::string(),
					'text' => Type::string(),
					'date' => Type::string(),
					'image' => Type::string(),
					'discipline' => Type::string(),
					'competition' => Types::getInstance()->get('Objects'),
				];
			}
		];

		parent::__construct($config);
	}
}