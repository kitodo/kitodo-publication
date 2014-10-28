<?php

	/**
	 * A simple, fast yet effective syntax highlighter for PHP.
	 *
	 * @author	Rowan Lewis <rl@nbsp.io>
	 * @package nbsp\bitter
	 */

	namespace nbsp\bitter\Lexers;
	use nbsp\bitter\Input;
	use nbsp\bitter\Output;
	use nbsp\bitter\Lexer;
	use nbsp\bitter\Tokens;

	class CSS extends Lexer {
		use Tokens\CSS;

		/**
		 * Parse a CSS file.
		 *
		 * @param Output $out
		 * @param string $in
		 */
		public function parse(Input $in, Output $out) {
			$out->startLine();
			$out->startToken('stylesheet css');

			// Begin parsing:
			Lexer::loop($in, $out, $this->tokens());

			$out->endToken();
			$out->endLine();
		}
	}