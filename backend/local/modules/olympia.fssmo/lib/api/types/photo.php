<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Olympia\Fssmo\Api\Types;

class Photo extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'Photo',
			'fields' => function ()
			{
				return [
					'title' => Type::string(),
					'preview' => Type::string(),
					'src' => Type::string(),
					'ratio' => Type::float(),
				];
			}
		];

		parent::__construct($config);
	}
}