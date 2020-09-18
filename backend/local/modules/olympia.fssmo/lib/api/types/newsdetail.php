<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Olympia\Fssmo\Api\Types;

class NewsDetail extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'NewsDetail',
			'fields' => function ()
			{
				return [
					'url' => Type::string(),
					'title' => Type::string(),
					'preview' => Type::string(),
					'text' => Type::string(),
					'date' => Type::string(),
					'arrows' => Types::getInstance()->get('Objects'),
					'similar' => Type::listOf(Types::getInstance()->get('News')),
					'discipline' => Type::string(),
					'competition' => Types::getInstance()->get('Objects'),
				];
			}
		];

		parent::__construct($config);
	}
}