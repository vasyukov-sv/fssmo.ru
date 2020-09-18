<?php

namespace Olympia\Fssmo\Api;

use Bitrix\Main\Loader;
use GraphQL\Error\Debug;
use GraphQL\Error\Error;
use GraphQL\Server\ServerConfig;
use GraphQL\Server\StandardServer;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules;

class Application
{
	public static function run ()
	{
		Loader::includeModule('iblock');

		try
		{
			DocumentValidator::addRule(new Rules\QueryDepth(2));
			DocumentValidator::addRule(new Rules\QueryComplexity(100));
			DocumentValidator::addRule(new Rules\DisableIntrospection());

			$registry = Types::getInstance();

			$schema = SchemaConfig::create()
				->setQuery(new Query())
				->setMutation(new Mutation())
			    ->setTypeLoader(function($name) use ($registry)
				{
					return $registry->get($name);
				});

			$schema = new Schema($schema);

			global $USER;

			$debug = false;

			if ($USER->IsAdmin())
				$debug = Debug::INCLUDE_DEBUG_MESSAGE | Debug::INCLUDE_TRACE;

			$context = [
				'user' => $USER->IsAuthorized() ? (int) $USER->GetID() : null
			];

			$errorFormatter = function(Error $error)
			{
			    return [
			    	'message' => $error->getMessage()
				];
			};

			$config = ServerConfig::create()
				->setSchema($schema)
				->setContext($context)
				->setRootValue([])
				->setQueryBatching(true)
				->setDebug($debug)
				->setErrorFormatter($errorFormatter);

			$server = new StandardServer($config);
			$server->handleRequest();
		}
		catch (\Exception $e)
		{
			$httpStatus = 500;

			$output = [
				'errors' => [[
					'message' => $e->getMessage()
				]]
			];

			header('Content-Type: application/json', true, $httpStatus);
			echo json_encode($output);
		}
	}
}