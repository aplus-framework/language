<?php namespace PHPSTORM_META;

registerArgumentsSet(
	'language_fallback_level',
	\Framework\Language\Language::FALLBACK_NONE,
	\Framework\Language\Language::FALLBACK_PARENT,
	\Framework\Language\Language::FALLBACK_DEFAULT
);
expectedArguments(
	\Framework\Language\Language::setFallbackLevel(),
	0,
	argumentsSet('language_fallback_level')
);
