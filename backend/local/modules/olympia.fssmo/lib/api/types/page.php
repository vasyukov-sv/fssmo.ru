<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Olympia\Fssmo\Api\Types;

class Page extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'Page',
			'fields' => function ()
			{
				return [
					'id' => Type::string(),
					'url' => Type::string(),
					'title' => Type::string(),
					'meta' => Type::listOf(Types::getInstance()->get('Objects')),
					'breadcrumbs' => Type::listOf(Types::getInstance()->get('Objects')),
					'text' => Type::string(),
					'area' => Types::getInstance()->get('Objects'),
				];
			}
		];

		parent::__construct($config);
	}
}