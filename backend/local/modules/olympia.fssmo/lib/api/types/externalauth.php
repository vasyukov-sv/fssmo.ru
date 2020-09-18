<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class ExternalAuth extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'ExternalAuth',
			'fields' => function ()
			{
				return [
					'id' => Type::string(),
					'name' => Type::string(),
					'link' => Type::string(),
				];
			}
		];

		parent::__construct($config);
	}
}