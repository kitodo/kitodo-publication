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

	class PHP extends Lexer {
		use Tokens\PHP;

		/**
		 * Parse a PHP file.
		 *
		 * @param Output $out
		 * @param string $in
		 */
		public function parse(Input $in, Output $out) {
			$out->startLine();
			$out->startToken('source php');

			// Begin parsing:
			Lexer::loop($in, $out, $this->tokens());

			$out->endToken();
			$out->endLine();
		}

		/**
		 * Parse PHP embedded in a HTML file.
		 *
		 * @param Output $out
		 * @param string $in
		 */
		public function parseWithHTML(Input $in, Output $out) {
			$out->startLine();
			$out->startToken('markup html');

			// Begin parsing:
			Lexer::loop($in, $out, Lexer::extend($this->htmlTokens(), [
				'beginPHP' => [
					Lexer::MATCH =>		'<\?php',
					Lexer::CALL =>		function($in, $out, $token) {
											$out->startToken('script php');

											$begin = $token;
											$out->writeToken($begin, 'keyword script begin');

											$end = Lexer::loop($in, $out, Lexer::extend(
												$this->tokens(),
												[
													'endPHP' => [
														Lexer::MATCH =>		'\?>',
														Lexer::WRAP =>		'keyword',
														Lexer::CALL =>		Lexer::STOP
													]
												]
											));
											$out->writeToken($end, 'keyword script end');

											// Calculate final position:
											if ($end) $token->position = $end->position - (
												strlen($begin) - strlen($end)
											);

											$out->endToken();
										}
				]
			]));

			$out->endToken();
			$out->endLine();
		}
	}