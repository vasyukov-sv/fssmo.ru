<?php

namespace Olympia\Fssmo\Api\Types\Output;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class RequestOutput extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'RequestOutput',
			'fields' => function ()
			{
				return [
					'status' => [
						'type' => Type::boolean()
					],
					'message' => [
						'type' => Type::string()
					],
				];
			}
		];

		parent::__construct($config);
	}
}