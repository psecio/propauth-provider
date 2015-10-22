<?php

namespace Psecio\PropAuth;

use Blade;
use Illuminate\Support\ServiceProvider;

class PolicyTemplateServiceProvider extends ServiceProvider
{
	/**
	 * Boot the provider and register our Blade extension
	 */
	public function boot()
	{
		Blade::extend(function($value, $obj) {
			// See if we need an enforcer
			if (strpos($value, '@allows') === false && strpos('@denies', $value) === false) {
				return $value;
			}

			// Add the enforcer to the page
			$value = '<?php $enforcer = \App::make("policies"); ?>'.$value;

			// Handle @allows and @denies
			$value = $this->parse($value);
			return $value;
		});
	}

	/**
	 * Parse the template and perform replacements for allows/denies
	 *
	 * @param string $value Template contents
	 * @return string Modified template contents
	 */
	protected function parse($value)
	{
		// Replace the check starts
		preg_match_all('/(@(allows|denies)\()(.*?)(\))/ms', $value, $matches);

		foreach ($matches[0] as $index => $match) {
			$params = $matches[3][$index];
			$type = $matches[2][$index];

			// if we have a comma, we have extra info
			$replaceWith = (strpos($params, ',') !== false)
				? $this->replaceComplex($type, $params) : $this->replaceSimple($type, $params);
			$value = str_replace($match, $replaceWith, $value);

			// Replace the end of the check
			$value = str_replace('@end'.$type, '<?php endif; ?>', $value);
		}

		return $value;
	}

	/**
	 * Perform a simple replacement on the type (allows/denies) check
	 *
	 * @param string $type Type of check (allows, denies)
	 * @param string $params Parameter string
	 * @return string Formatted replacement string
	 */
	protected function replaceSimple($type, $params)
	{
		return '<?php if ($enforcer->'.$type.'('.$params.', \Auth::user()) === true): ?>';
	}

	/**
	 * Perform a more complex replacement on the type (allows/denies) check
	 * 	This usually means that they have additional parameters on the check
	 *
	 * @param string $type Type of check (allows, denies)
	 * @param string $params Parameter string
	 * @return string Formatted replacement string
	 */
	protected function replaceComplex($type, $params)
	{
		$params = array_map(function($value) {
			return trim($value);
		}, explode(',', $params));
		$policyName = array_shift($params);
		$addl = '['.implode(',', $params).']';

		$replaceWith = '<?php if ($enforcer->'.$type.'('.$policyName.', \Auth::user(), '.$addl.') === true) : ?>';
		return $replaceWith;
	}

	public function register()
	{
		// nothing to see, move along
	}
}