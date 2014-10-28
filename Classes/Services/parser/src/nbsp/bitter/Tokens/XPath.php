<?php

	/**
	 * A simple, fast yet effective syntax highlighter for PHP.
	 *
	 * @author	Rowan Lewis <rl@nbsp.io>
	 * @package nbsp\bitter
	 */

	namespace nbsp\bitter\Tokens;
	use nbsp\bitter\Input;
	use nbsp\bitter\Lexer;

	trait XPath {
		use HTML {
			HTML::entity as htmlEntity;
			HTML::string as htmlString;
		}

		/**
		 * XPath tokens.
		 *
		 * @return array
		 */
		public function tokens() {
			return Lexer::extend($this->htmlEntity(), [
				'axis' => [
					Lexer::MATCH =>			[
												'(ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self)::',
												'[\*]|//'
											],
					Lexer::WRAP =>			'keyword axis'
				],

				'keyword' => [
					Lexer::MATCH =>			'\b(and|or|&lt;|&gt;)\b|[<=>]',
					Lexer::CALL =>			function($in, $out, $token) {
												// print_r("keyword");print_r($token);
												$out->tempStack['keyword'] = $token->value;


												// $out->startToken('predicate');

												// $begin = $token;
												// $out->writeToken($begin, 'predicate begin');

												// $end = Lexer::loop($in, $out, $this->predicate());
												// $out->writeToken($end, 'predicate end');

												// // Calculate final position:
												// if ($end) $token->position = $end->position - (
												// 	strlen($begin) - strlen($end)
												// );

												// $out->endToken();
											}
				],

				'attribute' => [
					Lexer::MATCH =>			'[@][a-z][a-z0-9_\-\:\.]*',
					Lexer::CALL =>			function($in, $out, $token) {
												// print_r("attribute");print_r($token);
												// Speicher den attributeType
												$out->tempStack['type'] = trim($token->value, '@');
												$out->writer->startAttribute(trim($token->value, '@'));

												// $out->startToken('predicate');

												// $begin = $token;
												// $out->writeToken($begin, 'predicate begin');

												// $end = Lexer::loop($in, $out, $this->predicate());
												// $out->writeToken($end, 'predicate end');

												// // Calculate final position:
												// if ($end) $token->position = $end->position - (
												// 	strlen($begin) - strlen($end)
												// );

												// $out->endToken();
											}
				],

				'variable' => [
					Lexer::MATCH =>			'[$][a-z][a-z0-9_\-\:\.]*',
					Lexer::WRAP =>			'variable'
				],

				'method' => [
					Lexer::MATCH =>			'\b[a-z][a-z0-9_\-\:\.]*(?=[(])',
					Lexer::WRAP	=>			'method'
				],

				'number' => [
					Lexer::MATCH =>			[
												'(?<![\w\.])([0-9]+[Ee][+-]?[0-9]+|([0-9]*\.[0-9]+|[0-9]+\.[0-9]*)([Ee][+-]?[0-9]+)?)(?![\w\.])',
												'(?<![\w\.])[+-]?([1-9][0-9]*|0)(?![\w\.])',
												'(?<![\w\.])[+-]?0[0-7]+(?![\w\.])',
												'(?<![\w\.])[+-]?0x[0-9a-fA-F]+(?![\w\.])'
											],
					Lexer::WRAP =>			'value number'
				],

				'predicate' => [
					Lexer::MATCH =>			'\[',
					Lexer::CALL =>			function($in, $out, $token) {
												// $out->startToken('predicate');
												// print_r("predicate");print_r($token);
												$begin = $token;
												// $out->writeToken($begin, 'predicate begin');

												$end = Lexer::loop($in, $out, $this->predicate());
												// $out->writeToken($end, 'predicate end');

												// Calculate final position:
												if ($end) $token->position = $end->position - (
													strlen($begin) - strlen($end)
												);

												// $out->endToken();
											}
				],

				'stringSingle' => [
					Lexer::MATCH =>			"'.*?'",
					Lexer::CALL =>			function($in, $out, $token) {
												// $out->startToken('value string single');
												// print_r("value");print_r($token);
												$out->tempStack['value'] = $token->value;
												// $out->startToken('value string double');
												$out->writer->text(trim($token->value, "'"));
												$out->writer->endAttribute();



												$in = new Input();
												$in->openString($token);

												Lexer::loop($in, $out, $this->htmlString());

												// $out->endToken();
											}
				],

				'stringDouble' => [
					Lexer::MATCH =>			'".*?"',
					Lexer::CALL =>			function($in, $out, $token) {
												// print_r("value");print_r($token);
												$out->tempStack['value'] = $token->value;
												
												if(count($out->tempStack) == 2) {
													// value
													$out->writer->text(trim($token->value, '"'));
													$out->writer->endElement();
												} else if(count($out->tempStack) == 3) {
													// Attribute text + end
													$out->writer->text(trim($token->value, '"'));
													$out->writer->endAttribute();
												}

												// reset tempStack
												$out->tempStack = array();
												// $out->startToken('value string double');
												

												$in = new Input();
												// $in->openString($token);

												Lexer::loop($in, $out, $this->htmlString());

												// $out->endToken();
											}
				]
			]);
		}

		public function predicate() {
			return Lexer::extend($this->tokens(), [
				'end' => [
					Lexer::MATCH =>			'\]',
					Lexer::CALL =>			Lexer::STOP
				]
			]);
		}
	}